<?php
// api/routes/restaurants.php

function handleRestaurantsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM RESTAURANT WHERE RestaurantID = :id");
                $stmt->execute([':id' => (int)$id]);
                $item = $stmt->fetch();
                
                if ($item) {
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Restaurant not found."]);
                }
            } else {
                $whereClause = " FROM RESTAURANT r LEFT JOIN DESTINATION d ON r.DestinationID = d.DestinationID WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['cuisine'])) {
                    $whereClause .= " AND r.CuisineType LIKE :cuisine";
                    $params[':cuisine'] = '%' . $_GET['cuisine'] . '%';
                }
                if (isset($_GET['destination_id'])) {
                    $whereClause .= " AND r.DestinationID = :dest";
                    $params[':dest'] = (int)$_GET['destination_id'];
                }
                
                $countQuery = "SELECT COUNT(1)" . $whereClause;
                $selectQuery = "SELECT r.*, d.City, d.Country" . $whereClause;

                // Sorting
                $sort = $_GET['sort'] ?? '';
                if ($sort === 'rating_asc') {
                    $selectQuery .= " ORDER BY r.Rating ASC";
                } else {
                    $selectQuery .= " ORDER BY r.Rating DESC";
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
