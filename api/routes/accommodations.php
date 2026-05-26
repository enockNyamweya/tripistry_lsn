<?php
// api/routes/accommodations.php

function handleAccommodationsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM ACCOMMODATION WHERE AccommodationID = :id");
                $stmt->execute([':id' => (int)$id]);
                $item = $stmt->fetch();
                
                if ($item) {
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Accommodation not found."]);
                }
            } else {
                $whereClause = " FROM ACCOMMODATION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['min_stars'])) {
                    $whereClause .= " AND a.StarRating >= :stars";
                    $params[':stars'] = (int)$_GET['min_stars'];
                }
                if (isset($_GET['destination_id'])) {
                    $whereClause .= " AND a.DestinationID = :dest";
                    $params[':dest'] = (int)$_GET['destination_id'];
                }
                
                $countQuery = "SELECT COUNT(1)" . $whereClause;
                $selectQuery = "SELECT a.*, d.City, d.Country" . $whereClause;

                // Sorting
                $sort = $_GET['sort'] ?? '';
                if ($sort === 'price_asc') {
                    $selectQuery .= " ORDER BY a.PricePerNight ASC";
                } elseif ($sort === 'price_desc') {
                    $selectQuery .= " ORDER BY a.PricePerNight DESC";
                } else {
                    $selectQuery .= " ORDER BY a.StarRating DESC";
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
