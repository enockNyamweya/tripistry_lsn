<?php
// database/fetch_flights.php
// Purpose: Seed flight data using CSV, RapidAPI, or programmatic fallback.

require_once __DIR__ . '/../../config/database.php';

try {
    echo "=== Flight Seeder Started ===\n";
    // Check if the script should run in API mode or offline CSV mode
    $apiKey = env('RAPIDAPI_KEY');
    
    // Define the path to the CSV file
    $csvPath = __DIR__ . '/flights.csv';
    $insertedCount = 0;
    
    // Check if CSV file exists and is readable
    if (file_exists($csvPath) && ($handle = fopen($csvPath, 'r')) !== false) {
        echo "Found local CSV file at database/flights.csv. Commencing CSV ingestion...\n";
        
        // Read header line
        $headers = fgetcsv($handle);
        
        $stmt = $pdo->prepare("
            INSERT INTO FLIGHT (Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price)
            VALUES (:airline, :flight_number, :dep_city, :arr_city, :dep_time, :arr_time, :price)
        ");
        
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM FLIGHT 
            WHERE FlightNumber = :flight_number AND DepartureTime = :dep_time
        ");
        
        while (($data = fgetcsv($handle)) !== false) {
            // Map columns: Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price
            if (count($data) < 7) continue;
            
            $airline = trim($data[0]);
            $flightNumber = trim($data[1]);
            $depCity = trim($data[2]);
            $arrCity = trim($data[3]);
            $depTime = trim($data[4]);
            $arrTime = trim($data[5]);
            $price = floatval($data[6]);
            
            // Check for duplicates before inserting
            $checkStmt->execute([':flight_number' => $flightNumber, ':dep_time' => $depTime]);
            if ($checkStmt->fetchColumn() > 0) {
                // Skip duplicate
                continue;
            }
            
            $stmt->execute([
                ':airline' => $airline,
                ':flight_number' => $flightNumber,
                ':dep_city' => $depCity,
                ':arr_city' => $arrCity,
                ':dep_time' => $depTime,
                ':arr_time' => $arrTime,
                ':price' => $price
            ]);
            $insertedCount++;
        }
        fclose($handle);
        echo "CSV Ingestion complete. Seeded $insertedCount flights.\n";
        
    } else {
        // Fallback to RapidAPI or programmatic generation
        $rapidApiKey = env('RAPIDAPI_KEY');
        
        if (!empty($rapidApiKey) && $rapidApiKey !== 'your_rapidapi_key') {
            echo "CSV file not found/readable. RAPIDAPI_KEY detected. Ingesting from Flight API via RapidAPI...\n";
            
            // Querying a popular public Flights API on RapidAPI (Skyscanner extended or equivalent flight search)
            $url = "https://skyscanner44.p.rapidapi.com/search-extended?adults=1&cabinClass=economy&children=0&infants=0&origin=JNB&destination=LHR&departureDate=" . date('Y-m-d', strtotime('+30 days')) . "&currency=ZAR";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-RapidAPI-Host: skyscanner44.p.rapidapi.com",
                "X-RapidAPI-Key: " . $rapidApiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $response = curl_exec($ch);
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpStatusCode === 200 && empty($curlError)) {
                $result = json_decode($response, true);
                // Parse flights from API response (depends on exact JSON schema, handling Skyscanner structure as example)
                if (isset($result['itineraries']['buckets']) && is_array($result['itineraries']['buckets'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO FLIGHT (Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price)
                        VALUES (:airline, :flight_number, :dep_city, :arr_city, :dep_time, :arr_time, :price)
                    ");
                    
                    foreach ($result['itineraries']['buckets'] as $bucket) {
                        if (!isset($bucket['items']) || !is_array($bucket['items'])) continue;
                        
                        foreach ($bucket['items'] as $item) {
                            // Extract itinerary legs
                            if (!isset($item['legs'][0])) continue;
                            $leg = $item['legs'][0];
                            
                            $airline = $leg['carriers']['marketing'][0]['name'] ?? 'Partner Airline';
                            $flightNumber = ($leg['carriers']['marketing'][0]['alternateId'] ?? 'FL') . rand(100, 999);
                            $depCity = 'Johannesburg';
                            $arrCity = 'London';
                            $depTime = date('Y-m-d H:i:s', strtotime($leg['departure'] ?? '+30 days'));
                            $arrTime = date('Y-m-d H:i:s', strtotime($leg['arrival'] ?? '+30 days + 11 hours'));
                            $price = floatval($item['price']['raw'] ?? rand(8000, 15000));
                            
                            $stmt->execute([
                                ':airline' => $airline,
                                ':flight_number' => $flightNumber,
                                ':dep_city' => $depCity,
                                ':arr_city' => $arr_city,
                                ':dep_time' => $depTime,
                                ':arr_time' => $arr_time,
                                ':price' => $price
                            ]);
                            $insertedCount++;
                        }
                    }
                    echo "API Ingestion complete. Seeded $insertedCount flights via RapidAPI Skyscanner.\n";
                } else {
                    echo "RapidAPI returned status 200 but response structure was unexpected or empty. Falling back to programmatic data...\n";
                    $insertedCount = seedProgrammaticFlights($pdo);
                }
            } else {
                echo "RapidAPI flight query failed with HTTP status code $httpStatusCode. Error: $curlError\n";
                echo "Falling back to programmatic data generation...\n";
                $insertedCount = seedProgrammaticFlights($pdo);
            }
            
        } else {
            echo "Local CSV not found and RAPIDAPI_KEY is not configured. Running programmatic backup flight seeder...\n";
            $insertedCount = seedProgrammaticFlights($pdo);
        }
    }
    
    echo "=== Flight Seeder Finished! Successfully inserted/synced $insertedCount flights ===\n";
    
} catch (Exception $e) {
    echo "Error during flight seeding: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Seeding fallback logic: Programmatically generate realistic flights from South Africa to major gateways
 */
function seedProgrammaticFlights($pdo) {
    echo "Generating programmatic fallback flight records...\n";
    $airlines = ['Emirates', 'Air France', 'Qatar Airways', 'Singapore Airlines', 'British Airways', 'Lufthansa', 'Delta Air Lines', 'South African Airways', 'KLM', 'Turkish Airlines'];
    $destCities = ['Cape Town', 'London', 'Paris', 'Tokyo', 'New York', 'Rome', 'Madrid', 'Sydney', 'Toronto', 'Berlin'];
    
    $stmt = $pdo->prepare("
        INSERT INTO FLIGHT (Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price)
        VALUES (:airline, :flight_number, :dep_city, :arr_city, :dep_time, :arr_time, :price)
    ");
    
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM FLIGHT 
        WHERE FlightNumber = :flight_number AND DepartureTime = :dep_time
    ");
    
    $count = 0;
    for ($i = 1; $i <= 50; $i++) {
        $airline = $airlines[$i % count($airlines)];
        $flightNo = strtoupper(substr($airline, 0, 2)) . rand(100, 999);
        
        $depCity = 'Johannesburg';
        if ($i % 3 == 0) $depCity = 'Cape Town';
        if ($i % 5 == 0) $depCity = 'Durban';
        
        $arrCity = $destCities[($i - 1) % count($destCities)];
        if ($arrCity === $depCity) {
            $arrCity = 'London';
        }
        
        $departDate = date('Y-m-d H:i:s', strtotime("+ " . ($i * 2 + 1) . " days + " . rand(1, 23) . " hours"));
        $arrivalDate = date('Y-m-d H:i:s', strtotime($departDate . " + " . rand(2, 16) . " hours"));
        $price = rand(1500, 16000) + rand(0, 99) / 100;
        
        // Prevent duplicates
        $checkStmt->execute([':flight_number' => $flightNo, ':dep_time' => $departDate]);
        if ($checkStmt->fetchColumn() > 0) {
            continue;
        }
        
        $stmt->execute([
            ':airline' => $airline,
            ':flight_number' => $flightNo,
            ':dep_city' => $depCity,
            ':arr_city' => $arrCity,
            ':dep_time' => $departDate,
            ':arr_time' => $arrivalDate,
            ':price' => $price
        ]);
        $count++;
    }
    
    return $count;
}
