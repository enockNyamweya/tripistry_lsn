<?php
// Importing Attraction Data from OpenTripMap API or Kaggle CSV
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

define('OTM_BASE', 'https://api.opentripmap.com/0.1');
define('CSV_PATH', __DIR__ . '/attractions.csv');

// Fetch attractions near a city via OpenTripMap radius search
function fetch(string $lat, string $lon): array {
    $key = env('OPENTRIPMAP_KEY');
    $params = http_build_query([
        'radius'  => 5000,
        'lon'     => $lon,
        'lat'     => $lat,
        'kinds'   => 'interesting_places',
        'format'  => 'geojson',
        'limit'   => 10,
        'apikey'  => $key,
    ]);
    
    $json = @file_get_contents(OTM_BASE . '/en/places/radius?' . $params);
    if (!$json) return [];
    
    $data = json_decode($json, true);
    return $data['features'] ?? [];
}

// Fetch attraction detail for description and kinds via OpenTripMap
function detail(string $xid): array {
    $key = env('OPENTRIPMAP_KEY');
    $params = http_build_query(['apikey' => $key]);
    
    $json = @file_get_contents(OTM_BASE . "/en/places/xid/$xid?" . $params);
    if (!$json) return [];
    
    return json_decode($json, true);
}

// Parse Kaggle attractions.csv offline fallback
function readCsv(): array {
    if (!file_exists(CSV_PATH)) {
        exit("ERROR: " . CSV_PATH . " not found. Download from kaggle.com\n");
    }
    
    $results = [];
    $handle  = fopen(CSV_PATH, 'r');
    $headers = fgetcsv($handle);
    
    while (($row = fgetcsv($handle)) !== false) {
        $results[] = [
            'name'  => $row[0] ?? 'Unknown Attraction',
            'type'  => $row[1] ?? 'Landmark',
            'fee'   => (float)($row[2] ?? 0),
            'desc'  => $row[3] ?? 'A wonderful attraction.',
            'hours' => $row[4] ?? '09:00 - 17:00',
            'city'  => $row[5] ?? '',
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

// Upsert into ATTRACTION table
function save(PDO $pdo, array $a, int $destId): void {
    $stmt = $pdo->prepare('
        INSERT INTO ATTRACTION (Name, Type, EntryFee, Description, OpeningHours, DestinationID)
        VALUES (:name, :type, :fee, :desc, :hours, :dest)
        ON DUPLICATE KEY UPDATE Type=VALUES(Type), EntryFee=VALUES(EntryFee), Description=VALUES(Description), OpeningHours=VALUES(OpeningHours)
    ');
    
    $stmt->execute([
        ':name'  => $a['name'],
        ':type'  => $a['type'] ?? 'Landmark',
        ':fee'   => $a['fee'] ?? 0.00,
        ':desc'  => $a['desc'] ?? '',
        ':hours' => $a['hours'] ?? '09:00 - 17:00',
        ':dest'  => $destId,
    ]);
}

// MAIN
$apiKey = env('OPENTRIPMAP_KEY');

if ($apiKey && $apiKey !== 'opentripmap_key' && stripos($apiKey, 'your_') === false) {
    echo "Fetching attractions from OpenTripMap...\n";
    $cities = $pdo->query("SELECT DestinationID, City, Latitude, Longitude FROM DESTINATION")->fetchAll();
    
    if (empty($cities)) {
        exit("ERROR: DESTINATION table is empty. Run fetch_destinations.php first.\n");
    }

    // Demo mode: limit to 3 cities for fast execution during presentations
    if (in_array('--demo', $argv)) {
        echo "Running in DEMO mode (limited to 3 cities)...\n";
        $cities = array_slice($cities, 0, 3);
    }
    
    $count = 0;
    foreach ($cities as $c) {
        echo "  {$c['City']} ... ";
        $places = fetch($c['Latitude'], $c['Longitude']);
        $added = 0;
        
        foreach ($places as $p) {
            $props = $p['properties'];
            $name  = $props['name'] ?? 'Local Attraction';
            if (empty(trim($name))) continue;
            
            $xid   = $props['xid'] ?? '';
            $d     = $xid ? detail($xid) : [];
            
            $kinds = $d['kinds'] ?? $props['kinds'] ?? '';
            $type = strpos($kinds, 'museums') !== false ? 'Museum' : 
                    (strpos($kinds, 'nature') !== false ? 'Nature' : 'Landmark');
            
            $desc = $d['wikipedia_extracts']['text'] ?? "A notable attraction in {$c['City']}.";
            $desc = preg_replace('/[^\x20-\x7E\t\r\n]/', '', strip_tags($desc));
            $fee  = ($added === 0) ? 0.00 : rand(5, 50); // Just some variation
            
            save($pdo, [
                'name'  => trim($name),
                'type'  => $type,
                'fee'   => $fee,
                'desc'  => substr($desc, 0, 500),
                'hours' => '09:00 - 17:00',
            ], $c['DestinationID']);
            $added++;
        }
        
        echo "$added attractions\n";
        $count += $added;
        usleep(200000);
    }
    echo "\n$count attractions inserted.\n";
} else {
    echo "No OPENTRIPMAP_KEY, falling back to Kaggle CSV...\n";
    $attractions = readCsv();
    $count = 0;
    foreach ($attractions as $a) {
        $id = destId($pdo, $a['city']);
        if ($id) { 
            save($pdo, $a, $id); 
            $count++; 
        }
    }
    echo "$count attractions inserted.\n";
}
