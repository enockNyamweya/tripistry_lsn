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
                    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
                    JOIN USER u ON ta.UserID = u.UserID
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
                $query = "
                    SELECT p.PackageID, p.Title, p.Price, p.DurationDays, ta.AgencyName,
                        COALESCE((SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID), 0) as AvgRating,
                        d.City as DestinationCity
                    FROM TRAVEL_PACKAGE p
                    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
                    JOIN HAS_DESTINATION hd ON p.PackageID = hd.PackageID
                    JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
                    WHERE (d.City LIKE :dest OR d.Country LIKE :dest OR d.DestinationID = :dest_id) AND p.Status = 'Active'
                ";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':dest' => '%' . $destVal . '%',
                    ':dest_id' => is_numeric($destVal) ? (int)$destVal : -1
                ]);
                $packages = $stmt->fetchAll();
                echo json_encode(["comparison" => $packages]);
                
            } else {
                // Get all packages with filtering and sorting
                $query = "
                    SELECT p.*, ta.AgencyName,
                        COALESCE((SELECT AVG(RatingScore) FROM REVIEW r WHERE r.PackageID = p.PackageID), 0) as AvgRating,
                        (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
                    FROM TRAVEL_PACKAGE p
                    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
                    WHERE p.Status = 'Active'
                ";
                $params = [];
                
                if (isset($_GET['destination'])) {
                    $query .= " AND p.PackageID IN (
                        SELECT hd.PackageID FROM HAS_DESTINATION hd
                        JOIN DESTINATION d ON hd.DestinationID = d.DestinationID
                        WHERE d.City LIKE :destination OR d.Country LIKE :destination
                    )";
                    $params[':destination'] = '%' . $_GET['destination'] . '%';
                }
                if (isset($_GET['min_price'])) {
                    $query .= " AND p.Price >= :min_price";
                    $params[':min_price'] = (float)$_GET['min_price'];
                }
                if (isset($_GET['max_price'])) {
                    $query .= " AND p.Price <= :max_price";
                    $params[':max_price'] = (float)$_GET['max_price'];
                }
                
                $sort = $_GET['sort'] ?? 'price_asc';
                if ($sort === 'price_asc') {
                    $query .= " ORDER BY p.Price ASC";
                } elseif ($sort === 'price_desc') {
                    $query .= " ORDER BY p.Price DESC";
                } elseif ($sort === 'duration') {
                    $query .= " ORDER BY p.DurationDays ASC";
                } elseif ($sort === 'rating') {
                    $query .= " ORDER BY AvgRating DESC";
                }
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $packages = $stmt->fetchAll();
                
                echo json_encode($packages);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
