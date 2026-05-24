<?php
// Importing Destination Data from OpenTripMap API or Kaggle CSV
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/env.php';

define('OTM_BASE', 'https://api.opentripmap.com/0.1');
define('CSV_PATH', __DIR__ . '/worldcities.csv');

// Convert ISO code to full name, e.g. ZA -> South Africa
function country(string $code): string {
    $name = locale_get_display_region(strtoupper($code), 'en');
    return $name ?: $code;
}

// Find local image for city or fallback to a random destination image
function getLocalImage(string $cityName): string {
    $filename = strtolower(str_replace(' ', '_', $cityName)) . '.jpg';
    $path = __DIR__ . '/../../uploads/destinations/' . $filename;
    if (file_exists($path)) {
        return BASE_URL . '/uploads/destinations/' . $filename;
    }
    // Fallback to random dest_X.jpg
    $rand = rand(1, 100);
    return BASE_URL . '/uploads/destinations/dest_' . $rand . '.jpg';
}

// Fetch city coordinates and description via OpenTripMap
function fetchCity(string $city): ?array {
    $key = env('OPENTRIPMAP_KEY');
    $params = http_build_query(['name' => $city, 'apikey' => $key]);
    $json = @file_get_contents(OTM_BASE . '/en/places/autosuggest?' . $params);
    if (!$json) return null;

    $data = json_decode($json, true);
    $feat = $data['features'][0] ?? null;
    if (!$feat) return null;

    $name  = $feat['properties']['name'];
    $lon   = $feat['geometry']['coordinates'][0];
    $lat   = $feat['geometry']['coordinates'][1];
    $xid   = $feat['properties']['xid'];
    $cc    = country($feat['properties']['country_code'] ?? '');

    $dJson = @file_get_contents(OTM_BASE . "/en/places/xid/$xid?" . http_build_query(['apikey' => $key]));
    $desc  = '';
    if ($dJson) {
        $detail = json_decode($dJson, true);
        $desc   = $detail['wikipedia_extracts']['text'] ?? '';
        $desc   = substr(strip_tags($desc), 0, 500);
    }
    $image = getLocalImage($name);
    return ['name' => $name, 'country' => $cc, 'lat' => $lat, 'lon' => $lon, 'desc' => $desc, 'image' => $image];
}

// Parse Kaggle worldcities.csv offline fallback
function readCsv(): array {
    if (!file_exists(CSV_PATH)) {
        exit("ERROR: " . CSV_PATH . " not found. Download from kaggle.com/max-mind/world-cities-database\n");
    }
    $results = [];
    $seen    = [];
    $handle  = fopen(CSV_PATH, 'r');
    fgetcsv($handle);
    while (($row = fgetcsv($handle)) !== false) {
        $city    = $row[0];
        $lat     = (float) $row[2];
        $lon     = (float) $row[3];
        $cc      = $row[4];
        $capital = $row[8] === 'primary';
        $pop     = (int) $row[9];
        if (!$capital && $pop < 500000) continue;
        $key = "$city|$cc";
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $results[] = [
            'name'    => $city,
            'country' => $cc,
            'lat'     => $lat,
            'lon'     => $lon,
            'desc'    => "Explore $city, $cc, a vibrant travel destination.",
            'image'   => getLocalImage($city),
        ];
    }
    fclose($handle);
    return $results;
}

// Upsert into DESTINATION table
function save(PDO $pdo, array $d): void {
    $stmt = $pdo->prepare('
        INSERT INTO DESTINATION (City, Country, Latitude, Longitude, Description, ImageURL)
        VALUES (:city, :country, :lat, :lon, :desc, :image)
        ON DUPLICATE KEY UPDATE Latitude=VALUES(Latitude), Longitude=VALUES(Longitude),
                                Description=VALUES(Description), ImageURL=VALUES(ImageURL)
    ');
    $stmt->execute([
        ':city'    => $d['name'],
        ':country' => $d['country'],
        ':lat'     => $d['lat'],
        ':lon'     => $d['lon'],
        ':desc'    => $d['desc'],
        ':image'   => $d['image'],
    ]);
}

// MAIN
$apiKey = env('OPENTRIPMAP_KEY');

if ($apiKey && $apiKey !== 'opentripmap_key') {
    echo "Fetching destinations from OpenTripMap...\n";
    $cityList = [
        'Cape Town', 'Johannesburg', 'Durban', 'Paris', 'Nice', 'Lyon',
        'Tokyo', 'Kyoto', 'Osaka', 'London', 'Edinburgh', 'Manchester',
        'New York', 'Los Angeles', 'Miami', 'Rome', 'Florence', 'Venice',
        'Madrid', 'Barcelona', 'Sydney', 'Melbourne', 'Berlin', 'Munich',
        'Toronto', 'Vancouver', 'Dubai', 'Bangkok', 'Singapore', 'Amsterdam',
    ];
    $count = 0;
    foreach ($cityList as $city) {
        $data = fetchCity($city);
        if ($data) {
            save($pdo, $data);
            echo "  $city added\n";
            $count++;
        } else {
            echo "  $city skipped\n";
        }
        usleep(200000);
    }
    echo "\n$count destinations inserted.\n";
} else {
    echo "No OPENTRIPMAP_KEY, falling back to Kaggle CSV...\n";
    $destinations = readCsv();
    foreach ($destinations as $d) {
        save($pdo, $d);
    }
    echo count($destinations) . " destinations inserted.\n";
}
