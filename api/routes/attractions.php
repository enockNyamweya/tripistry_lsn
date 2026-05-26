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
                $whereClause = " FROM ATTRACTION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['type'])) {
                    $whereClause .= " AND a.Type LIKE :type";
                    $params[':type'] = '%' . $_GET['type'] . '%';
                }
                if (isset($_GET['destination_id'])) {
                    $whereClause .= " AND a.DestinationID = :dest";
                    $params[':dest'] = (int)$_GET['destination_id'];
                }
                
                $countQuery = "SELECT COUNT(1)" . $whereClause;
                $selectQuery = "SELECT a.*, d.City, d.Country" . $whereClause . " ORDER BY a.Name ASC";
                
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
