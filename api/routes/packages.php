<?php
// api/routes/packages.php

function handlePackagesRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get specific package
                $stmt = $pdo->prepare("
                    SELECT p.*, ta.AgencyName, u.Email,
                        (SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID) as AvgRating,
                        (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
                    FROM TRAVEL_PACKAGE p
                    JOIN USER u ON p.AgencyID = u.UserID
                    JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
                    WHERE p.PackageID = :id
                ");
                $stmt->execute([':id' => $id]);
                $package = $stmt->fetch();
                
                if ($package) {
                    echo json_encode($package);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Package not found."]);
                }
            } else if (isset($_GET['compare'])) {
                // Task B: Package comparison logic
                $compareIds = explode(',', $_GET['compare']);
                $compareIds = array_map('intval', array_filter($compareIds, 'is_numeric'));
                
                if (empty($compareIds)) {
                    http_response_code(400);
                    echo json_encode(["message" => "Invalid compare parameter. Use ?compare=id1,id2"]);
                    break;
                }
                
                $placeholders = implode(',', array_fill(0, count($compareIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT p.PackageID, p.Title, p.Price, p.DurationDays, ta.AgencyName,
                        COALESCE((SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID), 0) as AvgRating,
                        (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
                    FROM TRAVEL_PACKAGE p
                    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
                    WHERE p.PackageID IN ($placeholders)
                ");
                $stmt->execute($compareIds);
                $packages = $stmt->fetchAll();
                
                echo json_encode(["comparison" => $packages]);
                
            } else if (isset($_GET['compare_destination'])) {
                // Return packages for the same destination side-by-side
                $destVal = $_GET['compare_destination'];
                // upsert correction — unique param names for PDO
                $query = "
                    SELECT p.PackageID, p.Title, p.Price, p.DurationDays, ta.AgencyName,
                        COALESCE((SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID), 0) as AvgRating,
                        d.City as DestinationCity
                    FROM TRAVEL_PACKAGE p
                    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
                    JOIN HAS_DESTINATION hd ON p.PackageID = hd.PackageID
                    JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
                    WHERE (d.City LIKE :dest_city OR d.Country LIKE :dest_country OR d.DestinationID = :dest_id) AND p.Status = 'Active'
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':dest_city' => '%' . $destVal . '%',
                    ':dest_country' => '%' . $destVal . '%',
                    ':dest_id' => is_numeric($destVal) ? (int)$destVal : -1
                ]);
                $packages = $stmt->fetchAll();
                echo json_encode(["comparison" => $packages]);
                
            } else {
                // Get all packages with filtering and sorting
                $whereClause = " FROM TRAVEL_PACKAGE p
                    JOIN USER u ON p.AgencyID = u.UserID
                    JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
                    WHERE p.Status = 'Active'";
                $params = [];
                
                if (isset($_GET['destination']) && $_GET['destination'] !== '') {
                    $whereClause .= " AND p.PackageID IN (
                        SELECT hd.PackageID FROM HAS_DESTINATION hd
                        JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
                        WHERE d.City = :destination OR d.Country = :destination
                    )";
                    $params[':destination'] = $_GET['destination'];
                }
                if (isset($_GET['search']) && $_GET['search'] !== '') {
                    $whereClause .= " AND (p.Title LIKE :search OR p.Description LIKE :search OR ta.AgencyName LIKE :search)";
                    $params[':search'] = '%' . $_GET['search'] . '%';
                }
                $minPriceFilter = isset($_GET['min_price']) && $_GET['min_price'] !== ''
                    ? (float)$_GET['min_price'] : null;
                $maxPriceFilter = isset($_GET['max_price']) && $_GET['max_price'] !== ''
                    ? (float)$_GET['max_price'] : null;
                if ($minPriceFilter !== null && $minPriceFilter <= 0) {
                    $minPriceFilter = null;
                }
                if ($maxPriceFilter !== null && $maxPriceFilter <= 0) {
                    $maxPriceFilter = null;
                }
                if ($minPriceFilter !== null && $maxPriceFilter !== null && $maxPriceFilter < $minPriceFilter) {
                    $maxPriceFilter = null;
                }
                if ($minPriceFilter !== null) {
                    $whereClause .= " AND p.Price >= :min_price";
                    $params[':min_price'] = $minPriceFilter;
                }
                if ($maxPriceFilter !== null) {
                    $whereClause .= " AND p.Price <= :max_price";
                    $params[':max_price'] = $maxPriceFilter;
                }

                $havingClause = '';
                if (isset($_GET['min_rating']) && $_GET['min_rating'] !== '') {
                    $havingClause = " HAVING AvgRating >= :min_rating OR AvgRating IS NULL";
                    $params[':min_rating'] = (float)$_GET['min_rating'];
                }
                
                $countQuery = "SELECT COUNT(1) FROM (
                    SELECT p.PackageID,
                        COALESCE((SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID), 0) as AvgRating
                    " . $whereClause . $havingClause . "
                ) pkg_count";

                $selectQuery = "
                    SELECT p.*, ta.AgencyName, u.Email,
                        CURRENT_DATE() as StartDate,
                        DATE_ADD(CURRENT_DATE(), INTERVAL p.DurationDays DAY) as EndDate,
                        20 as MaxTravellers,
                        COALESCE((SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID), 0) as AvgRating,
                        (SELECT COUNT(*) FROM REVIEW r3 WHERE r3.PackageID = p.PackageID) as ReviewCount,
                        (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity,
                        (SELECT d.Country FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCountry
                " . $whereClause . $havingClause;
                
                $sort = $_GET['sort'] ?? 'price_asc';
                if ($sort === 'price_asc') {
                    $selectQuery .= " ORDER BY p.Price ASC";
                } elseif ($sort === 'price_desc') {
                    $selectQuery .= " ORDER BY p.Price DESC";
                } elseif ($sort === 'duration' || $sort === 'duration_asc') {
                    $selectQuery .= " ORDER BY p.DurationDays ASC";
                } elseif ($sort === 'duration_desc') {
                    $selectQuery .= " ORDER BY p.DurationDays DESC";
                } elseif ($sort === 'rating' || $sort === 'rating_desc') {
                    $selectQuery .= " ORDER BY AvgRating DESC";
                } elseif ($sort === 'rating_asc') {
                    $selectQuery .= " ORDER BY AvgRating ASC";
                } elseif ($sort === 'title_asc') {
                    $selectQuery .= " ORDER BY p.Title ASC";
                } elseif ($sort === 'date_asc') {
                    $selectQuery .= " ORDER BY p.PackageID ASC";
                }
                
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
                
                $response = getPaginatedResponse($pdo, $countQuery, $selectQuery, $params, $page, $limit);
                echo json_encode($response);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
