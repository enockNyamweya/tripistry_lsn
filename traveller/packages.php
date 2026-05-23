<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$sort = $_GET['sort'] ?? 'price_asc';
$search = $_GET['search'] ?? '';
$destFilter = $_GET['destination'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$minRating = $_GET['min_rating'] ?? '';

$query = 'SELECT p.*, ta.AgencyName, u.Email,
    (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating,
    (SELECT COUNT(*) FROM REVIEW r3 WHERE r3.PackageID = p.PackageID) as ReviewCount,
    (SELECT d.City FROM VISITS v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity,
    (SELECT d.Country FROM VISITS v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCountry
    FROM PACKAGE p
    JOIN CURATES c ON p.PackageID = c.PackageID
    JOIN USER u ON c.UserID = u.UserID
    JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
    WHERE p.Status = \'Active\'';

$params = [];

if ($search) {
    $query .= ' AND (p.Title LIKE ? OR p.Description LIKE ? OR ta.AgencyName LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($destFilter) {
    $query .= ' AND p.PackageID IN (SELECT PackageID FROM VISITS v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE d.City = ? OR d.Country = ?)';
    $params[] = $destFilter;
    $params[] = $destFilter;
}
if ($minPrice !== '' && is_numeric($minPrice)) {
    $query .= ' AND p.Price >= ?';
    $params[] = (float)$minPrice;
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $query .= ' AND p.Price <= ?';
    $params[] = (float)$maxPrice;
}
if ($minRating !== '' && is_numeric($minRating)) {
    $query .= ' HAVING AvgRating >= ? OR AvgRating IS NULL';
    $params[] = (float)$minRating;
}

$sortMap = [
    'price_asc' => 'p.Price ASC',
    'price_desc' => 'p.Price DESC',
    'duration_asc' => 'p.DurationDays ASC',
    'duration_desc' => 'p.DurationDays DESC',
    'rating_desc' => 'AvgRating DESC',
    'rating_asc' => 'AvgRating ASC',
    'title_asc' => 'p.Title ASC',
    'date_asc' => 'p.StartDate ASC',
];
$order = $sortMap[$sort] ?? 'p.Price ASC';
$query .= ' ORDER BY ' . $order;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$packages = $stmt->fetchAll();

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
            (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating,
            (SELECT COUNT(*) FROM REVIEW r3 WHERE r3.PackageID = p.PackageID) as ReviewCount,
            (SELECT d.City FROM VISITS v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity,
            (SELECT d.Country FROM VISITS v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCountry
        FROM PACKAGE p
        JOIN CURATES c ON p.PackageID = c.PackageID
        JOIN USER u ON c.UserID = u.UserID
        JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
        WHERE p.PackageID IN ($placeholders)
    ");
    $stmt->execute($compareIds);
    $comparePackages = $stmt->fetchAll();
}
?>

<h1>Travel Packages</h1>

<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
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

<div class="package-list">
    <?php if (empty($packages)): ?>
        <p class="empty-state">No packages found matching your criteria.</p>
    <?php endif; ?>
    <?php foreach ($packages as $pkg): ?>
        <div class="package-card">
            <div class="package-card-header">
                <h2><?php echo htmlspecialchars($pkg['Title']); ?></h2>
                <span class="agency-badge"><?php echo htmlspecialchars($pkg['AgencyName']); ?></span>
            </div>
            <div class="package-card-body">
                <?php if ($pkg['ImageURL']): ?>
                    <img src="<?php echo htmlspecialchars($pkg['ImageURL']); ?>" alt="<?php echo htmlspecialchars($pkg['Title']); ?>" class="package-img">
                <?php endif; ?>
                <div class="package-info">
                    <p><strong>Destination:</strong> <?php echo htmlspecialchars(($pkg['DestinationCity'] ?? 'N/A') . ', ' . ($pkg['DestinationCountry'] ?? '')); ?></p>
                    <p><strong>Duration:</strong> <?php echo $pkg['DurationDays']; ?> days</p>
                    <p><strong>Dates:</strong> <?php echo date('M d Y', strtotime($pkg['StartDate'])); ?> — <?php echo date('M d Y', strtotime($pkg['EndDate'])); ?></p>
                    <p><strong>Max Travellers:</strong> <?php echo $pkg['MaxTravellers']; ?></p>
                    <p class="package-rating">
                        <?php if ($pkg['AvgRating']): ?>
                            <?php echo str_repeat('★', round($pkg['AvgRating'])); ?>
                            <?php echo number_format($pkg['AvgRating'], 1); ?> (<?php echo $pkg['ReviewCount']; ?> reviews)
                        <?php else: ?>
                            No reviews yet
                        <?php endif; ?>
                    </p>
                    <p class="package-price">R<?php echo number_format($pkg['Price'], 2); ?></p>
                </div>
            </div>
            <div class="package-card-footer">
                <a href="package_detail.php?id=<?php echo $pkg['PackageID']; ?>" class="btn btn-primary">View Details</a>
                <a href="packages.php?compare=<?php
                    $ids = $compareIds;
                    if (!in_array($pkg['PackageID'], $ids)) $ids[] = $pkg['PackageID'];
                    echo implode(',', array_slice($ids, 0, 3));
                ?>" class="btn btn-secondary">Compare</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
