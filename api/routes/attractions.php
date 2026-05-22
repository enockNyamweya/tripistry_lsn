<?php
// api/routes/attractions.php

function handleAttractionsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM ATTRACTION WHERE AttractionID = :id");
                $stmt->execute([':id' => $id]);
                $item = $stmt->fetch();
                
                if ($item) {
                    echo json_encode($item);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Attraction not found."]);
                }
            } else {
                $query = "SELECT a.*, d.City, d.Country FROM ATTRACTION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['type'])) {
                    $query .= " AND a.Type LIKE :type";
                    $params[':type'] = '%' . $_GET['type'] . '%';
                }
                if (isset($_GET['destination_id'])) {
                    $query .= " AND a.DestinationID = :dest";
                    $params[':dest'] = (int)$_GET['destination_id'];
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
