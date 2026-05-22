<?php
// api/routes/flights.php

function handleFlightsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM FLIGHT WHERE FlightID = :id");
                $stmt->execute([':id' => $id]);
                $flight = $stmt->fetch();
                
                if ($flight) {
                    echo json_encode($flight);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Flight not found."]);
                }
            } else {
                $query = "SELECT * FROM FLIGHT WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['departure'])) {
                    $query .= " AND DepartureCity LIKE :departure";
                    $params[':departure'] = '%' . $_GET['departure'] . '%';
                }
                if (isset($_GET['arrival'])) {
                    $query .= " AND ArrivalCity LIKE :arrival";
                    $params[':arrival'] = '%' . $_GET['arrival'] . '%';
                }
                
                // Sorting
                $sort = $_GET['sort'] ?? '';
                if ($sort === 'price_asc') {
                    $query .= " ORDER BY Price ASC";
                } elseif ($sort === 'price_desc') {
                    $query .= " ORDER BY Price DESC";
                } else {
                    $query .= " ORDER BY DepartureTime ASC";
                }
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $flights = $stmt->fetchAll();
                echo json_encode($flights);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
