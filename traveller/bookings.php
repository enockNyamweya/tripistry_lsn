<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$stmt = $pdo->prepare('
    SELECT b.*, ROUND(b.TotalCost / p.Price) as NumTravellers, p.Title, p.PackageID as PID, p.ImageURL, ta.AgencyName
    FROM BOOKING b
    JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
    WHERE b.UserID = ?
    ORDER BY b.BookingDate DESC
');
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>

<h1>My Bookings</h1>

<?php if (empty($bookings)): ?>
    <p class="empty-state">You have no bookings yet. <a href="packages.php">Browse packages</a></p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Package</th>
                <th>Agency</th>
                <th>Booking Date</th>
                <th>Travellers</th>
                <th>Total Cost</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['Title']); ?></td>
                    <td><?php echo htmlspecialchars($b['AgencyName']); ?></td>
                    <td><?php echo date('M d Y', strtotime($b['BookingDate'])); ?></td>
                    <td><?php echo $b['NumTravellers']; ?></td>
                    <td>R<?php echo number_format($b['TotalCost'], 2); ?></td>
                    <td><span class="status-badge status-<?php echo strtolower($b['Status']); ?>"><?php echo htmlspecialchars($b['Status']); ?></span></td>
                    <td><a href="package_detail.php?id=<?php echo $b['PID']; ?>" class="btn btn-secondary btn-sm">View / Review</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
