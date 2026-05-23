<?php
// Generates a massive seed_data.csv with >2000 rows using REALISTIC names
$csvFile = __DIR__ . '/seed_data.csv';
$fp = fopen($csvFile, 'w');

fputcsv($fp, ['section','field1','field2','field3','field4','field5','field6','field7','field8','field9','field10']);

$count = 0;

// 1. Destinations (50 Real World Cities)
$realCities = [
    ["Cape Town","South Africa","-33.9249","18.4241","Iconic city at the tip of Africa.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Johannesburg","South Africa","-26.2041","28.0473","South Africa's largest city.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Paris","France","48.8566","2.3522","The City of Light.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Tokyo","Japan","35.6762","139.6503","Dazzling metropolis.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["London","United Kingdom","51.5074","-0.1278","Historic capital.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["New York","United States","40.7128","-74.0060","The city that never sleeps.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Rome","Italy","41.9028","12.4964","The Eternal City.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Sydney","Australia","-33.8688","151.2093","Stunning harbour city.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Dubai","United Arab Emirates","25.2048","55.2708","Futuristic desert city.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Bangkok","Thailand","13.7563","100.5018","Exotic Thai capital.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Barcelona","Spain","41.3851","2.1734","Gaudi architecture and beaches.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Madrid","Spain","40.4168","-3.7038","Heart of Spanish culture.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Berlin","Germany","52.5200","13.4050","Vibrant history and nightlife.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Munich","Germany","48.1351","11.5820","Bavarian charm and beer gardens.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Amsterdam","Netherlands","52.3676","4.9041","Canals, bikes, and museums.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Vienna","Austria","48.2082","16.3738","Imperial palaces and classical music.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Prague","Czech Republic","50.0755","14.4378","The City of a Hundred Spires.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Budapest","Hungary","47.4979","19.0402","Thermal baths on the Danube.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Athens","Greece","37.9838","23.7275","Birthplace of Western civilization.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Istanbul","Turkey","41.0082","28.9784","Where East meets West.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Rio de Janeiro","Brazil","-22.9068","-43.1729","Christ the Redeemer and Copacabana.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Buenos Aires","Argentina","-34.6037","-58.3816","The Paris of South America.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Lima","Peru","-12.0464","-77.0428","Gastronomic capital of the Americas.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Bogota","Colombia","4.7110","-74.0721","Vibrant Andean metropolis.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Mexico City","Mexico","19.4326","-99.1332","Ancient ruins and modern art.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Toronto","Canada","43.6532","-79.3832","Multicultural hub of Canada.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Vancouver","Canada","49.2827","-123.1207","Mountains meeting the ocean.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Los Angeles","United States","34.0522","-118.2437","Hollywood and sunshine.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["San Francisco","United States","37.7749","-122.4194","Golden Gate and tech hub.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Miami","United States","25.7617","-80.1918","Art Deco and stunning beaches.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Chicago","United States","41.8781","-87.6298","Magnificent architecture and deep dish.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Seoul","South Korea","37.5665","126.9780","K-pop, tech, and ancient palaces.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Beijing","China","39.9042","116.4074","The Great Wall and Forbidden City.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Shanghai","China","31.2304","121.4737","Global financial hub and The Bund.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Hong Kong","China","22.3193","114.1694","Iconic skyline and dim sum.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Singapore","Singapore","1.3521","103.8198","Marina Bay Sands and Gardens.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Kuala Lumpur","Malaysia","3.1390","101.6869","Petronas Twin Towers.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Jakarta","Indonesia","-6.2088","106.8456","Bustling Southeast Asian megacity.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Mumbai","India","19.0760","72.8777","Bollywood and vibrant street life.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["New Delhi","India","28.6139","77.2090","Historic capital of India.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Cairo","Egypt","30.0444","31.2357","The Pyramids of Giza.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Marrakech","Morocco","31.6295","-7.9811","Souks and stunning medinas.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Nairobi","Kenya","-1.2921","36.8219","The safari capital of the world.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Lagos","Nigeria","6.5244","3.3792","West Africa's cultural powerhouse.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Auckland","New Zealand","-36.8485","174.7633","City of Sails.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Melbourne","Australia","-37.8136","144.9631","Coffee, art, and culture.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Honolulu","United States","21.3069","-157.8583","Waikiki Beach and volcanoes.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Reykjavik","Iceland","64.1466","-21.9426","Northern lights and hot springs.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Dublin","Ireland","53.3498","-6.2603","Guinness and friendly locals.","/uploads/destinations/dest_".rand(1,100).".jpg"],
    ["Edinburgh","United Kingdom","55.9533","-3.1883","Historic castle and Royal Mile.","/uploads/destinations/dest_".rand(1,100).".jpg"]
];

foreach ($realCities as $d) {
    fputcsv($fp, array_merge(['destination'], $d, ['','','','']));
    $count++;
}

// 2. Agencies (20 Real-sounding Agencies)
$agencies = [
    'Wanderlust Travel Co.','Global Explorer Tours','Sunset Safaris','Urban Adventures',
    'Mountain High Expeditions','Oceanic Getaways','Luxury Escapes','Budget Backpackers',
    'Cultural Connect','Epic Journeys','Horizon Travel','Blue Sky Vacations',
    'Nomad Ventures','Oasis Travel Agency','Pioneer Expeditions','Majestic Tours',
    'Silver Compass Travel','Dream Destinations','Summit Adventures','Coastal Cruises'
];
for ($i = 0; $i < 20; $i++) {
    fputcsv($fp, ['agency', "agency".($i+1)."@example.com", 'password123', $agencies[$i], 'Verified', rand(5, 15) . '.00', '', '', '', '']);
    $count++;
}

// 3. Travellers (500 Real-sounding Names)
$firsts = ['John','Jane','Michael','Emma','David','Olivia','James','Sophia','Robert','Isabella','William','Ava','Joseph','Mia','Charles','Charlotte','Thomas','Amelia','Christopher','Harper','Daniel','Evelyn','Paul','Abigail','Mark','Emily','Donald','Elizabeth','George','Mila','Kenneth','Ella','Steven','Avery','Edward','Sofia','Brian','Camila','Ronald','Aria','Anthony','Scarlett','Kevin','Victoria','Jason','Madison','Matthew','Luna','Gary','Grace'];
$lasts = ['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Hernandez','Lopez','Gonzalez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin','Lee','Perez','Thompson','White','Harris','Sanchez','Clark','Ramirez','Lewis','Robinson','Walker','Young','Allen','King','Wright','Scott','Torres','Nguyen','Hill','Flores','Green','Adams','Nelson','Baker','Hall','Rivera','Campbell','Mitchell','Carter','Roberts'];
for ($i = 1; $i <= 500; $i++) {
    $f = $firsts[array_rand($firsts)];
    $l = $lasts[array_rand($lasts)];
    fputcsv($fp, ['traveller', "traveller{$i}@example.com", 'password123', $f, $l, '', '', '', '', '']);
    $count++;
}

// 4. Accommodations (400 Real-sounding Hotels)
$hotelBrands = ['Marriott','Hilton','Hyatt','Sheraton','Radisson','Four Seasons','Ritz-Carlton','InterContinental','Westin','Novotel','Ibis','Holiday Inn','Crowne Plaza','Mandarin Oriental','Fairmont'];
$hotelSuffixes = ['Resort','Hotel & Spa','Suites','Boutique Hotel','Lodge','Inn','Grand Hotel'];
$types = ['Hotel','Hostel','Resort','Boutique','Apartment'];
for ($i = 1; $i <= 400; $i++) {
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

// 5. Restaurants (500 Real-sounding Restaurants)
$cuisines = ['Italian','French','Japanese','American','Mexican','Indian','Thai','Chinese','Mediterranean','Local'];
$restaurantPrefixes = ['Bistro','Trattoria','Café','Brasserie','Tavern','Grill','Steakhouse','Cantina','Izakaya','Osteria'];
$restaurantNames = ['The Golden','Blue','Red','The Rustic','Secret','Royal','Grand','Little','Ocean','Sunset'];
$prices = ['$$$$','$$$','$$','$'];
for ($i = 1; $i <= 500; $i++) {
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

// 6. Attractions (600 Real-sounding Attractions)
$atypes = ['Museum','Landmark','Nature','Theme Park','Historical','Cultural','Sport','Shopping'];
$attractionNouns = ['Museum of Art','National Gallery','Central Park','Botanical Gardens','City Zoo','Aquarium','Grand Palace','Historic Fort','Cathedral','Observation Deck'];
for ($i = 1; $i <= 600; $i++) {
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
