<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify ownership
$stmt = $pdo->prepare('SELECT COUNT(*) FROM CURATES WHERE UserID = ? AND PackageID = ?');
$stmt->execute([$_SESSION['user_id'], $packageId]);
if (!$packageId || $stmt->fetchColumn() == 0) {
    echo '<p class="empty-state">Package not found or access denied.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT Title FROM PACKAGE WHERE PackageID = ?');
$stmt->execute([$packageId]);
$package = $stmt->fetch();

// Handle remove
if (isset($_GET['remove']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $id = (int)$_GET['remove'];
    $tableMap = [
        'destination' => ['VISITS', 'DestinationID'],
        'flight' => ['INCLUDES_FLIGHT', 'FlightID'],
        'accommodation' => ['INCLUDES_STAY', 'AccomodationID'],
        'restaurant' => ['PACKAGE_RESTAURANT', 'RestaurantID'],
        'attraction' => ['PACKAGE_ATTRACTION', 'AttractionID'],
    ];
    if (isset($tableMap[$type])) {
        [$table, $col] = $tableMap[$type];
        $stmt = $pdo->prepare("DELETE FROM $table WHERE PackageID = ? AND $col = ?");
        $stmt->execute([$packageId, $id]);
    }
    header("Location: manage_items.php?id=$packageId&removed=1");
    exit;
}

// Get all associations
$stmt = $pdo->prepare('SELECT d.* FROM DESTINATION d JOIN VISITS v ON d.DestinationID = v.DestinationID WHERE v.PackageID = ?');
$stmt->execute([$packageId]);
$pkgDestinations = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT f.* FROM FLIGHT f JOIN INCLUDES_FLIGHT i ON f.FlightID = i.FlightID WHERE i.PackageID = ?');
$stmt->execute([$packageId]);
$pkgFlights = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.* FROM ACCOMODATION a JOIN INCLUDES_STAY i ON a.AccomodationID = i.AccomodationID WHERE i.PackageID = ?');
$stmt->execute([$packageId]);
$pkgAccommodations = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT r.* FROM RESTAURANT r JOIN PACKAGE_RESTAURANT pr ON r.RestaurantID = pr.RestaurantID WHERE pr.PackageID = ?');
$stmt->execute([$packageId]);
$pkgRestaurants = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.* FROM ATTRACTION a JOIN PACKAGE_ATTRACTION pa ON a.AttractionID = pa.AttractionID WHERE pa.PackageID = ?');
$stmt->execute([$packageId]);
$pkgAttractions = $stmt->fetchAll();

// Handle quick add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $type = $_POST['add_type'];
    $id = (int)$_POST['add_id'];
    $insertMap = [
        'destination' => ['VISITS', 'DestinationID'],
        'flight' => ['INCLUDES_FLIGHT', 'FlightID'],
        'accommodation' => ['INCLUDES_STAY', 'AccomodationID'],
        'restaurant' => ['PACKAGE_RESTAURANT', 'RestaurantID'],
        'attraction' => ['PACKAGE_ATTRACTION', 'AttractionID'],
    ];
    if (isset($insertMap[$type]) && $id > 0) {
        [$table, $col] = $insertMap[$type];
        $stmt = $pdo->prepare("INSERT IGNORE INTO $table (PackageID, $col) VALUES (?, ?)");
        $stmt->execute([$packageId, $id]);
    }
    header("Location: manage_items.php?id=$packageId&added=1");
    exit;
}

// Available items not yet associated
$stmt = $pdo->prepare('SELECT * FROM DESTINATION WHERE DestinationID NOT IN (SELECT DestinationID FROM VISITS WHERE PackageID = ?)');
$stmt->execute([$packageId]);
$availableDest = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM FLIGHT WHERE FlightID NOT IN (SELECT FlightID FROM INCLUDES_FLIGHT WHERE PackageID = ?)');
$stmt->execute([$packageId]);
$availableFlights = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM ACCOMODATION WHERE AccomodationID NOT IN (SELECT AccomodationID FROM INCLUDES_STAY WHERE PackageID = ?)');
$stmt->execute([$packageId]);
$availableAcc = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM RESTAURANT WHERE RestaurantID NOT IN (SELECT RestaurantID FROM PACKAGE_RESTAURANT WHERE PackageID = ?)');
$stmt->execute([$packageId]);
$availableRest = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM ATTRACTION WHERE AttractionID NOT IN (SELECT AttractionID FROM PACKAGE_ATTRACTION WHERE PackageID = ?)');
$stmt->execute([$packageId]);
$availableAttr = $stmt->fetchAll();
?>

<h1>Manage Items: <?php echo htmlspecialchars($package['Title']); ?></h1>

<?php if (isset($_GET['removed'])): ?>
    <div class="alert alert-success">Item removed from package.</div>
<?php endif; ?>
<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success">Item added to package.</div>
<?php endif; ?>

<div class="manage-sections">
    <?php
    $sections = [
        ['Destinations', $pkgDestinations, $availableDest, 'destination', 'DestinationID', 'City', 'Country'],
        ['Flights', $pkgFlights, $availableFlights, 'flight', 'FlightID', 'Airline', 'FlightNumber'],
        ['Accommodations', $pkgAccommodations, $availableAcc, 'accommodation', 'AccomodationID', 'Name', 'Type'],
        ['Restaurants', $pkgRestaurants, $availableRest, 'restaurant', 'RestaurantID', 'Name', 'CuisineType'],
        ['Attractions', $pkgAttractions, $availableAttr, 'attraction', 'AttractionID', 'Name', 'Type'],
    ];

    foreach ($sections as [$label, $current, $available, $type, $idCol, $nameCol, $subCol]):
    ?>
        <div class="manage-section">
            <h2><?php echo $label; ?></h2>
            <?php if ($current): ?>
                <ul class="item-list">
                    <?php foreach ($current as $item): ?>
                        <li>
                            <?php echo htmlspecialchars($item[$nameCol] . ' (' . ($item[$subCol] ?? '') . ')'); ?>
                            <a href="?id=<?php echo $packageId; ?>&remove=<?php echo $item[$idCol]; ?>&type=<?php echo $type; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')">Remove</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">No <?php echo strtolower($label); ?> associated.</p>
            <?php endif; ?>

            <?php if ($available): ?>
                <form method="POST" action="" class="inline-add-form">
                    <input type="hidden" name="add_type" value="<?php echo $type; ?>">
                    <select name="add_id" required>
                        <option value="">Add <?php echo rtrim($label, 's'); ?>...</option>
                        <?php foreach ($available as $item): ?>
                            <option value="<?php echo $item[$idCol]; ?>">
                                <?php echo htmlspecialchars($item[$nameCol] . ($item[$subCol] ? ' (' . $item[$subCol] . ')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<a href="packages.php" class="btn btn-secondary">Back to Packages</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
