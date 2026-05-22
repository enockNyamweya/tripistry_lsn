<?php
// api/routes/restaurants.php

function handleRestaurantsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM RESTAURANT WHERE RestaurantID = :id");
                $stmt->execute([':id' => $id]);
                $item = $stmt->fetch();
                
                if ($item) {
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Restaurant not found."]);
                }
            } else {
                $query = "SELECT r.*, d.City, d.Country FROM RESTAURANT r LEFT JOIN DESTINATION d ON r.DestinationID = d.DestinationID WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['cuisine'])) {
                    $query .= " AND r.CuisineType LIKE :cuisine";
                    $params[':cuisine'] = '%' . $_GET['cuisine'] . '%';
                }
                if (isset($_GET['destination_id'])) {
                    $query .= " AND r.DestinationID = :dest";
                    $params[':dest'] = (int)$_GET['destination_id'];
                }
                
                // Sorting
                $sort = $_GET['sort'] ?? '';
                if ($sort === 'rating_desc') {
                    $query .= " ORDER BY r.Rating DESC";
                } elseif ($sort === 'rating_asc') {
                    $query .= " ORDER BY r.Rating ASC";
                } else {
                    $query .= " ORDER BY r.Rating DESC";
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
