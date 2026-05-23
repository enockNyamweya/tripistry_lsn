<?php
// Importing Destination Data from OpenTripMap API or Kaggle CSV
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

define('OTM_BASE', 'https://api.opentripmap.com/0.1');
define('CSV_PATH', __DIR__ . '/worldcities.csv');

// Convert ISO code to full name, e.g. ZA -> South Africa
function country(string $code): string {
    $name = locale_get_display_region(strtoupper($code), 'en');
    return $name ?: $code;
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
    $image = '';
    if ($dJson) {
        $detail = json_decode($dJson, true);
        $desc   = $detail['wikipedia_extracts']['text'] ?? '';
        $desc   = substr(strip_tags($desc), 0, 500);
        $image  = $detail['image'] ?? '';
    }
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
            'image'   => '',
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
                                Description=VALUES(Description)
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
$hasKey = $apiKey && $apiKey !== 'opentripmap_key';

echo "Reading city list from CSV...\n";
$destinations = readCsv();
echo count($destinations) . " cities found in CSV.\n";

if ($hasKey) {
    echo "Enriching with OpenTripMap...\n";
    $enriched = 0;
    foreach ($destinations as $i => $d) {
        $otm = fetchCity($d['name']);
        if ($otm) {
            $destinations[$i] = $otm;
            echo "  {$d['name']} enriched\n";
            $enriched++;
        } else {
            echo "  {$d['name']} kept from CSV\n";
        }
        usleep(200000);
    }
    echo "$enriched cities enriched via API.\n";
} else {
    echo "No OPENTRIPMAP_KEY, using CSV data only.\n";
}

$count = 0;
foreach ($destinations as $d) {
    save($pdo, $d);
    $count++;
}
echo "$count destinations inserted.\n";
