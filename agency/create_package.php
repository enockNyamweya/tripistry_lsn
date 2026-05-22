<?php include __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../includes/PackageService.php';
requireAgency();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $duration = (int)($_POST['duration'] ?? 1);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $maxTravellers = (int)($_POST['max_travellers'] ?? 10);
    $isGroupTrip = isset($_POST['is_group_trip']) ? 1 : 0;
    $status = $_POST['status'] ?? 'Active';
    $imageURL = trim($_POST['image_url'] ?? '');
    $destinations = $_POST['destinations'] ?? [];
    $flights = $_POST['flights'] ?? [];
    $accommodations = $_POST['accommodations'] ?? [];
    $restaurants = $_POST['restaurants'] ?? [];
    $attractions = $_POST['attractions'] ?? [];

    if (empty($title) || $price <= 0) {
        $error = 'Title and price are required.';
    } else {
        // Prepare data array for the service layer
        $packageData = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'duration' => $duration,
            'is_group_trip' => $isGroupTrip,
            'status' => $status,
            'image_url' => $imageURL,
            'destinations' => $destinations,
            'flights' => $flights,
            'accommodations' => $accommodations,
            'restaurants' => $restaurants,
            'attractions' => $attractions,
            'group_trip_data' => []
        ];

        // Gather group trip details if applicable
        if ($isGroupTrip) {
            $groupName = trim($_POST['group_name'] ?? '');
            $packageData['group_trip_data'] = [
                'name' => empty($groupName) ? $title . ' Group' : $groupName,
                'max_participants' => (int)($_POST['max_participants'] ?? $maxTravellers)
            ];
        }

        // Delegate to the Data Access Layer (Service)
        $packageService = new PackageService($pdo);
        $result = $packageService->createPackage($packageData, $_SESSION['user_id']);
        
        if ($result) {
            header('Location: packages.php?created=1');
            exit;
        } else {
            $error = 'An error occurred while creating the package. Please try again.';
        }
    }
}

// Fetch all destinations, flights, accommodations etc for selection
$destinations = $pdo->query('SELECT * FROM DESTINATION ORDER BY Country, City')->fetchAll();
$flights = $pdo->query('SELECT * FROM FLIGHT ORDER BY DepartureTime')->fetchAll();
$accommodations = $pdo->query('SELECT * FROM ACCOMMODATION ORDER BY Name')->fetchAll();
$restaurants = $pdo->query('SELECT * FROM RESTAURANT ORDER BY Name')->fetchAll();
$attractions = $pdo->query('SELECT * FROM ATTRACTION ORDER BY Name')->fetchAll();
?>

<h1>Create New Package</h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" action="" class="package-form">
    <fieldset>
        <legend>Package Details</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="price">Price (R) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="duration">Duration (days)</label>
                <input type="number" id="duration" name="duration" value="1" min="1">
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date">
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date">
            </div>
            <div class="form-group">
                <label for="max_travellers">Max Travellers</label>
                <input type="number" id="max_travellers" name="max_travellers" value="10" min="1">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="url" id="image_url" name="image_url" placeholder="https://...">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="Active">Active</option>
                    <option value="Draft">Draft</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" id="is_group_trip" name="is_group_trip" value="1" onchange="toggleGroupFields()">
                    This is a Group Trip
                </label>
            </div>
        </div>
    </fieldset>

    <div id="groupTripFields" style="display:none;">
        <fieldset>
            <legend>Group Trip Settings</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="group_name">Group Name</label>
                    <input type="text" id="group_name" name="group_name">
                </div>
                <div class="form-group">
                    <label for="max_participants">Max Capacity</label>
                    <input type="number" id="max_participants" name="max_participants" value="20" min="2">
                </div>
            </div>
        </fieldset>
    </div>

    <fieldset>
        <legend>Associated Entities</legend>
        <div class="form-row">
            <div class="form-group">
                <label>Destinations</label>
                <div class="checkbox-list">
                    <?php foreach ($destinations as $d): ?>
                        <label><input type="checkbox" name="destinations[]" value="<?php echo $d['DestinationID']; ?>">
                            <?php echo htmlspecialchars($d['City'] . ', ' . $d['Country']); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Flights</label>
                <div class="checkbox-list">
                    <?php foreach ($flights as $f): ?>
                        <label><input type="checkbox" name="flights[]" value="<?php echo $f['FlightID']; ?>">
                            <?php echo htmlspecialchars($f['Airline'] . ' #' . $f['FlightNumber'] . ' (' . $f['DepartureCity'] . '→' . $f['ArrivalCity'] . ')'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Accommodations</label>
                <div class="checkbox-list">
                    <?php foreach ($accommodations as $a): ?>
                        <label><input type="checkbox" name="accommodations[]" value="<?php echo $a['AccommodationID']; ?>">
                            <?php echo htmlspecialchars($a['Name'] . ' (' . $a['Type'] . ', ' . $a['StarRating'] . '★)'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Restaurants</label>
                <div class="checkbox-list">
                    <?php foreach ($restaurants as $r): ?>
                        <label><input type="checkbox" name="restaurants[]" value="<?php echo $r['RestaurantID']; ?>">
                            <?php echo htmlspecialchars($r['Name'] . ' (' . $r['CuisineType'] . ')'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Attractions</label>
                <div class="checkbox-list">
                    <?php foreach ($attractions as $a): ?>
                        <label><input type="checkbox" name="attractions[]" value="<?php echo $a['AttractionID']; ?>">
                            <?php echo htmlspecialchars($a['Name'] . ' (' . $a['Type'] . ')'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </fieldset>

    <button type="submit" class="btn btn-primary btn-lg">Create Package</button>
</form>

<script>
function toggleGroupFields() {
    document.getElementById('groupTripFields').style.display =
        document.getElementById('is_group_trip').checked ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
