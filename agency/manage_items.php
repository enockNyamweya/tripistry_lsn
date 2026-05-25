<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify ownership
$stmt = $pdo->prepare('SELECT COUNT(*) FROM TRAVEL_PACKAGE WHERE AgencyID = ? AND PackageID = ?');
$stmt->execute([$_SESSION['user_id'], $packageId]);
if (!$packageId || $stmt->fetchColumn() == 0) {
    echo '<p class="empty-state">Package not found or access denied.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT Title FROM TRAVEL_PACKAGE WHERE PackageID = ?');
$stmt->execute([$packageId]);
$package = $stmt->fetch();

// Handle remove
if (isset($_GET['remove']) && isset($_GET['type'])) {
    $type = $_GET['type'];
    $id = (int)$_GET['remove'];
    $tableMap = [
        'destination' => ['HAS_DESTINATION', 'DestinationID'],
        'flight' => ['INCLUDES_FLIGHT', 'FlightID'],
        'accommodation' => ['INCLUDES_ACCOM', 'AccommodationID'],
        'restaurant' => ['INCLUDES_RESTAURANT', 'RestaurantID'],
        'attraction' => ['INCLUDES_ATTRACTION', 'AttractionID'],
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
$stmt = $pdo->prepare('SELECT d.* FROM DESTINATION d JOIN HAS_DESTINATION v ON d.DestinationID = v.DestinationID WHERE v.PackageID = ?');
$stmt->execute([$packageId]);
$pkgDestinations = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT f.* FROM FLIGHT f JOIN INCLUDES_FLIGHT i ON f.FlightID = i.FlightID WHERE i.PackageID = ?');
$stmt->execute([$packageId]);
$pkgFlights = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.* FROM ACCOMMODATION a JOIN INCLUDES_ACCOM i ON a.AccommodationID = i.AccommodationID WHERE i.PackageID = ?');
$stmt->execute([$packageId]);
$pkgAccommodations = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT r.* FROM RESTAURANT r JOIN INCLUDES_RESTAURANT pr ON r.RestaurantID = pr.RestaurantID WHERE pr.PackageID = ?');
$stmt->execute([$packageId]);
$pkgRestaurants = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT a.* FROM ATTRACTION a JOIN INCLUDES_ATTRACTION pa ON a.AttractionID = pa.AttractionID WHERE pa.PackageID = ?');
$stmt->execute([$packageId]);
$pkgAttractions = $stmt->fetchAll();

// Handle quick add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $type = $_POST['add_type'];
    $id = (int)$_POST['add_id'];
    $insertMap = [
        'destination' => ['HAS_DESTINATION', 'DestinationID'],
        'flight' => ['INCLUDES_FLIGHT', 'FlightID'],
        'accommodation' => ['INCLUDES_ACCOM', 'AccommodationID'],
        'restaurant' => ['INCLUDES_RESTAURANT', 'RestaurantID'],
        'attraction' => ['INCLUDES_ATTRACTION', 'AttractionID'],
    ];
    if (isset($insertMap[$type]) && $id > 0) {
        [$table, $col] = $insertMap[$type];
        $stmt = $pdo->prepare("INSERT IGNORE INTO $table (PackageID, $col) VALUES (?, ?)");
        $stmt->execute([$packageId, $id]);
    }
    header("Location: manage_items.php?id=$packageId&added=1");
    exit;
}

$apiBase = htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES);
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
        ['Destinations', $pkgDestinations, 'destination', 'destinations', 'DestinationID', 'City', 'Country'],
        ['Flights', $pkgFlights, 'flight', 'flights', 'FlightID', 'Airline', 'FlightNumber'],
        ['Accommodations', $pkgAccommodations, 'accommodation', 'accommodations', 'AccommodationID', 'Name', 'Type'],
        ['Restaurants', $pkgRestaurants, 'restaurant', 'restaurants', 'RestaurantID', 'Name', 'CuisineType'],
        ['Attractions', $pkgAttractions, 'attraction', 'attractions', 'AttractionID', 'Name', 'Type'],
    ];

    foreach ($sections as [$label, $current, $type, $apiType, $idCol, $nameCol, $subCol]):
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

            <h3 class="text-muted">Add <?php echo rtrim($label, 's'); ?></h3>
            <div class="available-picker agency-infinite"
                 data-agency-infinite
                 data-api-base="<?php echo $apiBase; ?>"
                 data-resource="available"
                 data-available-type="<?php echo htmlspecialchars($apiType); ?>"
                 data-add-type="<?php echo htmlspecialchars($type); ?>"
                 data-package-id="<?php echo (int)$packageId; ?>"
                 data-list-mode="picker"
                 data-page-size="20"
                 data-empty-message="No more items available to add.">

                <p class="lazy-status" data-agency-status style="display:none"></p>
                <div data-agency-list class="available-picker-list"></div>
                <button type="button" class="btn btn-secondary load-more-btn" data-agency-load-more style="display:none">Load more</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<a href="packages.php" class="btn btn-secondary">Back to Packages</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
