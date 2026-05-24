<?php
// Generates a massive seed_data.csv with >2000 rows using REALISTIC names
$csvFile = __DIR__ . '/seed_data.csv';
$fp = fopen($csvFile, 'w');

fputcsv($fp, ['section','field1','field2','field3','field4','field5','field6','field7','field8','field9','field10']);

$count = 0;

// 1. Destinations Generator
function generateUniqueCities(int $targetCount): array {
    $realCities = [
        ["Cape Town","South Africa","-33.9249","18.4241","Iconic city at the tip of Africa."],
        ["Johannesburg","South Africa","-26.2041","28.0473","South Africa's largest city."],
        ["Paris","France","48.8566","2.3522","The City of Light."],
        ["Tokyo","Japan","35.6762","139.6503","Dazzling metropolis."],
        ["London","United Kingdom","51.5074","-0.1278","Historic capital."],
        ["New York","United States","40.7128","-74.0060","The city that never sleeps."],
        ["Rome","Italy","41.9028","12.4964","The Eternal City."],
        ["Sydney","Australia","-33.8688","151.2093","Stunning harbour city."],
        ["Dubai","United Arab Emirates","25.2048","55.2708","Futuristic desert city."],
        ["Bangkok","Thailand","13.7563","100.5018","Exotic Thai capital."],
        ["Barcelona","Spain","41.3851","2.1734","Gaudi architecture and beaches."],
        ["Madrid","Spain","40.4168","-3.7038","Heart of Spanish culture."],
        ["Berlin","Germany","52.5200","13.4050","Vibrant history and nightlife."],
        ["Munich","Germany","48.1351","11.5820","Bavarian charm and beer gardens."],
        ["Amsterdam","Netherlands","52.3676","4.9041","Canals, bikes, and museums."],
        ["Vienna","Austria","48.2082","16.3738","Imperial palaces and classical music."],
        ["Prague","Czech Republic","50.0755","14.4378","The City of a Hundred Spires."],
        ["Budapest","Hungary","47.4979","19.0402","Thermal baths on the Danube."],
        ["Athens","Greece","37.9838","23.7275","Birthplace of Western civilization."],
        ["Istanbul","Turkey","41.0082","28.9784","Where East meets West."],
        ["Rio de Janeiro","Brazil","-22.9068","-43.1729","Christ the Redeemer and Copacabana."],
        ["Buenos Aires","Argentina","-34.6037","-58.3816","The Paris of South America."],
        ["Lima","Peru","-12.0464","-77.0428","Gastronomic capital of the Americas."],
        ["Bogota","Colombia","4.7110","-74.0721","Vibrant Andean metropolis."],
        ["Mexico City","Mexico","19.4326","-99.1332","Ancient ruins and modern art."],
        ["Toronto","Canada","43.6532","-79.3832","Multicultural hub of Canada."],
        ["Vancouver","Canada","49.2827","-123.1207","Mountains meeting the ocean."],
        ["Los Angeles","United States","34.0522","-118.2437","Hollywood and sunshine."],
        ["San Francisco","United States","37.7749","-122.4194","Golden Gate and tech hub."],
        ["Miami","United States","25.7617","-80.1918","Art Deco and stunning beaches."],
        ["Chicago","United States","41.8781","-87.6298","Magnificent architecture and deep dish."],
        ["Seoul","South Korea","37.5665","126.9780","K-pop, tech, and ancient palaces."],
        ["Beijing","China","39.9042","116.4074","The Great Wall and Forbidden City."],
        ["Shanghai","China","31.2304","121.4737","Global financial hub and The Bund."],
        ["Hong Kong","China","22.3193","114.1694","Iconic skyline and dim sum."],
        ["Singapore","Singapore","1.3521","103.8198","Marina Bay Sands and Gardens."],
        ["Kuala Lumpur","Malaysia","3.1390","101.6869","Petronas Twin Towers."],
        ["Jakarta","Indonesia","-6.2088","106.8456","Bustling Southeast Asian megacity."],
        ["Mumbai","India","19.0760","72.8777","Bollywood and vibrant street life."],
        ["New Delhi","India","28.6139","77.2090","Historic capital of India."],
        ["Cairo","Egypt","30.0444","31.2357","The Pyramids of Giza."],
        ["Marrakech","Morocco","31.6295","-7.9811","Souks and stunning medinas."],
        ["Nairobi","Kenya","-1.2921","36.8219","The safari capital of the world."],
        ["Lagos","Nigeria","6.5244","3.3792","West Africa's cultural powerhouse."],
        ["Auckland","New Zealand","-36.8485","174.7633","City of Sails."],
        ["Melbourne","Australia","-37.8136","144.9631","Coffee, art, and culture."],
        ["Honolulu","United States","21.3069","-157.8583","Waikiki Beach and volcanoes."],
        ["Reykjavik","Iceland","64.1466","-21.9426","Northern lights and hot springs."],
        ["Dublin","Ireland","53.3498","-6.2603","Guinness and friendly locals."],
        ["Edinburgh","United Kingdom","55.9533","-3.1883","Historic castle and Royal Mile."]
    ];

    $results = [];
    $seen = [];
    
    // Add real ones first
    foreach ($realCities as $rc) {
        $key = strtolower($rc[0] . "|" . $rc[1]);
        $seen[$key] = true;
        $img = "/uploads/destinations/dest_" . rand(1, 100) . ".jpg";
        $results[] = [$rc[0], $rc[1], $rc[2], $rc[3], $rc[4], $img];
    }

    $prefixes = ['Port', 'Saint', 'Mount', 'Lake', 'New', 'West', 'East', 'North', 'South', 'San', 'Santa', 'Fort', 'Grand', 'Cape', 'Hill', 'Valley', 'River', 'High', 'Old', 'Glen'];
    $bases = ['Spring', 'Summer', 'Winter', 'Autumn', 'Oak', 'Pine', 'Maple', 'River', 'Stone', 'Rock', 'Iron', 'Gold', 'Silver', 'Copper', 'Wind', 'Rain', 'Storm', 'Sunny', 'Cloudy', 'Star', 'Moon', 'Sun', 'Ocean', 'Sea', 'Lake', 'Valley', 'Mountain', 'Forest', 'Green', 'Blue', 'Red', 'White', 'Black'];
    $suffixes = ['ville', 'town', 'burg', 'city', 'port', 'mouth', 'bridge', 'ford', 'ton', 'wood', 'field', 'dale', 'land', 'bury', 'ham', 'stead', 'cove', 'peak', 'haven', 'bay'];
    
    $countries = [
        'South Africa', 'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France', 'Italy', 'Spain', 
        'Japan', 'China', 'India', 'Brazil', 'Argentina', 'Egypt', 'Morocco', 'Kenya', 'Nigeria', 'New Zealand', 
        'Netherlands', 'Sweden', 'Norway', 'Denmark', 'Switzerland', 'Austria', 'Belgium', 'Greece', 'Turkey', 
        'Thailand', 'Singapore', 'South Korea', 'Mexico', 'Colombia', 'Peru', 'Chile', 'Ireland', 'Portugal', 
        'Russia', 'Saudi Arabia', 'United Arab Emirates', 'Vietnam', 'Indonesia', 'Malaysia', 'Philippines', 
        'Poland', 'Czech Republic', 'Hungary', 'Romania', 'Ukraine', 'Finland'
    ];

    while (count($results) < $targetCount) {
        $p = $prefixes[array_rand($prefixes)];
        $b = $bases[array_rand($bases)];
        $s = $suffixes[array_rand($suffixes)];
        
        $city = rand(1, 2) === 1 ? "$p $b$s" : "$b$s";
        $country = $countries[array_rand($countries)];
        
        $key = strtolower($city . "|" . $country);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        $lat = number_format(rand(-900000, 900000) / 10000, 4);
        $lon = number_format(rand(-1800000, 1800000) / 10000, 4);
        $desc = "Discover $city in $country, an extraordinary and unique travel destination.";
        $img = "/uploads/destinations/dest_" . rand(1, 100) . ".jpg";
        
        $results[] = [$city, $country, $lat, $lon, $desc, $img];
    }
    
    return $results;
}

