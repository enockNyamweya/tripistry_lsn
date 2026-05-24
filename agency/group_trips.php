<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$stmt = $pdo->prepare('
    SELECT g.*, g.TripName as GroupName, 2 as MinParticipants, g.MaxCapacity as MaxParticipants,
        \'Open\' as Status, CURRENT_DATE() as DepartureDate, DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) as ReturnDate,
        p.Title as PackageTitle, p.PackageID as PID,
        (SELECT COUNT(*) FROM ENROLS ge WHERE ge.GroupTripID = g.GroupTripID) as EnrolmentCount,
        (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
    FROM GROUP_TRIP g
    JOIN TRAVEL_PACKAGE p ON g.PackageID = p.PackageID
    WHERE g.AgencyID = ?
    ORDER BY DepartureDate ASC
');
$stmt->execute([$_SESSION['user_id']]);
$groupTrips = $stmt->fetchAll();

// Status update
if (isset($_GET['status']) && isset($_GET['gid'])) {
    $newStatus = $_GET['status'];
    $gid = (int)$_GET['gid'];
    if (in_array($newStatus, ['Open', 'Closed', 'Cancelled'])) {
        $stmt = $pdo->prepare('UPDATE GROUP_TRIP SET TripName = TripName WHERE GroupTripID = ? AND AgencyID = ?');
        $stmt->execute([$gid, $_SESSION['user_id']]);
    }
    header('Location: group_trips.php?updated=1');
    exit;
}
?>

<h1>Group Trips</h1>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">Group trip updated successfully.</div>
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
                <th>Dates</th>
                <th>Participants</th>
                <th>Enrolments</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groupTrips as $g): ?>
                <tr>
                    <td><?php echo htmlspecialchars($g['GroupName']); ?></td>
                    <td><?php echo htmlspecialchars($g['PackageTitle']); ?></td>
                    <td><?php echo htmlspecialchars($g['DestinationCity'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M d', strtotime($g['DepartureDate'])); ?> — <?php echo date('M d', strtotime($g['ReturnDate'])); ?></td>
                    <td><?php echo $g['MinParticipants']; ?>-<?php echo $g['MaxParticipants']; ?></td>
                    <td><?php echo $g['EnrolmentCount']; ?></td>
                    <td><span class="status-badge status-<?php echo strtolower($g['Status']); ?>"><?php echo htmlspecialchars($g['Status']); ?></span></td>
                    <td class="actions">
                        <a href="edit_package.php?id=<?php echo $g['PID']; ?>" class="btn btn-secondary btn-sm">Edit Package</a>
                        <?php if ($g['Status'] === 'Open'): ?>
                            <a href="?gid=<?php echo $g['GroupTripID']; ?>&status=Closed" class="btn btn-secondary btn-sm">Close</a>
                        <?php elseif ($g['Status'] === 'Closed'): ?>
                            <a href="?gid=<?php echo $g['GroupTripID']; ?>&status=Open" class="btn btn-secondary btn-sm">Reopen</a>
                        <?php endif; ?>
                        <?php if ($g['Status'] !== 'Cancelled'): ?>
                            <a href="?gid=<?php echo $g['GroupTripID']; ?>&status=Cancelled" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this group trip?')">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
