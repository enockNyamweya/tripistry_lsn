<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$agency = getAgencyInfo($_SESSION['user_id']);

// Stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM TRAVEL_PACKAGE p WHERE p.AgencyID = ?');
$stmt->execute([$_SESSION['user_id']]);
$packageCount = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM BOOKING b JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID WHERE p.AgencyID = ?');
$stmt->execute([$_SESSION['user_id']]);
$bookingCount = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT AVG(r.RatingScore) FROM REVIEW r JOIN TRAVEL_PACKAGE p ON r.PackageID = p.PackageID WHERE p.AgencyID = ?');
$stmt->execute([$_SESSION['user_id']]);
$avgRating = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM GROUP_TRIP g WHERE g.AgencyID = ?');
$stmt->execute([$_SESSION['user_id']]);
$groupTripCount = $stmt->fetchColumn();

$revStmt = $pdo->prepare('SELECT COALESCE(SUM(b.TotalCost), 0) FROM BOOKING b JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID WHERE p.AgencyID = ? AND b.Status = "Confirmed"');
$revStmt->execute([$_SESSION['user_id']]);
$revenueAmount = (float)$revStmt->fetchColumn();
$revenueShort = formatRevenueShort($revenueAmount);
$revenueFull = formatRevenueFull($revenueAmount);

?>

<h1>Agency Dashboard</h1>
<p class="text-muted">Welcome, <?php echo htmlspecialchars($agency['AgencyName']); ?> (Status: <?php echo htmlspecialchars($agency['VerificationStatus']); ?>)</p>

<div class="stats-grid">
    <a href="packages.php" class="stat-card stat-card-link">
        <h3><?php echo (int)$packageCount; ?></h3>
        <p>Packages</p>
    </a>
    <a href="bookings.php" class="stat-card stat-card-link">
        <h3><?php echo (int)$bookingCount; ?></h3>
        <p>Bookings</p>
    </a>
    <div class="stat-card stat-card-revenue">
        <p class="stat-revenue-value" title="<?php echo htmlspecialchars($revenueFull); ?>"><?php echo htmlspecialchars($revenueShort); ?></p>
        <p class="stat-revenue-label">Revenue</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $avgRating ? number_format($avgRating, 1) . ' ★' : 'N/A'; ?></h3>
        <p>Avg Rating</p>
    </div>
    <a href="group_trips.php" class="stat-card stat-card-link">
        <h3><?php echo (int)$groupTripCount; ?></h3>
        <p>Group Trips</p>
    </a>
</div>

<div class="quick-actions">
    <a href="create_package.php" class="btn btn-primary">Create New Package</a>
    <a href="packages.php" class="btn btn-secondary">Manage Packages</a>
    <a href="bookings.php" class="btn btn-secondary">Manage Bookings</a>
    <a href="group_trips.php" class="btn btn-secondary">Group Trips</a>
</div>

<h2>Recent Bookings</h2>
<p class="text-muted">Scroll for more; opens full list on <a href="bookings.php">Manage Bookings</a>.</p>

<div class="agency-infinite dashboard-bookings-scroll"
     data-agency-infinite
     data-variant="dashboard"
     data-api-base="<?php echo htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES); ?>"
     data-resource="bookings"
     data-page-size="10"
     data-empty-message="No bookings yet.">

    <p class="lazy-status" data-agency-status style="display:none"></p>

    <table class="data-table">
        <thead>
            <tr><th>Package</th><th>Traveller</th><th>Date</th><th>Cost</th><th>Status</th></tr>
        </thead>
        <tbody data-agency-list></tbody>
    </table>
    <button type="button" class="btn btn-secondary load-more-btn" data-agency-load-more style="display:none">Load more</button>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