$realCities = generateUniqueCities(1000);

foreach ($realCities as $d) {
    fputcsv($fp, array_merge(['destination'], $d, ['','','','']));
    $count++;
}

// 2. Agencies (100 Real-sounding Agencies)
$agencies = [
    'Wanderlust Travel Co.','Global Explorer Tours','Sunset Safaris','Urban Adventures',
    'Mountain High Expeditions','Oceanic Getaways','Luxury Escapes','Budget Backpackers',
    'Cultural Connect','Epic Journeys','Horizon Travel','Blue Sky Vacations',
    'Nomad Ventures','Oasis Travel Agency','Pioneer Expeditions','Majestic Tours',
    'Silver Compass Travel','Dream Destinations','Summit Adventures','Coastal Cruises'
];
for ($i = 0; $i < 100; $i++) {
    $baseName = $agencies[$i % count($agencies)];
    $suffix = ($i >= count($agencies)) ? " " . (floor($i / count($agencies)) + 1) : "";
    $agencyName = $baseName . $suffix;
    fputcsv($fp, ['agency', "agency".($i+1)."@example.com", 'password123', $agencyName, 'Verified', rand(5, 15) . '.00', '', '', '', '']);
    $count++;
}

// 3. Travellers (10000 Real-sounding Names)
$firsts = ['John','Jane','Michael','Emma','David','Olivia','James','Sophia','Robert','Isabella','William','Ava','Joseph','Mia','Charles','Charlotte','Thomas','Amelia','Christopher','Harper','Daniel','Evelyn','Paul','Abigail','Mark','Emily','Donald','Elizabeth','George','Mila','Kenneth','Ella','Steven','Avery','Edward','Sofia','Brian','Camila','Ronald','Aria','Anthony','Scarlett','Kevin','Victoria','Jason','Madison','Matthew','Luna','Gary','Grace'];
$lasts = ['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Hernandez','Lopez','Gonzalez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin','Lee','Perez','Thompson','White','Harris','Sanchez','Clark','Ramirez','Lewis','Robinson','Walker','Young','Allen','King','Wright','Scott','Torres','Nguyen','Hill','Flores','Green','Adams','Nelson','Baker','Hall','Rivera','Campbell','Mitchell','Carter','Roberts'];
for ($i = 1; $i <= 10000; $i++) {
    $f = $firsts[array_rand($firsts)];
    $l = $lasts[array_rand($lasts)];
    fputcsv($fp, ['traveller', "traveller{$i}@example.com", 'password123', $f, $l, '', '', '', '', '']);
    $count++;
}

