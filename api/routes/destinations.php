<?php
// api/routes/destinations.php

function handleDestinationsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM DESTINATION WHERE DestinationID = :id");
                $stmt->execute([':id' => (int)$id]);
                $destination = $stmt->fetch();
                
                if ($destination) {
                    echo json_encode($destination);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Destination not found."]);
                }
            } else {
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;

                $countQuery = "SELECT COUNT(1) FROM DESTINATION";
                $selectQuery = "SELECT * FROM DESTINATION ORDER BY Country, City";
                
                $response = getPaginatedResponse($pdo, $countQuery, $selectQuery, [], $page, $limit);
                echo json_encode($response);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
