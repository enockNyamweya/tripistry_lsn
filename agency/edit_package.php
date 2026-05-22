<?php 
include __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../includes/PackageService.php';
requireAgency();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$agencyId = $_SESSION['user_id'];
$packageService = new PackageService($pdo);

// 1. Fetch package and its associations via the Service Layer
$package = $packageService->getPackageWithAssociations($packageId, $agencyId);

if (!$package) {
    echo '<p class="empty-state">Package not found or access denied.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$assoc = [
    'destinations' => $package['destinations'],
    'flights' => $package['flights'],
    'accommodations' => $package['accommodations'],
    'restaurants' => $package['restaurants'],
    'attractions' => $package['attractions']
];

// Get existing group trip (Optional logic still done here or in service, kept simple for now)
$stmt = $pdo->prepare('SELECT * FROM GROUP_TRIP WHERE PackageID = ? AND AgencyID = ?');
$stmt->execute([$packageId, $agencyId]);
$groupTrip = $stmt->fetch();

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    if (empty($title) || $price <= 0) {
        $error = 'Title and price are required.';
    } else {
        $updateData = [
            'title' => $title,
            'description' => trim($_POST['description'] ?? ''),
            'price' => $price,
            'duration' => (int)($_POST['duration'] ?? 1),
            'status' => $_POST['status'] ?? 'Active',
            'image_url' => trim($_POST['image_url'] ?? ''),
            'destinations' => $_POST['destinations'] ?? [],
            'flights' => $_POST['flights'] ?? [],
            'accommodations' => $_POST['accommodations'] ?? [],
            'restaurants' => $_POST['restaurants'] ?? [],
            'attractions' => $_POST['attractions'] ?? []
        ];

        // Group Trip Update Logic
        $isGroupTrip = isset($_POST['is_group_trip']) ? 1 : 0;
        if ($isGroupTrip) {
            $groupName = trim($_POST['group_name'] ?? $title . ' Group');
            $maxP = (int)($_POST['max_participants'] ?? 20);
            
            if ($groupTrip) {
                $stmt = $pdo->prepare('UPDATE GROUP_TRIP SET TripName = ?, MaxCapacity = ? WHERE GroupTripID = ?');
                $stmt->execute([$groupName, $maxP, $groupTrip['GroupTripID']]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO GROUP_TRIP (PackageID, AgencyID, TripName, MaxCapacity) VALUES (?, ?, ?, ?)');
                $stmt->execute([$packageId, $agencyId, $groupName, $maxP]);
            }
        } else {
            if ($groupTrip) {
                $stmt = $pdo->prepare('DELETE FROM GROUP_TRIP WHERE GroupTripID = ?');
                $stmt->execute([$groupTrip['GroupTripID']]);
            }
        }

        // Delegate main update to Service
        if ($packageService->updatePackage($packageId, $updateData, $agencyId)) {
            // Update IsGroupTrip flag directly since it's not in the main update array yet
            $stmt = $pdo->prepare('UPDATE TRAVEL_PACKAGE SET IsGroupTrip=? WHERE PackageID=?');
            $stmt->execute([$isGroupTrip, $packageId]);
            
            header('Location: packages.php?updated=1');
            exit;
        } else {
            $error = 'Error updating package.';
        }
    }

    // Refresh package data if update failed
    $package = $packageService->getPackageWithAssociations($packageId, $agencyId);
}

$destinations = $pdo->query('SELECT * FROM DESTINATION ORDER BY Country, City')->fetchAll();
$flights = $pdo->query('SELECT * FROM FLIGHT ORDER BY DepartureTime')->fetchAll();
$accommodations = $pdo->query('SELECT * FROM ACCOMMODATION ORDER BY Name')->fetchAll();
$restaurants = $pdo->query('SELECT * FROM RESTAURANT ORDER BY Name')->fetchAll();
$attractions = $pdo->query('SELECT * FROM ATTRACTION ORDER BY Name')->fetchAll();
?>

<h1>Edit Package: <?php echo htmlspecialchars($package['Title']); ?></h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" action="" class="package-form">
    <fieldset>
        <legend>Package Details</legend>
        <div class="form-row">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($package['Title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Price (R) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $package['Price']; ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($package['Description']); ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="duration">Duration (days)</label>
                <input type="number" id="duration" name="duration" value="<?php echo $package['DurationDays']; ?>" min="1">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="image_url">Image URL</label>
                <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($package['ImageURL']); ?>">
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="Active" <?php echo $package['Status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Draft" <?php echo $package['Status'] === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="Inactive" <?php echo $package['Status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" id="is_group_trip" name="is_group_trip" value="1" <?php echo $package['IsGroupTrip'] ? 'checked' : ''; ?> onchange="toggleGroupFields()">
                    This is a Group Trip
                </label>
            </div>
        </div>
    </fieldset>

    <div id="groupTripFields" style="<?php echo $package['IsGroupTrip'] ? 'display:block;' : 'display:none;'; ?>">
        <fieldset>
            <legend>Group Trip Settings</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="group_name">Group Name</label>
                    <input type="text" id="group_name" name="group_name" value="<?php echo htmlspecialchars($groupTrip['TripName'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="max_participants">Max Capacity</label>
                    <input type="number" id="max_participants" name="max_participants" value="<?php echo $groupTrip['MaxCapacity'] ?? 20; ?>" min="2">
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
                        <label><input type="checkbox" name="destinations[]" value="<?php echo $d['DestinationID']; ?>" <?php echo in_array($d['DestinationID'], $assoc['destinations']) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($d['City'] . ', ' . $d['Country']); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Flights</label>
                <div class="checkbox-list">
                    <?php foreach ($flights as $f): ?>
                        <label><input type="checkbox" name="flights[]" value="<?php echo $f['FlightID']; ?>" <?php echo in_array($f['FlightID'], $assoc['flights']) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($f['Airline'] . ' #' . $f['FlightNumber']); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Accommodations</label>
                <div class="checkbox-list">
                    <?php foreach ($accommodations as $a): ?>
                        <label><input type="checkbox" name="accommodations[]" value="<?php echo $a['AccommodationID']; ?>" <?php echo in_array($a['AccommodationID'], $assoc['accommodations']) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($a['Name'] . ' (' . $a['Type'] . ')'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Restaurants</label>
                <div class="checkbox-list">
                    <?php foreach ($restaurants as $r): ?>
                        <label><input type="checkbox" name="restaurants[]" value="<?php echo $r['RestaurantID']; ?>" <?php echo in_array($r['RestaurantID'], $assoc['restaurants']) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($r['Name'] . ' (' . $r['CuisineType'] . ')'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label>Attractions</label>
                <div class="checkbox-list">
                    <?php foreach ($attractions as $a): ?>
                        <label><input type="checkbox" name="attractions[]" value="<?php echo $a['AttractionID']; ?>" <?php echo in_array($a['AttractionID'], $assoc['attractions']) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($a['Name'] . ' (' . $a['Type'] . ')'); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </fieldset>

    <button type="submit" class="btn btn-primary btn-lg">Update Package</button>
    <a href="packages.php" class="btn btn-secondary">Cancel</a>
</form>

<script>
function toggleGroupFields() {
    document.getElementById('groupTripFields').style.display =
        document.getElementById('is_group_trip').checked ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