// 4. Accommodations (5000 Real-sounding Hotels)
$hotelBrands = ['Marriott','Hilton','Hyatt','Sheraton','Radisson','Four Seasons','Ritz-Carlton','InterContinental','Westin','Novotel','Ibis','Holiday Inn','Crowne Plaza','Mandarin Oriental','Fairmont'];
$hotelSuffixes = ['Resort','Hotel & Spa','Suites','Boutique Hotel','Lodge','Inn','Grand Hotel'];
$types = ['Hotel','Hostel','Resort','Boutique','Apartment'];
for ($i = 1; $i <= 5000; $i++) {
    $d = $realCities[array_rand($realCities)];
    $brand = $hotelBrands[array_rand($hotelBrands)];
    $suffix = $hotelSuffixes[array_rand($hotelSuffixes)];
    $hotelName = "The $brand {$d[0]} $suffix";
    if (rand(1,3) == 1) $hotelName = "{$d[0]} Central $suffix"; // Add some variety
    
    $t = $types[array_rand($types)];
    $price = rand(500, 5000);
    fputcsv($fp, ['accommodation', $hotelName, $t, rand(3,5), $price, "123 Main St, {$d[0]}", $d[0], '', '', '']);
    $count++;
}

// 5. Restaurants (5000 Real-sounding Restaurants)
$cuisines = ['Italian','French','Japanese','American','Mexican','Indian','Thai','Chinese','Mediterranean','Local'];
$restaurantPrefixes = ['Bistro','Trattoria','Café','Brasserie','Tavern','Grill','Steakhouse','Cantina','Izakaya','Osteria'];
$restaurantNames = ['The Golden','Blue','Red','The Rustic','Secret','Royal','Grand','Little','Ocean','Sunset'];
$prices = ['$$$$','$$$','$$','$'];
for ($i = 1; $i <= 5000; $i++) {
    $d = $realCities[array_rand($realCities)];
    $c = $cuisines[array_rand($cuisines)];
    $prefix = $restaurantPrefixes[array_rand($restaurantPrefixes)];
    $name = $restaurantNames[array_rand($restaurantNames)];
    $restName = "$name $c $prefix";
    if (rand(1,3) == 1) $restName = "{$d[0]} $prefix";
    
    $p = $prices[array_rand($prices)];
    $rating = rand(30, 50) / 10;
    fputcsv($fp, ['restaurant', $restName, $c, $p, "456 Food Ave, {$d[0]}", $rating, $d[0], '', '', '']);
    $count++;
}

// 6. Attractions (5000 Real-sounding Attractions)
$atypes = ['Museum','Landmark','Nature','Theme Park','Historical','Cultural','Sport','Shopping'];
$attractionNouns = ['Museum of Art','National Gallery','Central Park','Botanical Gardens','City Zoo','Aquarium','Grand Palace','Historic Fort','Cathedral','Observation Deck'];
for ($i = 1; $i <= 5000; $i++) {
    $d = $realCities[array_rand($realCities)];
    $t = $atypes[array_rand($atypes)];
    $noun = $attractionNouns[array_rand($attractionNouns)];
    $attrName = "{$d[0]} $noun";
    
    $fee = rand(0, 500);
    fputcsv($fp, ['attraction', $attrName, $t, $fee, "Explore the amazing $attrName in the heart of {$d[0]}.", "09:00-17:00", $d[0], '', '', '']);
    $count++;
}

fclose($fp);
echo "Generated seed_data.csv with $count rows.\n";
