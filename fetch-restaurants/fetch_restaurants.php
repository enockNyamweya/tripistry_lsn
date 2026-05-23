<?php
// Importing Restaurant Data from OpenTripMap API or Kaggle CSV
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

define('OTM_BASE', 'https://api.opentripmap.com/0.1');
define('CSV_PATH', __DIR__ . '/restaurants.csv');

// Fetch restaurants near a city via OpenTripMap radius search
function fetch(string $lat, string $lon): array {
    $key = env('OPENTRIPMAP_KEY');
    $params = http_build_query([
        'radius'  => 5000,
        'lon'     => $lon,
        'lat'     => $lat,
        'kinds'   => 'foods,restaurants',
        'format'  => 'json',
        'limit'   => 10,
        'apikey'  => $key,
    ]);
    $json = @file_get_contents(OTM_BASE . '/en/places/radius?' . $params);
    if (!$json) return [];
    $data = json_decode($json, true);
    return $data['features'] ?? [];
}

// Fetch restaurant detail for cuisine and rating via OpenTripMap
function detail(string $xid): array {
    $key = env('OPENTRIPMAP_KEY');
    $params = http_build_query(['apikey' => $key]);
    $json = @file_get_contents(OTM_BASE . "/en/places/xid/$xid?" . $params);
    if (!$json) return [];
    return json_decode($json, true);
}

// Parse Kaggle restaurants.csv offline fallback
function readCsv(): array {
    if (!file_exists(CSV_PATH)) {
        exit("ERROR: " . CSV_PATH . " not found. Download from kaggle.com\n");
    }
    $results = [];
    $handle  = fopen(CSV_PATH, 'r');
    $headers = fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        $results[] = [
            'name'    => $row[0] ?? 'Unknown',
            'cuisine' => $row[1] ?? 'Local',
            'price'   => $row[2] ?? '$$',
            'address' => $row[3] ?? '',
            'rating'  => (float)($row[4] ?? 0),
            'city'    => $row[5] ?? '',
            'country' => $row[6] ?? '',
        ];
    }
    fclose($handle);
    return $results;
}

// Find destination ID by city name
function destId(PDO $pdo, string $city): ?int {
    $stmt = $pdo->prepare("SELECT DestinationID FROM DESTINATION WHERE City = :city LIMIT 1");
    $stmt->execute([':city' => $city]);
    $row = $stmt->fetch();
    return $row ? (int) $row['DestinationID'] : null;
}

// Upsert into RESTAURANT table
function save(PDO $pdo, array $r, int $destId): void {
    $stmt = $pdo->prepare('
        INSERT INTO RESTAURANT (Name, CuisineType, PriceRange, Address, Rating, DestinationID)
        VALUES (:name, :cuisine, :price, :addr, :rating, :dest)
        ON DUPLICATE KEY UPDATE Rating=VALUES(Rating), PriceRange=VALUES(PriceRange)
    ');
    $stmt->execute([
        ':name'    => $r['name'],
        ':cuisine' => $r['cuisine'] ?? 'Local',
        ':price'   => $r['price'] ?? '$$',
        ':addr'    => $r['address'] ?? '',
        ':rating'  => $r['rating'] ?? 0,
        ':dest'    => $destId,
    ]);
}

// MAIN
$apiKey = env('OPENTRIPMAP_KEY');

if ($apiKey && $apiKey !== 'opentripmap_key') {
    echo "Fetching restaurants from OpenTripMap...\n";
    $cities = $pdo->query("SELECT DestinationID, City, Latitude, Longitude FROM DESTINATION")->fetchAll();
    if (empty($cities)) {
        exit("ERROR: DESTINATION table is empty. Run fetch_destinations.php first.\n");
    }
    $count = 0;
    foreach ($cities as $c) {
        echo "  {$c['City']} ... ";
        $places = fetch($c['Latitude'], $c['Longitude']);
        $added = 0;
        foreach ($places as $p) {
            $props = $p['properties'];
            $name  = $props['name'] ?? 'Local Eatery';
            $xid   = $props['xid'] ?? '';
            $d     = $xid ? detail($xid) : [];
            $kinds = $d['kinds'] ?? $props['kinds'] ?? '';
            $cuisine = strpos($kinds, 'restaurant') !== false ? 'International' : 'Local Cuisine';
            save($pdo, [
                'name'    => $name,
                'cuisine' => $cuisine,
                'price'   => '$$',
                'address' => $props['address'] ?? $c['City'],
                'rating'  => isset($d['rate']) ? (float)$d['rate'] : 0,
            ], $c['DestinationID']);
            $added++;
        }
        echo "$added restaurants\n";
        $count += $added;
        usleep(200000);
    }
    echo "\n$count restaurants inserted.\n";
} else {
    echo "No OPENTRIPMAP_KEY, falling back to Kaggle CSV...\n";
    $restaurants = readCsv();
    $count = 0;
    foreach ($restaurants as $r) {
        $id = destId($pdo, $r['city']);
        if ($id) { save($pdo, $r, $id); $count++; }
    }
    echo "$count restaurants inserted.\n";
}
