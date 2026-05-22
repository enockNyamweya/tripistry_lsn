<?php
// api/routes/destinations.php

function handleDestinationsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM DESTINATION WHERE DestinationID = :id");
                $stmt->execute([':id' => $id]);
                $destination = $stmt->fetch();
                
                if ($destination) {
                    echo json_encode($destination);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Destination not found."]);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM DESTINATION");
                $destinations = $stmt->fetchAll();
                echo json_encode($destinations);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
