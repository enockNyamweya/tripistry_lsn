<?php include __DIR__ . '/../includes/header.php'; requireAgency();

// Handle status update
if (isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bid = (int)$_POST['booking_id'];
    $newStatus = $_POST['action'] === 'confirm' ? 'Confirmed' : 'Cancelled';
    $stmt = $pdo->prepare('UPDATE BOOKING SET Status = ? WHERE BookingID = ? AND PackageID IN (SELECT PackageID FROM TRAVEL_PACKAGE WHERE AgencyID = ?)');
    $stmt->execute([$newStatus, $bid, $_SESSION['user_id']]);
    header('Location: ' . BASE_URL . '/agency/bookings.php?updated=1');
    exit;
}

// Revenue totals
$stmt = $pdo->prepare('
    SELECT SUM(b.TotalCost) as Revenue, COUNT(*) as Total, 
        SUM(CASE WHEN b.Status = "Confirmed" THEN 1 ELSE 0 END) as Confirmed,
        SUM(CASE WHEN b.Status = "Cancelled" THEN 1 ELSE 0 END) as Cancelled,
        SUM(CASE WHEN b.Status = "Pending" THEN 1 ELSE 0 END) as Pending
    FROM BOOKING b
    JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
    WHERE p.AgencyID = ?
');
$stmt->execute([$_SESSION['user_id']]);
$summary = $stmt->fetch();
$revenueAmount = (float)($summary['Revenue'] ?? 0);
$revenueShort = formatRevenueShort($revenueAmount);
$revenueFull = formatRevenueFull($revenueAmount);
?>

<h1>Booking Management</h1>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Booking status updated.</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card stat-card-revenue">
        <p class="stat-revenue-value" title="<?php echo htmlspecialchars($revenueFull); ?>"><?php echo htmlspecialchars($revenueShort); ?></p>
        <p class="stat-revenue-label">Total Revenue</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $summary['Total'] ?? 0; ?></h3>
        <p>Total Bookings</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $summary['Confirmed'] ?? 0; ?></h3>
        <p>Confirmed</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $summary['Pending'] ?? 0; ?></h3>
        <p>Pending</p>
    </div>
</div>

<div class="agency-infinite"
     data-agency-infinite
     data-api-base="<?php echo htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES); ?>"
     data-resource="bookings"
     data-page-size="15"
     data-empty-message="No bookings yet for your packages.">

    <p class="lazy-status" data-agency-status style="display:none"></p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Booking #</th>
                <th>Package</th>
                <th>Traveller</th>
                <th>Passport</th>
                <th>Phone</th>
                <th>Date</th>
                <th>Travellers</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody data-agency-list></tbody>
    </table>
    <button type="button" class="btn btn-secondary load-more-btn" data-agency-load-more style="display:none">Load more</button>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
