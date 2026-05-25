<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$sort = $_GET['sort'] ?? 'price_asc';
$search = $_GET['search'] ?? '';
$destFilter = $_GET['destination'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
// Package list loaded via API (main.js infinite scroll)

// Get all destinations for filter dropdown
$destStmt = $pdo->query('SELECT DISTINCT City, Country FROM DESTINATION ORDER BY Country, City');
$destList = $destStmt->fetchAll();

// If comparing, get selected packages
$compareIds = isset($_GET['compare']) ? array_map('intval', explode(',', $_GET['compare'])) : [];
$comparePackages = [];
if ($compareIds) {
    $placeholders = implode(',', array_fill(0, count($compareIds), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, ta.AgencyName, u.Email,
            CURRENT_DATE() as StartDate, DATE_ADD(CURRENT_DATE(), INTERVAL p.DurationDays DAY) as EndDate,
            20 as MaxTravellers,
            (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating,
            (SELECT COUNT(*) FROM REVIEW r3 WHERE r3.PackageID = p.PackageID) as ReviewCount,
            (SELECT d.City FROM HAS_DESTINATION hd JOIN DESTINATION d ON hd.DestinationID = d.DestinationID WHERE hd.PackageID = p.PackageID LIMIT 1) as DestinationCity,
            (SELECT d.Country FROM HAS_DESTINATION hd JOIN DESTINATION d ON hd.DestinationID = d.DestinationID WHERE hd.PackageID = p.PackageID LIMIT 1) as DestinationCountry
        FROM TRAVEL_PACKAGE p
        JOIN USER u ON p.AgencyID = u.UserID
        JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
        WHERE p.PackageID IN ($placeholders)
    ");
    $stmt->execute($compareIds);
    $comparePackages = $stmt->fetchAll();
}
?>

<h1>Travel Packages</h1>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form" id="packages-filter-form">
        <input type="text" name="search" placeholder="Search packages..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="destination">
            <option value="">All Destinations</option>
            <?php foreach ($destList as $d): ?>
                <option value="<?php echo htmlspecialchars($d['City']); ?>" <?php echo $destFilter === $d['City'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d['City'] . ', ' . $d['Country']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="min_price" placeholder="Min Price" step="0.01" value="<?php echo htmlspecialchars($minPrice); ?>" style="width:100px;">
        <input type="number" name="max_price" placeholder="Max Price" step="0.01" value="<?php echo htmlspecialchars($maxPrice); ?>" style="width:100px;">
        <select name="sort">
            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low-High</option>
            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High-Low</option>
            <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>Rating: High-Low</option>
            <option value="rating_asc" <?php echo $sort === 'rating_asc' ? 'selected' : ''; ?>>Rating: Low-High</option>
            <option value="duration_asc" <?php echo $sort === 'duration_asc' ? 'selected' : ''; ?>>Duration: Short-Long</option>
            <option value="duration_desc" <?php echo $sort === 'duration_desc' ? 'selected' : ''; ?>>Duration: Long-Short</option>
            <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Date: Earliest</option>
            <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title: A-Z</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="packages.php" class="btn btn-secondary">Clear</a>
    </form>
</div>

<?php if ($comparePackages): ?>
<div class="comparison-section">
    <h2>Comparing <?php echo count($comparePackages); ?> Packages</h2>
    <table class="data-table comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <?php foreach ($comparePackages as $cp): ?>
                    <th><?php echo htmlspecialchars($cp['Title']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Agency</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><?php echo htmlspecialchars($cp['AgencyName']); ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td><strong>Price</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td>R<?php echo number_format($cp['Price'], 2); ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td><strong>Duration</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><?php echo $cp['DurationDays']; ?> days</td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td><strong>Destination</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><?php echo htmlspecialchars(($cp['DestinationCity'] ?? 'N/A') . ', ' . ($cp['DestinationCountry'] ?? '')); ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td><strong>Rating</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><?php echo $cp['AvgRating'] ? number_format($cp['AvgRating'], 1) . ' ★' : 'No reviews'; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td><strong>Max Travellers</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><?php echo $cp['MaxTravellers']; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td><strong>Group Trip</strong></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><?php echo $cp['IsGroupTrip'] ? 'Yes' : 'No'; ?></td>
                <?php endforeach; ?>
            </tr>
            <tr>
                <td></td>
                <?php foreach ($comparePackages as $cp): ?>
                    <td><a href="package_detail.php?id=<?php echo $cp['PackageID']; ?>" class="btn btn-primary btn-sm">View & Book</a></td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
    <a href="packages.php" class="btn btn-secondary">Clear Comparison</a>
</div>
<?php endif; ?>

<div id="packages-lazy"
     data-api-base="<?php echo htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES); ?>"
     data-page-size="12"
     data-compare-ids="<?php echo htmlspecialchars(implode(',', $compareIds), ENT_QUOTES); ?>">

    <p class="lazy-status" data-packages-status style="display:none"></p>
    <div class="package-list" data-packages-list></div>
    <div class="infinite-sentinel" data-infinite-sentinel aria-hidden="true"></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
