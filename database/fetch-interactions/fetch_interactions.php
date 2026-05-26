<?php
// Master Seeder: Reads seed_data.csv and populates the entire Tripistry database
require_once __DIR__ . '/../../config/database.php';

define('CSV_PATH', __DIR__ . '/seed_data.csv');

if (!file_exists(CSV_PATH)) {
    exit("ERROR: seed_data.csv not found in " . __DIR__ . "\n");
}

echo "Starting Tripistry CSV Seed Importer...\n\n";

// --- Parse CSV into sections ---
$handle = fopen(CSV_PATH, 'r');
fgetcsv($handle); // skip header row

$sections = [
    'destination'   => [],
    'agency'        => [],
    'traveller'     => [],
    'accommodation' => [],
    'restaurant'    => [],
    'attraction'    => [],
];

while (($row = fgetcsv($handle)) !== false) {
    $type = trim($row[0] ?? '');
    if (isset($sections[$type])) {
        $sections[$type][] = $row;
    }
}
fclose($handle);

// Helper: trim row values
function r(array $row, int $i): string {
    return trim($row[$i] ?? '');
}

$pdo->beginTransaction();
try {

    // 1. DESTINATIONS
    echo "[1/7] Importing Destinations...\n";
    $count = 0;
    $stmtDest = $pdo->prepare('
        INSERT INTO DESTINATION (City, Country, Latitude, Longitude, Description, ImageURL)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE Latitude=VALUES(Latitude), Longitude=VALUES(Longitude),
                                Description=VALUES(Description), ImageURL=VALUES(ImageURL)
    ');
    foreach ($sections['destination'] as $row) {
        $imgUrl = r($row, 6);
        if ($imgUrl && str_starts_with($imgUrl, '/uploads/')) {
            $imgUrl = '..' . $imgUrl;
        }
        $stmtDest->execute([r($row,1), r($row,2), r($row,3), r($row,4), r($row,5), $imgUrl]);
        $count++;
    }
    echo "  $count destinations imported.\n\n";

    // Load destination name -> ID map
    $destMap = [];
    foreach ($pdo->query("SELECT DestinationID, City FROM DESTINATION")->fetchAll() as $d) {
        $destMap[$d['City']] = (int)$d['DestinationID'];
    }

    // 2. AGENCIES
    echo "[2/7] Importing Travel Agencies...\n";
    $count = 0;
    $agencyIds = [];
    $passwordCache = [];
    $stmtUser = $pdo->prepare("INSERT IGNORE INTO USER (Email, Password, UserType) VALUES (?, ?, 'Agency')");
    $stmtUserId = $pdo->prepare("SELECT UserID FROM USER WHERE Email = ?");
    $stmtAgency = $pdo->prepare("INSERT IGNORE INTO TRAVEL_AGENCY (UserID, AgencyName, VerificationStatus, CommissionRate) VALUES (?, ?, ?, ?)");
    foreach ($sections['agency'] as $row) {
        $email  = r($row, 1);
        $plainPass = 'password';
        if (!isset($passwordCache[$plainPass])) {
            $passwordCache[$plainPass] = password_hash($plainPass, PASSWORD_DEFAULT);
        }
        $pass   = $passwordCache[$plainPass];
        $name   = r($row, 3);
        $status = r($row, 4);
        $rate   = r($row, 5);

        $stmtUser->execute([$email, $pass]);
        $stmtUserId->execute([$email]);
        $userId = (int)$stmtUserId->fetchColumn();
        $agencyIds[] = $userId;

        $stmtAgency->execute([$userId, $name, $status, $rate]);
        $count++;
    }
    echo "  $count agencies imported.\n\n";

    // 3. TRAVELLERS
    echo "[3/7] Importing Travellers...\n";
    $count = 0;
    $travellerIds = [];
    $stmtUserTrav = $pdo->prepare("INSERT IGNORE INTO USER (Email, Password, UserType) VALUES (?, ?, 'Traveller')");
    $stmtUserIdTrav = $pdo->prepare("SELECT UserID FROM USER WHERE Email = ?");
    $stmtTraveller = $pdo->prepare("INSERT IGNORE INTO TRAVELLER (UserID, FirstName, LastName) VALUES (?, ?, ?)");
    foreach ($sections['traveller'] as $row) {
        $email  = r($row, 1);
        $plainPass = 'password';
        if (!isset($passwordCache[$plainPass])) {
            $passwordCache[$plainPass] = password_hash($plainPass, PASSWORD_DEFAULT);
        }
        $pass   = $passwordCache[$plainPass];
        $fName  = r($row, 3);
        $lName  = r($row, 4);

        $stmtUserTrav->execute([$email, $pass]);
        $stmtUserIdTrav->execute([$email]);
        $userId = (int)$stmtUserIdTrav->fetchColumn();
        $travellerIds[] = $userId;

        $stmtTraveller->execute([$userId, $fName, $lName]);
        $count++;
    }
    echo "  $count travellers imported.\n\n";

    // 4. ACCOMMODATIONS
    echo "[4/7] Importing Accommodations...\n";
    $count = 0;
    $stmtAccom = $pdo->prepare('
        INSERT IGNORE INTO ACCOMMODATION (Name, Type, StarRating, PricePerNight, Address, DestinationID)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    foreach ($sections['accommodation'] as $row) {
        $city = r($row, 6);
        $destId = $destMap[$city] ?? null;
        if (!$destId) { echo "  WARNING: No destination found for city '$city'\n"; continue; }

        $stmtAccom->execute([r($row,1), r($row,2), r($row,3), r($row,4), r($row,5), $destId]);
        $count++;
    }
    echo "  $count accommodations imported.\n\n";

    // 5. RESTAURANTS
    echo "[5/7] Importing Restaurants...\n";
    $count = 0;
    $stmtRest = $pdo->prepare('
        INSERT IGNORE INTO RESTAURANT (Name, CuisineType, PriceRange, Address, Rating, DestinationID)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    foreach ($sections['restaurant'] as $row) {
        $city = r($row, 6);
        $destId = $destMap[$city] ?? null;
        if (!$destId) { echo "  WARNING: No destination found for city '$city'\n"; continue; }

        $stmtRest->execute([r($row,1), r($row,2), r($row,3), r($row,4), r($row,5), $destId]);
        $count++;
    }
    echo "  $count restaurants imported.\n\n";

    // 6. ATTRACTIONS
    echo "[6/7] Importing Attractions...\n";
    $count = 0;
    $stmtAttr = $pdo->prepare('
        INSERT IGNORE INTO ATTRACTION (Name, Type, EntryFee, Description, OpeningHours, DestinationID)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    foreach ($sections['attraction'] as $row) {
        $city = r($row, 6);
        $destId = $destMap[$city] ?? null;
        if (!$destId) { echo "  WARNING: No destination found for city '$city'\n"; continue; }

        $stmtAttr->execute([r($row,1), r($row,2), r($row,3), r($row,4), r($row,5), $destId]);
        $count++;
    }
    echo "  $count attractions imported.\n\n";

    // --- Seed Flights ---
    require_once __DIR__ . '/../fetch-flights/fetch_flights.php';

    // 7. GENERATE PACKAGES, BOOKINGS, REVIEWS
    echo "[7/7] Generating Packages, Bookings & Reviews...\n";

    $destList    = $pdo->query("SELECT DestinationID, City FROM DESTINATION")->fetchAll();
    $accomList   = $pdo->query("SELECT AccommodationID, DestinationID FROM ACCOMMODATION")->fetchAll();
    $restList    = $pdo->query("SELECT RestaurantID, DestinationID FROM RESTAURANT")->fetchAll();
    $attrList    = $pdo->query("SELECT AttractionID, DestinationID FROM ATTRACTION")->fetchAll();
    $flightList  = $pdo->query("SELECT FlightID, ArrivalCity FROM FLIGHT")->fetchAll();

    // Index by DestinationID for fast lookups
    $accomByDest = $restByDest = $attrByDest = [];
    foreach ($accomList as $a) $accomByDest[$a['DestinationID']][] = $a['AccommodationID'];
    foreach ($restList as $r)  $restByDest[$r['DestinationID']][]  = $r['RestaurantID'];
    foreach ($attrList as $a)  $attrByDest[$a['DestinationID']][]  = $a['AttractionID'];

    $flightsByDest = [];
    foreach ($flightList as $f) {
        $flightsByDest[$f['ArrivalCity']][] = (int)$f['FlightID'];
    }
    $allFlightIds = array_column($flightList, 'FlightID');

    $adjectives = ['Luxury', 'Ultimate', 'Budget', 'Express', 'Romantic', 'Adventurous', 'Exclusive', 'Classic', 'Grand', 'Serene'];
    $types      = ['Escape', 'Getaway', 'Tour', 'Experience', 'Journey', 'Adventure', 'Retreat', 'Expedition'];
    $comments   = [
        "Absolutely amazing experience! The agency handled everything perfectly.",
        "Loved every second of it. Highly recommend to everyone.",
        "Great trip overall. The hotel was stunning and the food incredible.",
        "One of the best vacations of my life. Will definitely book again!",
        "Incredible value for money. The guided tours were exceptional.",
        "Fantastic destination. The locals were so friendly and welcoming.",
        "Everything went smoothly. Great organisation from start to finish.",
        "Beautiful scenery and amazing restaurants. 5 stars from me!",
        "The package exceeded all my expectations. Truly unforgettable.",
        "Good experience but the flights could have been better planned.",
    ];

    $pkgCount = $bookCount = $reviewCount = 0;

    // Prepare package linking statements outside the loops
    $stmtPackage    = $pdo->prepare('INSERT INTO TRAVEL_PACKAGE (Title, Description, Price, DurationDays, IsGroupTrip, ImageURL, AgencyID) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmtHasDest    = $pdo->prepare('INSERT IGNORE INTO HAS_DESTINATION (PackageID, DestinationID) VALUES (?, ?)');
    $stmtIncFlight  = $pdo->prepare('INSERT IGNORE INTO INCLUDES_FLIGHT (PackageID, FlightID) VALUES (?, ?)');
    $stmtIncAccom   = $pdo->prepare('INSERT IGNORE INTO INCLUDES_ACCOM (PackageID, AccommodationID) VALUES (?, ?)');
    $stmtIncRest    = $pdo->prepare('INSERT IGNORE INTO INCLUDES_RESTAURANT (PackageID, RestaurantID) VALUES (?, ?)');
    $stmtIncAttr    = $pdo->prepare('INSERT IGNORE INTO INCLUDES_ATTRACTION (PackageID, AttractionID) VALUES (?, ?)');

    foreach ($agencyIds as $agencyId) {
        // Each agency gets 100 packages across different destinations
        $usedDests = [];
        for ($i = 0; $i < 100; $i++) {
            // Pick a destination not already used by this agency
            $dest = $destList[array_rand($destList)];
            $dId  = (int)$dest['DestinationID'];

            $adj      = $adjectives[array_rand($adjectives)];
            $type     = $types[array_rand($types)];
            $title    = "$adj {$dest['City']} $type";
            $desc     = "Experience the best of {$dest['City']} with this carefully curated $adj $type. An adventure you will never forget.";
            $duration = rand(3, 14);
            $price    = rand(4000, 50000);
            $isGroup  = (rand(1, 10) > 7) ? 1 : 0;
            $imgNum   = rand(1, 200);
            $imageURL = '../uploads/packages/pkg_' . $imgNum . '.jpg';

            $stmtPackage->execute([$title, $desc, $price, $duration, $isGroup, $imageURL, $agencyId]);
            $pkgId = (int)$pdo->lastInsertId();
            $pkgCount++;

            // Link destination
            $stmtHasDest->execute([$pkgId, $dId]);

            // Link flights
            $fIds = $flightsByDest[$dest['City']] ?? [];
            if (empty($fIds) && !empty($allFlightIds)) {
                $fIds = $allFlightIds;
            }
            if (!empty($fIds)) {
                shuffle($fIds);
                foreach (array_slice($fIds, 0, min(rand(1, 2), count($fIds))) as $id) {
                    $stmtIncFlight->execute([$pkgId, $id]);
                }
            }

            // Link accommodations
            if (!empty($accomByDest[$dId])) {
                $ids = $accomByDest[$dId]; shuffle($ids);
                foreach (array_slice($ids, 0, min(rand(1,2), count($ids))) as $id)
                    $stmtIncAccom->execute([$pkgId, $id]);
            }

            // Link restaurants
            if (!empty($restByDest[$dId])) {
                $ids = $restByDest[$dId]; shuffle($ids);
                foreach (array_slice($ids, 0, min(rand(2,4), count($ids))) as $id)
                    $stmtIncRest->execute([$pkgId, $id]);
            }

            // Link attractions
            if (!empty($attrByDest[$dId])) {
                $ids = $attrByDest[$dId]; shuffle($ids);
                foreach (array_slice($ids, 0, min(rand(2,5), count($ids))) as $id)
                    $stmtIncAttr->execute([$pkgId, $id]);
            }
        }
    }
    echo "  $pkgCount packages generated.\n";

    // Load all package IDs + prices for booking/review generation
    $allPkgs = $pdo->query("SELECT PackageID, Price FROM TRAVEL_PACKAGE")->fetchAll();

    // Prepare booking and review statements outside the loop
    $stmtBooking = $pdo->prepare('INSERT INTO BOOKING (UserID, PackageID, BookingDate, TotalCost, Status) VALUES (?, ?, ?, ?, ?)');
    $stmtReview  = $pdo->prepare('INSERT INTO REVIEW (UserID, PackageID, BookingID, Comment, RatingScore, DatePosted) VALUES (?, ?, ?, ?, ?, ?)');

    // Generate 40000 bookings + reviews
    for ($i = 0; $i < 40000; $i++) {
        $userId = $travellerIds[array_rand($travellerIds)];
        $pkg    = $allPkgs[array_rand($allPkgs)];
        $numTrav = rand(1, 4);
        $total  = $pkg['Price'] * $numTrav;
        $statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
        $status = $statuses[array_rand($statuses)];
        $daysAgo = rand(1, 365);
        $bookDate = date('Y-m-d H:i:s', strtotime("-$daysAgo days"));

        $stmtBooking->execute([$userId, $pkg['PackageID'], $bookDate, $total, $status]);
        $bookId = (int)$pdo->lastInsertId();
        $bookCount++;

        // Review with ~70% probability
        if ($status === 'Completed' || rand(1, 10) > 3) {
            $rating = rand(3, 5);
            if (rand(1, 15) == 1) $rating = rand(1, 2); // Occasional bad review
            $comment = $comments[array_rand($comments)];
            $reviewDate = date('Y-m-d H:i:s', strtotime("$bookDate +" . rand(5,20) . " days"));
            $stmtReview->execute([$userId, $pkg['PackageID'], $bookId, $comment, $rating, $reviewDate]);
            $reviewCount++;
        }
    }
    echo "  $bookCount bookings and $reviewCount reviews generated.\n\n";

    // 8. GENERATE PLACEHOLDERS FOR REMAINING 6 TABLES
    echo "[8/8] Generating Placeholders for remaining tables...\n";
    
    // GROUP_TRIP
    $stmtPkg = $pdo->query("SELECT PackageID, AgencyID, Title FROM TRAVEL_PACKAGE LIMIT 10");
    $packages = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);
    $insertGroupTrip = $pdo->prepare("INSERT INTO GROUP_TRIP (TripName, MaxCapacity, AgencyID, PackageID) VALUES (?, ?, ?, ?)");
    foreach ($packages as $pkg) {
        $insertGroupTrip->execute([$pkg['Title'] . ' Group Tour', rand(10, 30), $pkg['AgencyID'], $pkg['PackageID']]);
    }

    // ENROLS
    $groupTrips = $pdo->query("SELECT GroupTripID FROM GROUP_TRIP LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    $travs = $pdo->query("SELECT UserID FROM TRAVELLER LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
    $insertEnrols = $pdo->prepare("INSERT IGNORE INTO ENROLS (UserID, GroupTripID) VALUES (?, ?)");
    for ($i = 0; $i < 20; $i++) {
        $insertEnrols->execute([$travs[$i], $groupTrips[array_rand($groupTrips)]]);
    }

    // PAYMENT
    $bookingsToPay = $pdo->query("SELECT BookingID, TotalCost FROM BOOKING LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    $insertPayment = $pdo->prepare("INSERT IGNORE INTO PAYMENT (BookingID, PaymentSeq, Amount, Status) VALUES (?, 1, ?, 'Completed')");
    foreach ($bookingsToPay as $b) {
        $insertPayment->execute([$b['BookingID'], $b['TotalCost']]);
    }

    // TRAVELLER_PREFERENCE & TAGS
    $insertPref = $pdo->prepare("INSERT INTO TRAVELLER_PREFERENCE (BudgetRange, TravelPace) VALUES (?, ?)");
    $updateTraveller = $pdo->prepare("UPDATE TRAVELLER SET PreferenceID = ? WHERE UserID = ?");
    $insertTag = $pdo->prepare("INSERT IGNORE INTO TRAVELLER_PREFERENCE_TAGS (PreferenceID, PreferenceTag) VALUES (?, ?)");
    $budgets = ['Low', 'Medium', 'High', 'Luxury'];
    $paces = ['Relaxed', 'Moderate', 'Fast'];
    $tags = ['Nature', 'City', 'History', 'Food', 'Adventure'];
    for ($i = 0; $i < 20; $i++) {
        $insertPref->execute([$budgets[array_rand($budgets)], $paces[array_rand($paces)]]);
        $prefId = $pdo->lastInsertId();
        $updateTraveller->execute([$prefId, $travs[$i]]);
        $t1 = $tags[array_rand($tags)];
        $t2 = $tags[array_rand($tags)];
        $insertTag->execute([$prefId, $t1]);
        if ($t1 !== $t2) $insertTag->execute([$prefId, $t2]);
    }

    // ACCOMMODATION_AMENITIES
    $accoms = $pdo->query("SELECT AccommodationID FROM ACCOMMODATION LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);
    $amenities = ['Free WiFi', 'Pool', 'Gym', 'Breakfast Included', 'Parking'];
    $insertAmenity = $pdo->prepare("INSERT IGNORE INTO ACCOMMODATION_AMENITIES (AccommodationID, Amenity) VALUES (?, ?)");
    foreach ($accoms as $accId) {
        $insertAmenity->execute([$accId, $amenities[array_rand($amenities)]]);
        $insertAmenity->execute([$accId, $amenities[array_rand($amenities)]]);
    }

    $pdo->commit();

    echo "All data successfully imported!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
