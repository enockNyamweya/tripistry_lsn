<?php include __DIR__ . '/../includes/header.php'; requireAgency();

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

<div class="agency-infinite"
     data-agency-infinite
     data-api-base="<?php echo htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES); ?>"
     data-resource="group-trips"
     data-page-size="15"
     data-empty-message="No group trips created yet. Create a package with the Group Trip option enabled.">

    <p class="lazy-status" data-agency-status style="display:none"></p>

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
        <tbody data-agency-list></tbody>
    </table>
    <button type="button" class="btn btn-secondary load-more-btn" data-agency-load-more style="display:none">Load more</button>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>/assets/js/group_trips.js"></script>
