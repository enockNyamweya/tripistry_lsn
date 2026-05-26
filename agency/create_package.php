<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);

    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $durationInput = (int)($_POST['duration'] ?? 0);

    $maxTravellers = (int)($_POST['max_travellers'] ?? 10);
    $isGroupTrip = isset($_POST['is_group_trip']) ? 1 : 0;
    $status = $_POST['status'] ?? 'Active';
    $imageURL = trim($_POST['image_url'] ?? '');

    $destinations = $_POST['destinations'] ?? [];
    $flights = $_POST['flights'] ?? [];
    $accommodations = $_POST['accommodations'] ?? [];
    $restaurants = $_POST['restaurants'] ?? [];
    $attractions = $_POST['attractions'] ?? [];

    if (empty($title) || $price <= 0 || !$startDate) {
        $error = 'Title, price, and start date are required.';
    } else {
        try {
            // Calculate end date from start + duration if end date not provided
            if (!$endDate && $durationInput > 0 && $startDate) {
                $start = new DateTime($startDate);
                $start->modify('+' . ($durationInput - 1) . ' days');
                $endDate = $start->format('Y-m-d');
            }

            // SERVER-SIDE duration (authoritative)
            if ($startDate && $endDate) {
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $duration = $start->diff($end)->days + 1;
            } else {
                $duration = $durationInput > 0 ? $durationInput : 1;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('
                INSERT INTO TRAVEL_PACKAGE
                (Title, Description, Price, DurationDays, IsGroupTrip, ImageURL, Status, AgencyID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');

            $stmt->execute([
                $title,
                $description,
                $price,
                $duration,
                $isGroupTrip,
                $imageURL,
                $status,
                $_SESSION['user_id']
            ]);

            $packageId = $pdo->lastInsertId();

            foreach ($destinations as $did)
                $pdo->prepare('INSERT IGNORE INTO HAS_DESTINATION VALUES (?, ?)')->execute([$packageId, (int)$did]);

            foreach ($flights as $fid)
                $pdo->prepare('INSERT IGNORE INTO INCLUDES_FLIGHT VALUES (?, ?)')->execute([$packageId, (int)$fid]);

            foreach ($accommodations as $aid)
                $pdo->prepare('INSERT IGNORE INTO INCLUDES_ACCOM VALUES (?, ?)')->execute([$packageId, (int)$aid]);

            foreach ($restaurants as $rid)
                $pdo->prepare('INSERT IGNORE INTO INCLUDES_RESTAURANT VALUES (?, ?)')->execute([$packageId, (int)$rid]);

            foreach ($attractions as $aid)
                $pdo->prepare('INSERT IGNORE INTO INCLUDES_ATTRACTION VALUES (?, ?)')->execute([$packageId, (int)$aid]);

            if ($isGroupTrip) {
                $groupName = trim($_POST['group_name'] ?? $title . ' Group');
                $maxP = (int)($_POST['max_participants'] ?? $maxTravellers);

                $pdo->prepare('INSERT INTO GROUP_TRIP (TripName, MaxCapacity, AgencyID, PackageID)
                               VALUES (?, ?, ?, ?)')
                    ->execute([$groupName, $maxP, $_SESSION['user_id'], $packageId]);
            }

            $pdo->commit();

            header('Location: packages.php?created=1');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error creating package: ' . $e->getMessage();
        }
    }
}
?>

<div class="page-container">

<h1><?= basename($_SERVER['PHP_SELF']) === 'create_package.php' ? 'Create New Package' : 'Edit Package' ?></h1>

<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" class="package-form">

<fieldset>
<legend>Package Details</legend>

<div class="form-row">
    <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" required>
    </div>

    <div class="form-group">
        <label>Price (R) *</label>
        <input type="number" name="price" step="0.01" min="1" required>
    </div>
</div>

<div class="form-group">
    <label>Description</label>
    <textarea name="description"></textarea>
</div>

<div class="form-row">

    <div class="form-group">
        <label>Start Date *</label>
        <input type="date" id="start_date" name="start_date" required>
    </div>

    <div class="form-group">
        <label>Duration (days)</label>
        <input type="number" id="duration" name="duration" value="1" min="1">
    </div>

    <div class="form-group">
        <label>End Date</label>
        <input type="date" id="end_date" name="end_date">
        <small class="text-muted">Auto-calculated from start + duration</small>
    </div>

    <div class="form-group">
        <label>Max Travellers</label>
        <input type="number" name="max_travellers" value="10">
    </div>

</div>

<div class="form-row">

    <div class="form-group">
        <label>Image URL</label>
        <input type="url" name="image_url">
    </div>

    <div class="form-group">
        <label>Status</label>
        <select name="status">
            <option>Active</option>
            <option>Draft</option>
            <option>Inactive</option>
        </select>
    </div>

    <div class="form-group checkbox-group">
        <label>
            <input type="checkbox" name="is_group_trip" value="1">
            Group Trip
        </label>
    </div>

</div>

</fieldset>

<button class="btn btn-primary">Create Package</button>

</form>

</div>

<script src="<?php echo BASE_URL; ?>/assets/js/package.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
