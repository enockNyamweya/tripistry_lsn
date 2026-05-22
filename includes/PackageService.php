<?php
/**
 * PackageService.php
 * 
 * Handles all database operations related to Travel Packages.
 * This class ensures a strict separation of concerns between the presentation layer
 * (HTML forms) and the data access layer (SQL Queries).
 */

class PackageService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new travel package and all its associated junction records.
     * Uses a database transaction to ensure atomicity.
     * 
     * @param array $data The sanitized input data for the package.
     * @param int $agencyId The UserID of the agency creating the package.
     * @return int|false The ID of the newly created package, or false on failure.
     */
    public function createPackage($data, $agencyId) {
        $this->pdo->beginTransaction();
        try {
            // 1. Insert into the main TRAVEL_PACKAGE table
            $stmt = $this->pdo->prepare('
                INSERT INTO TRAVEL_PACKAGE 
                (Title, Description, Price, DurationDays, IsGroupTrip, Status, ImageURL, AgencyID) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['price'],
                $data['duration'],
                $data['is_group_trip'],
                $data['status'],
                $data['image_url'],
                $agencyId
            ]);
            $packageId = $this->pdo->lastInsertId();

            // 2. Insert Associations (M:N Junction Tables)
            $this->linkDestinations($packageId, $data['destinations']);
            $this->linkFlights($packageId, $data['flights']);
            $this->linkAccommodations($packageId, $data['accommodations']);
            $this->linkRestaurants($packageId, $data['restaurants']);
            $this->linkAttractions($packageId, $data['attractions']);

            // 3. Handle Group Trip specific logic
            if ($data['is_group_trip']) {
                $this->createGroupTrip($packageId, $agencyId, $data['group_trip_data']);
            }

            $this->pdo->commit();
            return $packageId;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // In a production environment, log the error here.
            error_log("Package Creation Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper methods for M:N Junctions
     * Using INSERT IGNORE to gracefully handle duplicates if any occur.
     */

    private function linkDestinations($packageId, $destinations) {
        if (empty($destinations)) return;
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO HAS_DESTINATION (PackageID, DestinationID) VALUES (?, ?)');
        foreach ($destinations as $did) {
            $stmt->execute([$packageId, (int)$did]);
        }
    }

    private function linkFlights($packageId, $flights) {
        if (empty($flights)) return;
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO INCLUDES_FLIGHT (PackageID, FlightID) VALUES (?, ?)');
        foreach ($flights as $fid) {
            $stmt->execute([$packageId, (int)$fid]);
        }
    }

    private function linkAccommodations($packageId, $accommodations) {
        if (empty($accommodations)) return;
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO INCLUDES_ACCOM (PackageID, AccommodationID) VALUES (?, ?)');
        foreach ($accommodations as $aid) {
            $stmt->execute([$packageId, (int)$aid]);
        }
    }

    private function linkRestaurants($packageId, $restaurants) {
        if (empty($restaurants)) return;
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO INCLUDES_RESTAURANT (PackageID, RestaurantID) VALUES (?, ?)');
        foreach ($restaurants as $rid) {
            $stmt->execute([$packageId, (int)$rid]);
        }
    }

    private function linkAttractions($packageId, $attractions) {
        if (empty($attractions)) return;
        $stmt = $this->pdo->prepare('INSERT IGNORE INTO INCLUDES_ATTRACTION (PackageID, AttractionID) VALUES (?, ?)');
        foreach ($attractions as $aid) {
            $stmt->execute([$packageId, (int)$aid]);
        }
    }

    /**
     * Creates a specialized Group Trip record.
     */
    private function createGroupTrip($packageId, $agencyId, $groupData) {
        $stmt = $this->pdo->prepare('
            INSERT INTO GROUP_TRIP 
            (TripName, MaxCapacity, AgencyID, PackageID) 
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([
            $groupData['name'],
            $groupData['max_participants'],
            $agencyId,
            $packageId
        ]);
    }

    /**
     * Retrieves a complete package with all its junction table associations.
     */
    public function getPackageWithAssociations($packageId, $agencyId) {
        $stmt = $this->pdo->prepare('SELECT * FROM TRAVEL_PACKAGE WHERE PackageID = ? AND AgencyID = ?');
        $stmt->execute([$packageId, $agencyId]);
        $package = $stmt->fetch();

        if (!$package) return null;

        // Fetch associated IDs
        $package['destinations'] = $this->pdo->query("SELECT DestinationID FROM HAS_DESTINATION WHERE PackageID = $packageId")->fetchAll(PDO::FETCH_COLUMN);
        $package['flights'] = $this->pdo->query("SELECT FlightID FROM INCLUDES_FLIGHT WHERE PackageID = $packageId")->fetchAll(PDO::FETCH_COLUMN);
        $package['accommodations'] = $this->pdo->query("SELECT AccommodationID FROM INCLUDES_ACCOM WHERE PackageID = $packageId")->fetchAll(PDO::FETCH_COLUMN);
        $package['restaurants'] = $this->pdo->query("SELECT RestaurantID FROM INCLUDES_RESTAURANT WHERE PackageID = $packageId")->fetchAll(PDO::FETCH_COLUMN);
        $package['attractions'] = $this->pdo->query("SELECT AttractionID FROM INCLUDES_ATTRACTION WHERE PackageID = $packageId")->fetchAll(PDO::FETCH_COLUMN);

        return $package;
    }

    /**
     * Updates an existing travel package and completely replaces its associations.
     */
    public function updatePackage($packageId, $data, $agencyId) {
        $this->pdo->beginTransaction();
        try {
            // 1. Update main record
            $stmt = $this->pdo->prepare('
                UPDATE TRAVEL_PACKAGE 
                SET Title = ?, Description = ?, Price = ?, DurationDays = ?, Status = ?, ImageURL = ?
                WHERE PackageID = ? AND AgencyID = ?
            ');
            $stmt->execute([
                $data['title'], $data['description'], $data['price'],
                $data['duration'], $data['status'], $data['image_url'],
                $packageId, $agencyId
            ]);

            // 2. Clear old associations
            $this->pdo->exec("DELETE FROM HAS_DESTINATION WHERE PackageID = $packageId");
            $this->pdo->exec("DELETE FROM INCLUDES_FLIGHT WHERE PackageID = $packageId");
            $this->pdo->exec("DELETE FROM INCLUDES_ACCOM WHERE PackageID = $packageId");
            $this->pdo->exec("DELETE FROM INCLUDES_RESTAURANT WHERE PackageID = $packageId");
            $this->pdo->exec("DELETE FROM INCLUDES_ATTRACTION WHERE PackageID = $packageId");

            // 3. Insert new associations
            $this->linkDestinations($packageId, $data['destinations']);
            $this->linkFlights($packageId, $data['flights']);
            $this->linkAccommodations($packageId, $data['accommodations']);
            $this->linkRestaurants($packageId, $data['restaurants']);
            $this->linkAttractions($packageId, $data['attractions']);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Package Update Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Map of logical item types to their database tables and ID columns.
     */
    private $tableMap = [
        'destination' => ['HAS_DESTINATION', 'DestinationID'],
        'flight' => ['INCLUDES_FLIGHT', 'FlightID'],
        'accommodation' => ['INCLUDES_ACCOM', 'AccommodationID'],
        'restaurant' => ['INCLUDES_RESTAURANT', 'RestaurantID'],
        'attraction' => ['INCLUDES_ATTRACTION', 'AttractionID'],
    ];

    /**
     * Removes a single associated item from a package.
     */
    public function removeItemFromPackage($packageId, $type, $itemId) {
        if (!isset($this->tableMap[$type])) return false;
        [$table, $col] = $this->tableMap[$type];
        
        $stmt = $this->pdo->prepare("DELETE FROM $table WHERE PackageID = ? AND $col = ?");
        return $stmt->execute([$packageId, $itemId]);
    }

    /**
     * Adds a single associated item to a package.
     */
    public function addItemToPackage($packageId, $type, $itemId) {
        if (!isset($this->tableMap[$type])) return false;
        [$table, $col] = $this->tableMap[$type];
        
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO $table (PackageID, $col) VALUES (?, ?)");
        return $stmt->execute([$packageId, $itemId]);
    }

    /**
     * Retrieves current and available items for a package to power the manage_items UI.
     */
    public function getPackageItemsData($packageId) {
        $data = [];
        
        // Destinations
        $data['pkgDestinations'] = $this->pdo->query("SELECT d.* FROM DESTINATION d JOIN HAS_DESTINATION v ON d.DestinationID = v.DestinationID WHERE v.PackageID = $packageId")->fetchAll();
        $data['availableDest'] = $this->pdo->query("SELECT * FROM DESTINATION WHERE DestinationID NOT IN (SELECT DestinationID FROM HAS_DESTINATION WHERE PackageID = $packageId)")->fetchAll();
        
        // Flights
        $data['pkgFlights'] = $this->pdo->query("SELECT f.* FROM FLIGHT f JOIN INCLUDES_FLIGHT i ON f.FlightID = i.FlightID WHERE i.PackageID = $packageId")->fetchAll();
        $data['availableFlights'] = $this->pdo->query("SELECT * FROM FLIGHT WHERE FlightID NOT IN (SELECT FlightID FROM INCLUDES_FLIGHT WHERE PackageID = $packageId)")->fetchAll();
        
        // Accommodations
        $data['pkgAccommodations'] = $this->pdo->query("SELECT a.* FROM ACCOMMODATION a JOIN INCLUDES_ACCOM i ON a.AccommodationID = i.AccommodationID WHERE i.PackageID = $packageId")->fetchAll();
        $data['availableAcc'] = $this->pdo->query("SELECT * FROM ACCOMMODATION WHERE AccommodationID NOT IN (SELECT AccommodationID FROM INCLUDES_ACCOM WHERE PackageID = $packageId)")->fetchAll();
        
        // Restaurants
        $data['pkgRestaurants'] = $this->pdo->query("SELECT r.* FROM RESTAURANT r JOIN INCLUDES_RESTAURANT pr ON r.RestaurantID = pr.RestaurantID WHERE pr.PackageID = $packageId")->fetchAll();
        $data['availableRest'] = $this->pdo->query("SELECT * FROM RESTAURANT WHERE RestaurantID NOT IN (SELECT RestaurantID FROM INCLUDES_RESTAURANT WHERE PackageID = $packageId)")->fetchAll();
        
        // Attractions
        $data['pkgAttractions'] = $this->pdo->query("SELECT a.* FROM ATTRACTION a JOIN INCLUDES_ATTRACTION pa ON a.AttractionID = pa.AttractionID WHERE pa.PackageID = $packageId")->fetchAll();
        $data['availableAttr'] = $this->pdo->query("SELECT * FROM ATTRACTION WHERE AttractionID NOT IN (SELECT AttractionID FROM INCLUDES_ATTRACTION WHERE PackageID = $packageId)")->fetchAll();
        
        return $data;
    }
}
