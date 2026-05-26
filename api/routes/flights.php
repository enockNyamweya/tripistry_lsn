<?php
// api/routes/flights.php

function handleFlightsRequest($method, $id) {
    $pdo = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM FLIGHT WHERE FlightID = :id");
                $stmt->execute([':id' => (int)$id]);
                $flight = $stmt->fetch();
                
                if ($flight) {
                    echo json_encode($flight);
                } else {
                    http_response_code(404);
                    echo json_encode(["message" => "Flight not found."]);
                }
            } else {
                $whereClause = " FROM FLIGHT WHERE 1=1";
                $params = [];
                
                // Filtering
                if (isset($_GET['departure'])) {
                    $whereClause .= " AND DepartureCity LIKE :departure";
                    $params[':departure'] = '%' . $_GET['departure'] . '%';
                }
                if (isset($_GET['arrival'])) {
                    $whereClause .= " AND ArrivalCity LIKE :arrival";
                    $params[':arrival'] = '%' . $_GET['arrival'] . '%';
                }
                
                $countQuery = "SELECT COUNT(1)" . $whereClause;
                $selectQuery = "SELECT *" . $whereClause;

                // Sorting
                $sort = $_GET['sort'] ?? '';
                if ($sort === 'price_asc') {
                    $selectQuery .= " ORDER BY Price ASC";
                } elseif ($sort === 'price_desc') {
                    $selectQuery .= " ORDER BY Price DESC";
                } else {
                    $selectQuery .= " ORDER BY DepartureTime ASC";
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
