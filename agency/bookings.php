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

$stmt = $pdo->prepare('
    SELECT b.*, p.Title as PackageTitle, t.FirstName, t.LastName, NULL as PassportNum,
        u.Phone as PhoneNumber, ROUND(b.TotalCost / p.Price) as NumTravellers
    FROM BOOKING b
    JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
    JOIN TRAVELLER t ON b.UserID = t.UserID
    JOIN USER u ON t.UserID = u.UserID
    WHERE p.AgencyID = ?
    ORDER BY b.BookingDate DESC
');
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

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
?>

<h1>Booking Management</h1>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Booking status updated.</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>R<?php echo number_format($summary['Revenue'] ?? 0, 2); ?></h3>
        <p>Total Revenue</p>
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

<?php if (empty($bookings)): ?>
    <p class="empty-state">No bookings yet for your packages.</p>
<?php else: ?>
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
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td>#<?php echo $b['BookingID']; ?></td>
                    <td><?php echo htmlspecialchars($b['PackageTitle']); ?></td>
                    <td><?php echo htmlspecialchars($b['FirstName'] . ' ' . $b['LastName']); ?></td>
                    <td><?php echo htmlspecialchars($b['PassportNum'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($b['PhoneNumber'] ?? '—'); ?></td>
                    <td><?php echo date('M d Y', strtotime($b['BookingDate'])); ?></td>
                    <td><?php echo $b['NumTravellers']; ?></td>
                    <td>R<?php echo number_format($b['TotalCost'], 2); ?></td>
                    <td><span class="status-badge status-<?php echo strtolower($b['Status']); ?>"><?php echo htmlspecialchars($b['Status']); ?></span></td>
                    <td class="actions">
                        <?php if ($b['Status'] === 'Pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="booking_id" value="<?php echo $b['BookingID']; ?>">
                                <button type="submit" name="action" value="confirm" class="btn btn-primary btn-sm">Confirm</button>
                                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                            </form>
                        <?php elseif ($b['Status'] === 'Confirmed'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="booking_id" value="<?php echo $b['BookingID']; ?>">
                                <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
