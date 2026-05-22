<?php
// database/seed_db.php
// Purpose: Multi-table seeder for Tripistry database.
// Generates a substantial and realistic testing dataset (thousands of records)
// in a fully offline, self-contained programmatic way.

require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting offline scaled-up database seeding...\n";
    $startTime = microtime(true);

    // 1. Disable Foreign Keys and Clear ALL Tables to ensure a clean slate
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    $allTables = [
        'enrols', 'traveller_preference_tags', 'traveller_preference', 'group_trip',
        'review', 'payment', 'booking', 'includes_restaurant', 'includes_attraction',
        'includes_accom', 'includes_flight', 'has_destination', 'travel_package',
        'restaurant', 'attraction', 'accommodation_amenities', 'accommodation', 
        'flight', 'destination', 'travel_agency', 'traveller', 'user'
    ];
    
    foreach ($allTables as $t) {
        $pdo->exec("TRUNCATE TABLE `$t` ");
    }
    echo "Cleaned all 22 database tables.\n";

    // Re-enable Foreign Key Checks for referential integrity during insertions
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Begin transactional seeding
    $pdo->beginTransaction();

    // 2. Define 100 Destinations (10 countries x 10 cities each)
    $geography = [
        'South Africa' => [
            'cities' => ['Cape Town', 'Johannesburg', 'Durban', 'Pretoria', 'Port Elizabeth', 'Knysna', 'Stellenbosch', 'Bloemfontein', 'East London', 'Kimberley'],
            'bounds' => ['lat' => [-34.8, -25.0], 'lng' => [18.4, 32.5]],
            'image' => 'uploads/destinations/cape_town.jpg'
        ],
        'France' => [
            'cities' => ['Paris', 'Nice', 'Lyon', 'Marseille', 'Bordeaux', 'Strasbourg', 'Toulouse', 'Montpellier', 'Nantes', 'Lille'],
            'bounds' => ['lat' => [43.0, 50.0], 'lng' => [-1.0, 7.5]],
            'image' => 'uploads/destinations/paris.jpg'
        ],
        'Japan' => [
            'cities' => ['Tokyo', 'Kyoto', 'Osaka', 'Sapporo', 'Hiroshima', 'Fukuoka', 'Nara', 'Okinawa', 'Nagoya', 'Kobe'],
            'bounds' => ['lat' => [26.0, 43.5], 'lng' => [127.0, 142.5]],
            'image' => 'uploads/destinations/tokyo.jpg'
        ],
        'United Kingdom' => [
            'cities' => ['London', 'Edinburgh', 'Manchester', 'Birmingham', 'Liverpool', 'Glasgow', 'Bath', 'Oxford', 'Belfast', 'Bristol'],
            'bounds' => ['lat' => [50.5, 57.5], 'lng' => [-6.0, 1.5]],
            'image' => 'uploads/destinations/london.jpg'
        ],
        'United States' => [
            'cities' => ['New York', 'Los Angeles', 'Chicago', 'San Francisco', 'Miami', 'Seattle', 'Boston', 'Las Vegas', 'Austin', 'Denver'],
            'bounds' => ['lat' => [25.5, 48.5], 'lng' => [-123.0, -73.0]],
            'image' => 'uploads/destinations/new_york.jpg'
        ],
        'Italy' => [
            'cities' => ['Rome', 'Florence', 'Venice', 'Milan', 'Naples', 'Turin', 'Palermo', 'Genoa', 'Bologna', 'Pisa'],
            'bounds' => ['lat' => [37.0, 46.0], 'lng' => [8.0, 16.0]],
            'image' => 'uploads/destinations/rome.jpg'
        ],
        'Spain' => [
            'cities' => ['Madrid', 'Barcelona', 'Seville', 'Valencia', 'Granada', 'Mallorca', 'Ibiza', 'Bilbao', 'Malaga', 'Toledo'],
            'bounds' => ['lat' => [36.0, 43.5], 'lng' => [-9.0, 3.5]],
            'image' => 'uploads/destinations/madrid.jpg'
        ],
        'Australia' => [
            'cities' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth', 'Adelaide', 'Cairns', 'Hobart', 'Darwin', 'Canberra', 'Gold Coast'],
            'bounds' => ['lat' => [-38.0, -12.0], 'lng' => [113.5, 153.0]],
            'image' => 'uploads/destinations/sydney.jpg'
        ],
        'Canada' => [
            'cities' => ['Toronto', 'Vancouver', 'Montreal', 'Quebec City', 'Calgary', 'Ottawa', 'Halifax', 'Whistler', 'Victoria', 'Edmonton'],
            'bounds' => ['lat' => [43.5, 53.5], 'lng' => [-123.5, -63.5]],
            'image' => 'uploads/destinations/new_york.jpg'
        ],
        'Germany' => [
            'cities' => ['Berlin', 'Munich', 'Frankfurt', 'Hamburg', 'Cologne', 'Stuttgart', 'Dusseldorf', 'Dresden', 'Nuremberg', 'Leipzig'],
            'bounds' => ['lat' => [47.5, 54.5], 'lng' => [6.0, 14.0]],
            'image' => 'uploads/destinations/paris.jpg'
        ]
    ];

    $destStmt = $pdo->prepare("INSERT INTO DESTINATION (DestinationID, City, Country, Latitude, Longitude, Description, ImageURL) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $allDestinationsList = [];
    $destIdCounter = 1;

    foreach ($geography as $country => $data) {
        foreach ($data['cities'] as $cityIndex => $city) {
            // Generate deterministic coordinates within bounding boxes
            $latMin = $data['bounds']['lat'][0];
            $latMax = $data['bounds']['lat'][1];
            $lngMin = $data['bounds']['lng'][0];
            $lngMax = $data['bounds']['lng'][1];
            
            $fraction = $cityIndex / count($data['cities']);
            $lat = $latMin + ($latMax - $latMin) * $fraction;
            $lng = $lngMin + ($lngMax - $lngMin) * (1 - $fraction);
            
            $description = "A wonderful trip to $city, $country. Experience local heritage, historical sites, dining highlights, and outstanding natural beauties.";
            
            $image = "uploads/destinations/dest_{$destIdCounter}.jpg";

            $destStmt->execute([$destIdCounter, $city, $country, $lat, $lng, $description, $image]);
            
            $allDestinationsList[] = [
                'DestinationID' => $destIdCounter,
                'City' => $city,
                'Country' => $country
            ];
            
            $destIdCounter++;
        }
    }
    echo "100 Destinations seeded successfully.\n";

    // 3. Seed 20 Travel Agencies (Users + Travel Agencies)
    $passwordHash = password_hash('password', PASSWORD_DEFAULT);
    $userStmt = $pdo->prepare("INSERT INTO USER (UserID, Email, Password, UserType) VALUES (?, ?, ?, 'Agency')");
    $agencyStmt = $pdo->prepare("INSERT INTO TRAVEL_AGENCY (UserID, AgencyName, VerificationStatus, CommissionRate) VALUES (?, ?, 'Verified', ?)");
    
    $agencyNames = [
        "Tripistry_lsn Official", "Wanderlust Travel Co", "Safari Horizons", "Oceanic Escapes", 
        "Peak Adventures", "Apex Travel", "Vista Journeys", "Horizon Travel Group", 
        "Compass Rose Tours", "Nomad Travels", "Travelers Choice", "Global Explorer", 
        "Voyage Group", "Blue Sky Tours", "Pathfinder Travel", "Odyssey Travel", 
        "Destinations Unlimited", "Star Travel", "Pioneer Tours", "Discovery Travel"
    ];

    $agencyIds = [];
    // UserID 1, and 3 to 21 (UserID 2 is reserved for the primary traveller)
    for ($i = 1; $i <= 21; $i++) {
        if ($i === 2) continue; // skip 2
        
        $email = ($i === 1) ? 'admin@tripistry_lsn.com' : "agency{$i}@test.com";
        $nameIndex = ($i > 2) ? $i - 2 : 0;
        $name = $agencyNames[$nameIndex % count($agencyNames)];
        $commission = 8.00 + ($i % 8);

        $userStmt->execute([$i, $email, $passwordHash]);
        $agencyStmt->execute([$i, $name, $commission]);
        $agencyIds[] = $i;
    }
    echo "20 Travel Agencies (Users + Profiles) seeded.\n";

    // 4. Seed 100 Travellers (Users + Preferences + Tags + Travellers)
    $travellerUserStmt = $pdo->prepare("INSERT INTO USER (UserID, Email, Password, UserType) VALUES (?, ?, ?, 'Traveller')");
    $preferenceStmt = $pdo->prepare("INSERT INTO TRAVELLER_PREFERENCE (PreferenceID, BudgetRange, TravelPace) VALUES (?, ?, ?)");
    $prefTagStmt = $pdo->prepare("INSERT INTO TRAVELLER_PREFERENCE_TAGS (PreferenceID, PreferenceTag) VALUES (?, ?)");
    $travellerStmt = $pdo->prepare("INSERT INTO TRAVELLER (UserID, FirstName, LastName, PreferenceID) VALUES (?, ?, ?, ?)");

    $firstNames = ['John', 'Jane', 'Robert', 'Mary', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Jessica', 'William', 'Amanda', 'Charles', 'Megan', 'Richard', 'Ashley', 'Joseph', 'Taylor', 'Thomas', 'Kayla', 'Daniel', 'Nicole', 'Matthew', 'Rachel', 'Christopher', 'Samantha', 'Donald', 'Heather', 'Paul', 'Elizabeth', 'Steven', 'Lisa', 'Kenneth', 'Betty', 'Kevin', 'Sandra', 'George', 'Helen', 'Timothy', 'Donna'];
    $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores'];
    
    $budgets = ['Economy', 'Mid-range', 'Luxury'];
    $paces = ['Slow', 'Moderate', 'Fast'];
    $tagPool = ['Adventure', 'Relaxing', 'Beach', 'Cultural', 'Foodie', 'Nature', 'Historic', 'Shopping'];

    // Seed John Doe first (UserID 2 - matches default traveler login)
    $travellerUserStmt->execute([2, 'traveller@test.com', $passwordHash]);
    $preferenceStmt->execute([2, 'Mid-range', 'Moderate']);
    $prefTagStmt->execute([2, 'Beach']);
    $prefTagStmt->execute([2, 'Foodie']);
    $travellerStmt->execute([2, 'John', 'Doe', 2]);

    // Seed Travellers UserID 22 to 120 (total 100 travellers)
    for ($i = 22; $i <= 120; $i++) {
        $email = "traveller{$i}@test.com";
        $fn = $firstNames[$i % count($firstNames)];
        $ln = $lastNames[$i % count($lastNames)];
        $bud = $budgets[$i % count($budgets)];
        $pac = $paces[$i % count($paces)];

        $travellerUserStmt->execute([$i, $email, $passwordHash]);
        $preferenceStmt->execute([$i, $bud, $pac]);
        
        // Add unique preference tags
        $tagsUsed = [$tagPool[$i % count($tagPool)], $tagPool[($i + 3) % count($tagPool)]];
        foreach (array_unique($tagsUsed) as $tg) {
            $prefTagStmt->execute([$i, $tg]);
        }

        $travellerStmt->execute([$i, $fn, $ln, $i]);
    }
    echo "100 Travellers (Users + Preferences + Tags) seeded.\n";

    // 5. Seed 2000 Flights (South African gateways to 100 destination cities)
    $airlines = ['Emirates', 'Air France', 'Qatar Airways', 'Singapore Airlines', 'British Airways', 'Lufthansa', 'Delta Air Lines', 'United Airlines', 'South African Airways', 'KLM', 'Qantas', 'ANA', 'Cathay Pacific', 'Turkish Airlines'];
    $flightInsert = $pdo->prepare("INSERT INTO FLIGHT (FlightID, Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $flightCount = 2000;
    for ($f = 1; $f <= $flightCount; $f++) {
        $airline = $airlines[$f % count($airlines)];
        $flightNo = strtoupper(substr($airline, 0, 2)) . rand(100, 9999);
        
        // Departures from SA hubs
        $departCity = 'Johannesburg';
        if ($f % 4 == 0) $departCity = 'Cape Town';
        if ($f % 5 == 0) $departCity = 'Durban';
        
        // Destination city
        $destInfo = $allDestinationsList[($f - 1) % count($allDestinationsList)];
        $arrivalCity = $destInfo['City'];
        
        if ($arrivalCity === $departCity) {
            $arrivalCity = 'London';
        }
        
        $departDate = date('Y-m-d H:i:s', strtotime("+ " . ($f % 120 + 1) . " days + " . rand(1, 23) . " hours"));
        $arrivalDate = date('Y-m-d H:i:s', strtotime($departDate . " + " . rand(2, 18) . " hours"));
        $price = rand(2000, 18000) + rand(0, 99) / 100;
        
        $flightInsert->execute([$f, $airline, $flightNo, $departCity, $arrivalCity, $departDate, $arrivalDate, $price]);
    }
    echo "2000 Flights seeded successfully.\n";

    // 6. Seed 400 Accommodations (4 hotels per destination)
    $hotelPrefixes = ['Grand Royal', 'Ocean View Resort', 'Metropolis Suites', 'Forest Lodge', 'Sheraton Club', 'Hilton Plaza', 'Park Hyatt', 'Comfort Inn', 'Boutique Residence', 'Red Carnation Guesthouse', 'Aman Resorts', 'Radisson Blu', 'Marriott Executive', 'Four Seasons'];
    $hotelTypes = ['Hotel', 'Resort', 'Guesthouse', 'Lodge'];
    
    $accomInsert = $pdo->prepare("INSERT INTO ACCOMMODATION (AccommodationID, Name, Type, StarRating, PricePerNight, Address, DestinationID) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $amenityInsert = $pdo->prepare("INSERT INTO ACCOMMODATION_AMENITIES (AccommodationID, Amenity) VALUES (?, ?)");
    
    $accomCount = 400;
    $amenityPool = ['Free Wi-Fi', 'Swimming Pool', 'Spa', 'Gym', 'Room Service', 'Bar', 'Secure Parking', 'Restaurant', 'Concierge', 'Private Beach'];
    
    for ($a = 1; $a <= $accomCount; $a++) {
        // Distribute hotels evenly across all 100 destinations
        $destId = (($a - 1) % 100) + 1;
        $destName = $allDestinationsList[$destId - 1]['City'];
        
        $prefix = $hotelPrefixes[$a % count($hotelPrefixes)];
        $name = "$prefix $destName";
        $type = $hotelTypes[$a % count($hotelTypes)];
        $rating = rand(3, 5);
        $price = rand(400, 4800) + rand(0, 99) / 100;
        $address = rand(1, 999) . " Avenue of the Sun, $destName";
        
        $accomInsert->execute([$a, $name, $type, $rating, $price, $address, $destId]);
        
        // Add random amenities (2 to 5)
        $numAmenities = rand(2, 5);
        for ($am = 0; $am < $numAmenities; $am++) {
            $amenity = $amenityPool[($a + $am) % count($amenityPool)];
            try {
                $amenityInsert->execute([$a, $amenity]);
            } catch (PDOException $e) {
                // Ignore duplicates
            }
        }
    }
    echo "400 Accommodations with amenities seeded successfully.\n";

    // 7. Seed 400 Attractions (4 attractions per destination)
    $attractionPrefixes = ['Historic Castle', 'National Reserve', 'Ancient Temple', 'City Museum', 'Botanical Gardens', 'Sky Deck Observatory', 'Scenic Coastal Path', 'Cultural Heritage Center', 'Art Gallery', 'Iconic Cathedral', 'Symphony Hall', 'Adventure Park', 'Ancient Ruins', 'Wildlife Sanctuary'];
    $attractionTypes = ['Landmark', 'Nature', 'Museum', 'Religious', 'Shopping', 'Adventure'];
    
    $attrInsert = $pdo->prepare("INSERT INTO ATTRACTION (AttractionID, Name, Type, EntryFee, Description, OpeningHours, DestinationID) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $attrCount = 400;
    for ($at = 1; $at <= $attrCount; $at++) {
        $destId = (($at - 1) % 100) + 1;
        $destName = $allDestinationsList[$destId - 1]['City'];
        
        $prefix = $attractionPrefixes[$at % count($attractionPrefixes)];
        $type = $attractionTypes[$at % count($attractionTypes)];
        $fee = ($at % 3 === 0) ? 0.00 : (rand(50, 600));
        $description = "A wonderful $type attraction located in the heart of $destName, offering visitors a unique experience.";
        $hours = "09:00 - 18:00";
        
        $attrInsert->execute([$at, "$prefix of $destName", $type, $fee, $description, $hours, $destId]);
    }
    echo "400 Attractions seeded successfully.\n";

    // 8. Seed 600 Restaurants (6 restaurants per destination)
    $restaurantPrefixes = ['The Golden Spoon', 'Le Bistrot Antique', 'Sakura Sushi', 'V&A Bistro', 'Ocean Breeze Seafood', 'Bella Italia', 'Steakhouse 88', 'Curry Kingdom', 'Green Lotus Garden', 'Cantina Del Sol', 'Royal Grill', 'The Corner Waffle', 'The Roast House', 'Olive Garden Inn', 'Le Jardin', 'Noodle Box'];
    $cuisines = ['Italian', 'French', 'Japanese', 'Seafood', 'Steakhouse', 'Indian', 'Chinese', 'Mexican', 'Local Cuisine'];
    $priceRanges = ['$', '$$', '$$$'];
    
    $restInsert = $pdo->prepare("INSERT INTO RESTAURANT (RestaurantID, Name, CuisineType, PriceRange, Address, Rating, DestinationID) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $restCount = 600;
    for ($r = 1; $r <= $restCount; $r++) {
        $destId = (($r - 1) % 100) + 1;
        $destName = $allDestinationsList[$destId - 1]['City'];
        
        $prefix = $restaurantPrefixes[$r % count($restaurantPrefixes)];
        $cuisine = $cuisines[$r % count($cuisines)];
        $price = $priceRanges[$r % count($priceRanges)];
        $name = "$prefix $cuisine $destName";
        $address = rand(10, 500) . " Foodie Boulevard, $destName";
        $rating = rand(30, 50) / 10;
        
        $restInsert->execute([$r, $name, $cuisine, $price, $address, $rating, $destId]);
    }
    echo "600 Restaurants seeded successfully.\n";

    // 9. Seed 200 Travel Packages
    $packageInsert = $pdo->prepare("
        INSERT INTO TRAVEL_PACKAGE (PackageID, Title, Description, Price, DurationDays, IsGroupTrip, Status, ImageURL, AgencyID) 
        VALUES (:package_id, :title, :description, :price, :duration, :is_group, 'Active', :image_url, :agency_id)
    ");

    $hasDest = $pdo->prepare("INSERT INTO HAS_DESTINATION (PackageID, DestinationID) VALUES (?, ?)");
    $incFlight = $pdo->prepare("INSERT INTO INCLUDES_FLIGHT (PackageID, FlightID) VALUES (?, ?)");
    $incAccom = $pdo->prepare("INSERT INTO INCLUDES_ACCOM (PackageID, AccommodationID) VALUES (?, ?)");
    $incAttr = $pdo->prepare("INSERT INTO INCLUDES_ATTRACTION (PackageID, AttractionID) VALUES (?, ?)");
    $incRest = $pdo->prepare("INSERT INTO INCLUDES_RESTAURANT (PackageID, RestaurantID) VALUES (?, ?)");

    $packageArchetypes = [
        ['suffix' => 'Luxury Escape', 'desc' => 'Indulge in a premium, stress-free holiday package. Includes top-rated 5-star accommodation, custom airport shuttle, Michelin-caliber local dining, and private guided excursions.', 'image' => 'uploads/packages/luxury.jpg'],
        ['suffix' => 'Adventure Tour', 'desc' => 'For the bold traveler. A thrilling itinerary packed with outdoor hiking trails, local history explorations, scenic vistas, and budget-friendly cozy stays.', 'image' => 'uploads/packages/adventure.jpg'],
        ['suffix' => 'Family Holiday', 'desc' => 'Create beautiful memories with the family. Relaxed paced tours, kid-friendly excursions, central hotels, and classic restaurants suited for all ages.', 'image' => 'uploads/packages/family.jpg'],
        ['suffix' => 'Culinary & Culture', 'desc' => 'Discover the destination through its authentic flavors. This curated package highlights local food markets, wine pairings, traditional cooking classes, and historical temple tours.', 'image' => 'uploads/packages/culinary.jpg']
    ];

    $packageCount = 200;

    for ($pid = 1; $pid <= $packageCount; $pid++) {
        // Map evenly to all 100 destinations
        $destId = (($pid - 1) % 100) + 1;
        $cityName = $allDestinationsList[$destId - 1]['City'];
        
        $arch = $packageArchetypes[$pid % count($packageArchetypes)];
        $title = "$cityName {$arch['suffix']}";
        $description = "{$arch['desc']} Experience the magic of $cityName and its beautiful local surroundings.";
        
        $price = rand(6000, 48000) + rand(0, 99) / 100;
        $duration = rand(3, 14);
        $isGroup = ($pid % 4 === 0) ? 1 : 0;
        $agencyId = $agencyIds[$pid % count($agencyIds)];
        $image = "uploads/packages/pkg_{$pid}.jpg";

        $packageInsert->execute([
            ':package_id' => $pid,
            ':title' => $title,
            ':description' => $description,
            ':price' => $price,
            ':duration' => $duration,
            ':is_group' => $isGroup,
            ':image_url' => $image,
            ':agency_id' => $agencyId
        ]);

        // Link HAS_DESTINATION
        $hasDest->execute([$pid, $destId]);

        // Link Flight (FlightIDs 1 to 2000. Use flight mapped to destination)
        $flightId = (($destId - 1) * 20) + ($pid % 20) + 1;
        $incFlight->execute([$pid, $flightId]);

        // Link Accommodations (AccomIDs 1 to 400. Mapped to destination: 4 per dest)
        $accomId = (($destId - 1) * 4) + ($pid % 4) + 1;
        $incAccom->execute([$pid, $accomId]);

        // Link Attractions (AttrIDs 1 to 400. Mapped to destination: 4 per dest, link 2)
        $attrId1 = (($destId - 1) * 4) + ($pid % 3) + 1;
        $attrId2 = (($destId - 1) * 4) + (($pid + 1) % 3) + 1;
        $incAttr->execute([$pid, $attrId1]);
        $incAttr->execute([$pid, $attrId2]);

        // Link Restaurants (RestIDs 1 to 600. Mapped to destination: 6 per dest, link 2)
        $restId1 = (($destId - 1) * 6) + ($pid % 5) + 1;
        $restId2 = (($destId - 1) * 6) + (($pid + 1) % 5) + 1;
        $incRest->execute([$pid, $restId1]);
        $incRest->execute([$pid, $restId2]);
    }
    echo "200 Travel Packages generated and relationally mapped.\n";

    // 10. Seed 400 Bookings & Payments
    $bookingInsert = $pdo->prepare("
        INSERT INTO BOOKING (BookingID, BookingDate, TotalCost, Status, UserID, PackageID) 
        VALUES (:booking_id, :booking_date, :total_cost, :status, :user_id, :package_id)
    ");

    $paymentInsert = $pdo->prepare("
        INSERT INTO PAYMENT (BookingID, PaymentSeq, Amount, PaymentDate, Status) 
        VALUES (?, ?, ?, ?, 'Completed')
    ");

    $bookingStatuses = ['Confirmed', 'Confirmed', 'Confirmed', 'Pending', 'Cancelled'];
    $confirmedBookings = [];
    
    $bookingCount = 400;
    for ($bid = 1; $bid <= $bookingCount; $bid++) {
        $pkgId = (($bid - 1) % 200) + 1;
        // UserID 2 (John Doe), or 22 to 120 (travellers)
        if ($bid <= 15) {
            $travellerId = 2; // Let John Doe have 15 bookings for rich dashboard display
        } else {
            $travellerId = (($bid + 7) % 99) + 22;
        }
        
        $status = $bookingStatuses[$bid % count($bookingStatuses)];
        
        // Retrieve package price
        $pkgPriceStmt = $pdo->prepare("SELECT Price FROM TRAVEL_PACKAGE WHERE PackageID = ?");
        $pkgPriceStmt->execute([$pkgId]);
        $pkgPrice = $pkgPriceStmt->fetchColumn();

        $daysAgo = $bid % 90;
        $bookingDate = date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));

        $bookingInsert->execute([
            ':booking_id' => $bid,
            ':booking_date' => $bookingDate,
            ':total_cost' => $pkgPrice,
            ':status' => $status,
            ':user_id' => $travellerId,
            ':package_id' => $pkgId
        ]);

        if ($status === 'Confirmed') {
            $confirmedBookings[] = ['booking_id' => $bid, 'pkg_id' => $pkgId, 'user_id' => $travellerId, 'price' => $pkgPrice, 'date' => $bookingDate];
            $paymentInsert->execute([$bid, 1, $pkgPrice, $bookingDate]);
        } elseif ($status === 'Pending') {
            $deposit = round($pkgPrice * 0.4, 2);
            $paymentInsert->execute([$bid, 1, $deposit, $bookingDate]);
        }
    }
    echo "400 Bookings and corresponding payment records seeded.\n";

    // 11. Seed exactly 250 Reviews with tailored content
    $reviewInsert = $pdo->prepare("
        INSERT INTO REVIEW (ReviewID, UserID, PackageID, Comment, RatingScore, DatePosted, BookingID) 
        VALUES (:review_id, :user_id, :pkg_id, :comment, :rating, :date_posted, :booking_id)
    ");

    $reviewPool = [
        5 => [
            "An absolutely phenomenal experience! The entire itinerary was perfectly paced, and the hotel accommodation exceeded all expectations. We thoroughly enjoyed the local dining and attractions.",
            "Magical getaway! The service was outstanding, the flights were perfectly scheduled, and the local sightseeing was breath-taking. Worth every single cent!",
            "Simply incredible. I have never had a more seamless holiday. The agency took care of everything and the resort was a tropical dream.",
            "Exceeded our highest hopes. Perfect coordination, highly professional guide, and delicious restaurants included. Will definitely book through this agency again."
        ],
        4 => [
            "We had a wonderful trip overall. The destination was beautiful and the hotel was very comfortable. The only minor drawback was a brief delay on our transfer flight.",
            "Very well organized package. Great balance of leisure and sightseeing. Highly recommend the dining options selected by the agency.",
            "Excellent value for money. The accommodation was clean and centrally located, and the attractions were fun. Highly recommended for couples.",
            "A solid four-star vacation. Excellent guidance on sightseeing, though some museum tours felt a bit rushed. The hotel staff was lovely."
        ],
        3 => [
            "Decent package. The destination itself is stunning, but the hotel looked a bit dated compared to the online photos. It was an okay trip for the price.",
            "The scenery was great, but the package pacing was too fast for us. We felt rather exhausted by day four. Good service from the booking agent though.",
            "An average experience. Accommodation was acceptable but lacked amenities like fast Wi-Fi. The dining options were okay, but not premium."
        ],
        2 => [
            "Quite disappointing. The hotel did not match the description and the entry fees for attractions were not fully explained beforehand. Not worth the price.",
            "Poor scheduling. We missed one of the main excursions because our flight arrival time was scheduled too close to the tour departure. The agency was unhelpful."
        ]
    ];

    $reviewCount = 0;
    foreach ($confirmedBookings as $index => $cb) {
        if ($reviewCount >= 250) break;

        $rating = rand(3, 5);
        if ($index % 10 === 0) $rating = 2; // Occasional lower review score for realism

        $comments = $reviewPool[$rating];
        $comment = $comments[$index % count($comments)];
        $reviewId = $reviewCount + 1;
        $reviewDate = date('Y-m-d H:i:s', strtotime($cb['date'] . ' +6 days'));

        $reviewInsert->execute([
            ':review_id' => $reviewId,
            ':user_id' => $cb['user_id'],
            ':pkg_id' => $cb['pkg_id'],
            ':comment' => $comment,
            ':rating' => $rating,
            ':date_posted' => $reviewDate,
            ':booking_id' => $cb['booking_id']
        ]);
        $reviewCount++;
    }
    echo "250 Reviews generated and published.\n";

    // 12. Seed 20 Group Trips and Enrolments
    $groupTripInsert = $pdo->prepare("
        INSERT INTO GROUP_TRIP (GroupTripID, TripName, MaxCapacity, AgencyID, PackageID) 
        VALUES (:group_trip_id, :trip_name, :max_capacity, :agency_id, :package_id)
    ");

    $enrolInsert = $pdo->prepare("
        INSERT INTO ENROLS (UserID, GroupTripID) 
        VALUES (?, ?)
    ");

    // Select packages marked as group trips
    $groupPkgsStmt = $pdo->query("SELECT PackageID, Title, AgencyID FROM TRAVEL_PACKAGE WHERE IsGroupTrip = 1 LIMIT 20");
    $groupPkgs = $groupPkgsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($groupPkgs as $index => $gp) {
        $gtId = $index + 1;
        $tripName = str_replace(['Adventure Tour', 'Luxury Escape', 'Family Holiday', 'Culinary & Culture'], 'Group Departure', $gp['Title']) . ' ' . rand(2026, 2027);
        
        $groupTripInsert->execute([
            ':group_trip_id' => $gtId,
            ':trip_name' => $tripName,
            ':max_capacity' => rand(15, 30),
            ':agency_id' => $gp['AgencyID'],
            ':package_id' => $gp['PackageID']
        ]);

        // Enrol 6-12 random travellers in this group trip
        $numEnrolled = rand(6, 12);
        $enrolledIds = [];
        for ($e = 0; $e < $numEnrolled; $e++) {
            $tId = rand(22, 120);
            if (!in_array($tId, $enrolledIds)) {
                $enrolledIds[] = $tId;
                $enrolInsert->execute([$tId, $gtId]);
            }
        }
    }
    echo "20 Group Trips with multiple traveller enrolments seeded.\n";

    $pdo->commit();
    $elapsed = round(microtime(true) - $startTime, 3);
    echo "\nScaled-up offline database seeding completed successfully in {$elapsed} seconds!\n";

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\nSeeding failed: " . $e->getMessage() . "\n";
    exit(1);
}
