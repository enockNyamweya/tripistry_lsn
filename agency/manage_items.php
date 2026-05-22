<?php 
include __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../includes/PackageService.php';
requireAgency();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$agencyId = $_SESSION['user_id'];
$packageService = new PackageService($pdo);

// 1. Fetch package and Verify ownership
$stmt = $pdo->prepare('SELECT Title FROM TRAVEL_PACKAGE WHERE PackageID = ? AND AgencyID = ?');
$stmt->execute([$packageId, $agencyId]);
$package = $stmt->fetch();

if (!$package) {
    echo '<p class="empty-state">Package not found or access denied.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// 2. Handle Item Removal
if (isset($_GET['remove']) && isset($_GET['type'])) {
    $packageService->removeItemFromPackage($packageId, $_GET['type'], (int)$_GET['remove']);
    header("Location: manage_items.php?id=$packageId&removed=1");
    exit;
}

// 3. Handle Item Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $packageService->addItemToPackage($packageId, $_POST['add_type'], (int)$_POST['add_id']);
    header("Location: manage_items.php?id=$packageId&added=1");
    exit;
}

// 4. Fetch Display Data
$itemsData = $packageService->getPackageItemsData($packageId);
extract($itemsData); // Extracts $pkgDestinations, $availableDest, etc. into current scope for the view
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
        ['Accommodations', $pkgAccommodations, $availableAcc, 'accommodation', 'AccommodationID', 'Name', 'Type'],
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
