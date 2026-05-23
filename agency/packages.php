<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$stmt = $pdo->prepare('
    SELECT p.*,
        (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating,
        (SELECT COUNT(*) FROM BOOKS b WHERE b.PackageID = p.PackageID) as BookingCount,
        (SELECT d.City FROM VISITS v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
    FROM PACKAGE p
    JOIN CURATES c ON p.PackageID = c.PackageID
    WHERE c.UserID = ?
    ORDER BY p.PackageID DESC
');
$stmt->execute([$_SESSION['user_id']]);
$packages = $stmt->fetchAll();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM PACKAGE WHERE PackageID = ? AND PackageID IN (SELECT PackageID FROM CURATES WHERE UserID = ?)');
    $stmt->execute([(int)$_GET['delete'], $_SESSION['user_id']]);
    header('Location: packages.php?deleted=1');
    exit;
}
?>

<h1>My Packages</h1>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Package deleted successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success">Package created successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Package updated successfully.</div>
<?php endif; ?>

<a href="create_package.php" class="btn btn-primary" style="margin-bottom:1rem;">Create New Package</a>

<?php if (empty($packages)): ?>
    <p class="empty-state">You haven't created any packages yet.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Destination</th>
                <th>Price</th>
                <th>Duration</th>
                <th>Rating</th>
                <th>Bookings</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($packages as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['Title']); ?></td>
                    <td><?php echo htmlspecialchars($p['DestinationCity'] ?? 'N/A'); ?></td>
                    <td>R<?php echo number_format($p['Price'], 2); ?></td>
                    <td><?php echo $p['DurationDays']; ?> days</td>
                    <td><?php echo $p['AvgRating'] ? number_format($p['AvgRating'], 1) . ' ★' : '—'; ?></td>
                    <td><?php echo $p['BookingCount']; ?></td>
                    <td><span class="status-badge status-<?php echo strtolower($p['Status']); ?>"><?php echo htmlspecialchars($p['Status']); ?></span></td>
                    <td class="actions">
                        <a href="edit_package.php?id=<?php echo $p['PackageID']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="manage_items.php?id=<?php echo $p['PackageID']; ?>" class="btn btn-secondary btn-sm">Items</a>
                        <a href="?delete=<?php echo $p['PackageID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this package?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
