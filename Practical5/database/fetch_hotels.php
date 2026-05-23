<?php
// Importing Hotel Data from RapidAPI Booking.com v18
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

define('API_HOST', 'booking-com18.p.rapidapi.com');

// Fetch hotels for one city via RapidAPI search
function fetch(string $city): array {
    $params = http_build_query([
        'dest_name'      => $city,
        'search_type'    => 'city',
        'arrival_date'   => date('Y-m-d', strtotime('+7 days')),
        'departure_date' => date('Y-m-d', strtotime('+14 days')),
        'adults'         => 1,
        'room_qty'       => 1,
        'currency_code'  => 'USD',
    ]);
    $ch = curl_init('https://' . API_HOST . '/v1/hotels/search?' . $params);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-RapidAPI-Key: ' . env('RAPIDAPI_KEY'),
            'X-RapidAPI-Host: ' . API_HOST,
        ],
        CURLOPT_TIMEOUT => 25,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true)['result'] ?? [];
}

// Fetch hotel detail for amenities
function detail(int $hotelId): array {
    $params = http_build_query(['hotel_id' => $hotelId]);
    $ch = curl_init('https://' . API_HOST . '/v1/hotels/detail?' . $params);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-RapidAPI-Key: ' . env('RAPIDAPI_KEY'),
            'X-RapidAPI-Host: ' . API_HOST,
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// Upsert one hotel row
function save(PDO $pdo, array $h, int $destId): int {
    $name    = $h['hotel_name'] ?? 'Unnamed';
    $stars   = (int)($h['stars'] ?? 3);
    $price   = (float)($h['price_breakdown']['gross_price'] ?? 0);
    $address = $h['address'] ?? '';
    $type    = $stars >= 5 ? 'Luxury Hotel' : ($stars >= 3 ? 'Hotel' : 'Budget Hotel');

    $stmt = $pdo->prepare('
        INSERT INTO ACCOMMODATION (Name, Type, StarRating, PricePerNight, Address, DestinationID)
        VALUES (:name, :type, :stars, :price, :addr, :dest)
        ON DUPLICATE KEY UPDATE StarRating=VALUES(StarRating), PricePerNight=VALUES(PricePerNight)
    ');
    $stmt->execute([
        ':name'  => $name,
        ':type'  => $type,
        ':stars' => $stars,
        ':price' => $price,
        ':addr'  => $address ?: ($h['city'] ?? ''),
        ':dest'  => $destId,
    ]);
    return (int) $pdo->lastInsertId();
}

// Insert amenities from hotel detail
function amenities(PDO $pdo, int $aid, int $hotelId): void {
    $d = detail($hotelId);
    $facilities = $d['facilities'] ?? [];
    $stmt = $pdo->prepare('
        INSERT IGNORE INTO ACCOMMODATION_AMENITIES (AccommodationID, Amenity)
        VALUES (?, ?)
    ');
    foreach ($facilities as $f) {
        $name = is_string($f) ? $f : ($f['name'] ?? '');
        if ($name) $stmt->execute([$aid, $name]);
    }
}

// MAIN
if (!env('RAPIDAPI_KEY') || env('RAPIDAPI_KEY') === 'rapidapi_key') {
    exit("ERROR: Set RAPIDAPI_KEY in .env first.\n");
}

$cities = $pdo->query("SELECT DestinationID, City, Country FROM DESTINATION")->fetchAll();
if (empty($cities)) {
    exit("ERROR: DESTINATION table is empty. Run fetch_destinations.php first.\n");
}

$total = 0;
foreach ($cities as $row) {
    echo $row['City'] . ', ' . $row['Country'] . ' ... ';
    $hotels = fetch($row['City']);
    if (empty($hotels)) { echo "0 hotels\n"; continue; }

    $count = 0;
    foreach (array_slice($hotels, 0, 5) as $h) {
        $aid = save($pdo, $h, $row['DestinationID']);
        if ($aid) { amenities($pdo, $aid, (int) $h['hotel_id']); $count++; }
    }
    echo "$count hotels\n";
    $total += $count;
    sleep(1);
}

echo "\n$total hotels inserted.\n";
