<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

// Verify ownership
$stmt = $pdo->prepare('SELECT COUNT(*) FROM CURATES WHERE UserID = ? AND PackageID = ?');
$stmt->execute([$_SESSION['user_id'], $packageId]);
if (!$packageId || $stmt->fetchColumn() == 0) {
    echo '<p class="empty-state">Package not found or access denied.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Get existing package
$stmt = $pdo->prepare('SELECT * FROM PACKAGE WHERE PackageID = ?');
$stmt->execute([$packageId]);
$package = $stmt->fetch();

// Get existing associations
$assoc = [];
$stmt = $pdo->prepare('SELECT DestinationID FROM VISITS WHERE PackageID = ?');
$stmt->execute([$packageId]);
$assoc['destinations'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT FlightID FROM INCLUDES_FLIGHT WHERE PackageID = ?');
$stmt->execute([$packageId]);
$assoc['flights'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT AccomodationID FROM INCLUDES_STAY WHERE PackageID = ?');
$stmt->execute([$packageId]);
$assoc['accommodations'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT RestaurantID FROM PACKAGE_RESTAURANT WHERE PackageID = ?');
$stmt->execute([$packageId]);
$assoc['restaurants'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT AttractionID FROM PACKAGE_ATTRACTION WHERE PackageID = ?');
$stmt->execute([$packageId]);
$assoc['attractions'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

// Get existing group trip
$stmt = $pdo->prepare('SELECT * FROM GROUP_TRIP WHERE PackageID = ?');
$stmt->execute([$packageId]);
$groupTrip = $stmt->fetch();

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

    if (empty($title) || $price <= 0) {
        $error = 'Title and price are required.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE PACKAGE SET Title=?, Description=?, Price=?, DurationDays=?, StartDate=?, EndDate=?, MaxTravellers=?, IsGroupTrip=?, ImageURL=?, Status=? WHERE PackageID=?');
            $stmt->execute([$title, $description, $price, $duration, $startDate, $endDate, $maxTravellers, $isGroupTrip, $imageURL, $status, $packageId]);

            // Rebuild associations - delete and re-insert
            $tables = ['VISITS', 'INCLUDES_FLIGHT', 'INCLUDES_STAY', 'PACKAGE_RESTAURANT', 'PACKAGE_ATTRACTION'];
            $columns = ['DestinationID', 'FlightID', 'AccomodationID', 'RestaurantID', 'AttractionID'];
            $postKeys = ['destinations', 'flights', 'accommodations', 'restaurants', 'attractions'];

            foreach ($tables as $i => $table) {
                if ($table === 'INCLUDES_FLIGHT') {
                    $stmt = $pdo->prepare("UPDATE $table SET PackageID = NULL WHERE PackageID = ?");
                    $stmt->execute([$packageId]);
                    $stmt = $pdo->prepare("DELETE FROM $table WHERE PackageID = ?");
                    $stmt->execute([$packageId]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM $table WHERE PackageID = ?");
                    $stmt->execute([$packageId]);
                }
                $values = $_POST[$postKeys[$i]] ?? [];
                foreach ($values as $vid) {
                    if ($table === 'INCLUDES_FLIGHT') {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO $table (PackageID, $columns[$i]) VALUES (?, ?)");
                    } else {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO $table (PackageID, $columns[$i]) VALUES (?, ?)");
                    }
                    $stmt->execute([$packageId, (int)$vid]);
                }
            }

            // Update group trip
            if ($isGroupTrip) {
                $groupName = trim($_POST['group_name'] ?? $title . ' Group');
                $minP = (int)($_POST['min_participants'] ?? 2);
                $maxP = (int)($_POST['max_participants'] ?? 20);
                $depDate = $_POST['group_departure'] ?? $startDate;
                $retDate = $_POST['group_return'] ?? $endDate;
                if ($groupTrip) {
                    $stmt = $pdo->prepare('UPDATE GROUP_TRIP SET GroupName=?, MinParticipants=?, MaxParticipants=?, DepartureDate=?, ReturnDate=? WHERE GroupTripID=?');
                    $stmt->execute([$groupName, $minP, $maxP, $depDate, $retDate, $groupTrip['GroupTripID']]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO GROUP_TRIP (PackageID, GroupName, MinParticipants, MaxParticipants, Status, DepartureDate, ReturnDate) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$packageId, $groupName, $minP, $maxP, 'Open', $depDate, $retDate]);
                }
            }

            $pdo->commit();
            header('Location: packages.php?updated=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }

    // Refresh package data after failed update
    $stmt = $pdo->prepare('SELECT * FROM PACKAGE WHERE PackageID = ?');
    $stmt->execute([$packageId]);
    $package = $stmt->fetch();
}

$destinations = $pdo->query('SELECT * FROM DESTINATION ORDER BY Country, City')->fetchAll();
$flights = $pdo->query('SELECT * FROM FLIGHT ORDER BY DepartureTime')->fetchAll();
$accommodations = $pdo->query('SELECT * FROM ACCOMODATION ORDER BY Name')->fetchAll();
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
                <input type="number" id="duration" name="duration" value="<?php echo $package['DurationDays']; ?>" min="1" onchange="autoEndDate()">
            </div>
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $package['StartDate']; ?>" onchange="autoEndDate()">
            </div>
            <div class="form-group">
                <label for="end_date">End Date <small>(auto-calculated)</small></label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $package['EndDate']; ?>">
            </div>
            <div class="form-group">
                <label for="max_travellers">Max Travellers</label>
                <input type="number" id="max_travellers" name="max_travellers" value="<?php echo $package['MaxTravellers']; ?>" min="1">
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
                    <input type="text" id="group_name" name="group_name" value="<?php echo htmlspecialchars($groupTrip['GroupName'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="min_participants">Min Participants</label>
                    <input type="number" id="min_participants" name="min_participants" value="<?php echo $groupTrip['MinParticipants'] ?? 2; ?>" min="2">
                </div>
                <div class="form-group">
                    <label for="max_participants">Max Participants</label>
                    <input type="number" id="max_participants" name="max_participants" value="<?php echo $groupTrip['MaxParticipants'] ?? 20; ?>" min="2">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="group_departure">Departure Date</label>
                    <input type="date" id="group_departure" name="group_departure" value="<?php echo $groupTrip['DepartureDate'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="group_return">Return Date</label>
                    <input type="date" id="group_return" name="group_return" value="<?php echo $groupTrip['ReturnDate'] ?? ''; ?>">
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
                        <label><input type="checkbox" name="accommodations[]" value="<?php echo $a['AccomodationID']; ?>" <?php echo in_array($a['AccomodationID'], $assoc['accommodations']) ? 'checked' : ''; ?>>
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
function autoEndDate() {
    var startEl = document.getElementById('start_date');
    var daysEl = document.getElementById('duration');
    var endEl = document.getElementById('end_date');
    if (startEl && daysEl && endEl) {
        var start = new Date(startEl.value + 'T00:00:00');
        var days = parseInt(daysEl.value) || 1;
        if (!isNaN(start.getTime()) && startEl.value) {
            start.setDate(start.getDate() + days - 1);
            endEl.value = start.toISOString().split('T')[0];
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
