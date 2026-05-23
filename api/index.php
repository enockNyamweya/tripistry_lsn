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

/**
 * We wrap the entire router in a try/catch block so that if a database query crashes 
 * inside one of the route files, the API catches it here and returns a clean 500 JSON error.
 * Without this, the server would spit out ugly HTML error logs that break frontend apps.
 */
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
