<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$stmt = $pdo->prepare('
    SELECT g.*, p.Title as PackageTitle, p.PackageID as PID, p.Status as PackageStatus, p.DurationDays,
        (SELECT COUNT(*) FROM ENROLS ge WHERE ge.GroupTripID = g.GroupTripID) as EnrolmentCount,
        (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
    FROM GROUP_TRIP g
    JOIN TRAVEL_PACKAGE p ON g.PackageID = p.PackageID
    WHERE g.AgencyID = ?
    ORDER BY g.GroupTripID DESC
');
$stmt->execute([$_SESSION['user_id']]);
$groupTrips = $stmt->fetchAll();

// Status update
if (isset($_GET['status']) && isset($_GET['gid'])) {
    $newStatus = $_GET['status'];
    $gid = (int)$_GET['gid'];
    if (in_array($newStatus, ['Active', 'Inactive'])) {
        $stmt = $pdo->prepare('UPDATE TRAVEL_PACKAGE p JOIN GROUP_TRIP g ON p.PackageID = g.PackageID SET p.Status = ? WHERE g.GroupTripID = ? AND g.AgencyID = ?');
        $stmt->execute([$newStatus, $gid, $_SESSION['user_id']]);
    }
    header('Location: group_trips.php?updated=1');
    exit;
}
?>

<h1>Group Trips</h1>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Group trip status updated successfully.</div>
<?php endif; ?>

<a href="create_package.php" class="btn btn-primary" style="margin-bottom:1rem;">Create New Group Trip</a>

<?php if (empty($groupTrips)): ?>
    <p class="empty-state">No group trips created yet. Create a package with the "Group Trip" option enabled.</p>
<?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Group Name</th>
                <th>Package</th>
                <th>Destination</th>
                <th>Duration</th>
                <th>Max Capacity</th>
                <th>Enrolments</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupTrips as $g): ?>
                <tr>
                    <td><?php echo htmlspecialchars($g['TripName']); ?></td>
                    <td><?php echo htmlspecialchars($g['PackageTitle']); ?></td>
                    <td><?php echo htmlspecialchars($g['DestinationCity'] ?? 'N/A'); ?></td>
                    <td><?php echo $g['DurationDays']; ?> days</td>
                    <td><?php echo $g['MaxCapacity']; ?></td>
                    <td><?php echo $g['EnrolmentCount']; ?></td>
                    <td><span class="status-badge status-<?php echo strtolower($g['PackageStatus']); ?>"><?php echo htmlspecialchars($g['PackageStatus']); ?></span></td>
                    <td class="actions">
                        <a href="edit_package.php?id=<?php echo $g['PID']; ?>" class="btn btn-secondary btn-sm">Edit Package</a>
                        <?php if ($g['PackageStatus'] === 'Active'): ?>
                            <a href="?gid=<?php echo $g['GroupTripID']; ?>&status=Inactive" class="btn btn-secondary btn-sm">Deactivate</a>
                        <?php else: ?>
                            <a href="?gid=<?php echo $g['GroupTripID']; ?>&status=Active" class="btn btn-secondary btn-sm">Activate</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
