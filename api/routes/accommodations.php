<?php
// api/routes/accommodations.php

function handleAccommodationsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM ACCOMMODATION WHERE AccommodationID = :id");
                $stmt->execute([':id' => $id]);
                $item = $stmt->fetch();
                
                if ($item) {
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Accommodation not found."]);
                }
            } else {
                $query = "SELECT a.*, d.City, d.Country FROM ACCOMMODATION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['min_stars'])) {
                    $query .= " AND a.StarRating >= :stars";
                    $params[':stars'] = (int)$_GET['min_stars'];
                }
                if (isset($_GET['destination_id'])) {
                    $query .= " AND a.DestinationID = :dest";
                    $params[':dest'] = (int)$_GET['destination_id'];
                }
                
                // Sorting
                $sort = $_GET['sort'] ?? '';
                if ($sort === 'price_asc') {
                    $query .= " ORDER BY a.PricePerNight ASC";
                } elseif ($sort === 'price_desc') {
                    $query .= " ORDER BY a.PricePerNight DESC";
                } elseif ($sort === 'rating_desc') {
                    $query .= " ORDER BY a.StarRating DESC";
                } else {
                    $query .= " ORDER BY a.StarRating DESC";
                }
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $items = $stmt->fetchAll();
                echo json_encode($items);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
