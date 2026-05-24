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
$revenue = $revStmt->fetchColumn();

$unread = getUnreadCount($_SESSION['user_id']);
?>

<h1>Agency Dashboard</h1>
<p class="text-muted">Welcome, <?php echo htmlspecialchars($agency['AgencyName']); ?> — Status: <?php echo htmlspecialchars($agency['VerificationStatus']); ?></p>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $packageCount; ?></h3>
        <p>Packages</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $bookingCount; ?></h3>
        <p>Bookings</p>
    </div>
    <div class="stat-card">
        <h3>R<?php echo number_format($revenue, 0); ?></h3>
        <p>Revenue</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $avgRating ? number_format($avgRating, 1) . ' ★' : 'N/A'; ?></h3>
        <p>Avg Rating</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $groupTripCount; ?></h3>
        <p>Group Trips</p>
    </div>
    <div class="stat-card" style="position:relative;">
        <h3><?php echo $unread; ?></h3>
        <p>Unread Messages</p>
        <?php if ($unread): ?><a href="messages.php" style="position:absolute;inset:0;"></a><?php endif; ?>
    </div>
</div>

<div class="quick-actions">
    <a href="create_package.php" class="btn btn-primary">Create New Package</a>
    <a href="packages.php" class="btn btn-secondary">Manage Packages</a>
    <a href="bookings.php" class="btn btn-secondary">Manage Bookings</a>
    <a href="group_trips.php" class="btn btn-secondary">Group Trips</a>
    <a href="messages.php" class="btn btn-secondary">Messages<?php if ($unread): ?> (<?php echo $unread; ?>)<?php endif; ?></a>
</div>

<h2>Recent Bookings</h2>
<?php
$stmt = $pdo->prepare('
    SELECT b.*, p.Title, t.FirstName, t.LastName
    FROM BOOKING b
    JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
    JOIN TRAVELLER t ON b.UserID = t.UserID
    WHERE p.AgencyID = ?
    ORDER BY b.BookingDate DESC LIMIT 5
');
$stmt->execute([$_SESSION['user_id']]);
$recent = $stmt->fetchAll();
?>
<?php if ($recent): ?>
<table class="data-table">
    <thead>
        <tr><th>Package</th><th>Traveller</th><th>Date</th><th>Cost</th><th>Status</th></tr>
    </thead>
    <tbody>
        <?php foreach ($recent as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['Title']); ?></td>
                <td><?php echo htmlspecialchars($r['FirstName'] . ' ' . $r['LastName']); ?></td>
                <td><?php echo date('M d Y', strtotime($r['BookingDate'])); ?></td>
                <td>R<?php echo number_format($r['TotalCost'], 2); ?></td>
                <td><span class="status-badge status-<?php echo strtolower($r['Status']); ?>"><?php echo htmlspecialchars($r['Status']); ?></span></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p>No bookings yet.</p>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
