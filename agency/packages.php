<?php include __DIR__ . '/../includes/header.php'; requireAgency();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM TRAVEL_PACKAGE WHERE PackageID = ? AND AgencyID = ?');
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

<div class="agency-infinite"
     data-agency-infinite
     data-api-base="<?php echo htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES); ?>"
     data-resource="packages"
     data-page-size="15"
     data-empty-message="You haven't created any packages yet.">

    <p class="lazy-status" data-agency-status style="display:none"></p>

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
        <tbody data-agency-list></tbody>
    </table>
    <button type="button" class="btn btn-secondary load-more-btn" data-agency-load-more style="display:none">Load more</button>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
