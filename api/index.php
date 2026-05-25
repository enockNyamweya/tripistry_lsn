<?php
// api/index.php
// Central API Router

require_once __DIR__ . '/../includes/Database.php'; // Reuse our PDO singleton

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Extract the endpoint from the URI
$uriParts = explode('/', trim($requestUri, '/'));

// Find the 'api' part in the URI to know where the actual route starts
$apiIndex = array_search('api', $uriParts);
if ($apiIndex === false || !isset($uriParts[$apiIndex + 1])) {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found."]);
    exit();
}

$nextIndex = $apiIndex + 1;
if (isset($uriParts[$nextIndex]) && $uriParts[$nextIndex] === 'index.php') {
    $nextIndex++;
}
$endpoint = isset($uriParts[$nextIndex]) ? $uriParts[$nextIndex] : '';
$id = isset($uriParts[$nextIndex + 1]) ? $uriParts[$nextIndex + 1] : null;
$subId = isset($uriParts[$nextIndex + 2]) ? $uriParts[$nextIndex + 2] : null;

/**
 * Reusable helper function to build a standardized paginated response envelope.
 */
function getPaginatedResponse($pdo, $countQuery, $selectQuery, $params, $page, $limit) {
    // 1. Calculate totals
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = (int)$stmt->fetchColumn();

    $totalPages = (int)ceil($totalRecords / $limit);
    $page = max(1, min($totalPages, $page));
    if ($totalPages === 0) {
        $page = 1;
    }
    $offset = ($page - 1) * $limit;

    // 2. Fetch data
    $paginatedQuery = $selectQuery . " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($paginatedQuery);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    return [
        "success" => true,
        "pagination" => [
            "total_records" => $totalRecords,
            "total_pages" => $totalPages,
            "current_page" => $page,
            "limit" => $limit,
            "next_page" => ($page < $totalPages) ? $page + 1 : null,
            "prev_page" => ($page > 1) ? $page - 1 : null
        ],
        "data" => $data
    ];
}

try {
    // Route the request
    switch ($endpoint) {
        case 'destinations':
            require_once __DIR__ . '/routes/destinations.php';
            handleDestinationsRequest($_SERVER['REQUEST_METHOD'], $id);
            break;
            
        case 'packages':
            require_once __DIR__ . '/routes/packages.php';
            handlePackagesRequest($_SERVER['REQUEST_METHOD'], $id);
            break;
            
        case 'flights':
            require_once __DIR__ . '/routes/flights.php';
            handleFlightsRequest($_SERVER['REQUEST_METHOD'], $id);
            break;
            
        case 'accommodations':
            require_once __DIR__ . '/routes/accommodations.php';
            handleAccommodationsRequest($_SERVER['REQUEST_METHOD'], $id);
            break;
            
        case 'restaurants':
            require_once __DIR__ . '/routes/restaurants.php';
            handleRestaurantsRequest($_SERVER['REQUEST_METHOD'], $id);
            break;
            
        case 'attractions':
            require_once __DIR__ . '/routes/attractions.php';
            handleAttractionsRequest($_SERVER['REQUEST_METHOD'], $id);
            break;

        case 'agency':
            require_once __DIR__ . '/routes/agency.php';
            handleAgencyRequest($_SERVER['REQUEST_METHOD'], $id, $subId);
            break;
    
        default:
            http_response_code(404);
            echo json_encode(["message" => "Endpoint '$endpoint' not recognized."]);
            break;
    }
} catch (PDOException $e) {
    // Catch database connection or query errors
    http_response_code(500);
    echo json_encode(["message" => "Database Error: " . $e->getMessage()]);
} catch (Exception $e) {
    // Catch any other generic exceptions
    http_response_code(500);
    echo json_encode(["message" => "Server Error: " . $e->getMessage()]);
}
