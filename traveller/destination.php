<?php
include __DIR__ . '/../includes/header.php';
requireTraveller();

$id = $_GET['id'] ?? null;

if (!$id) {
    echo "<p>Invalid destination.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM DESTINATION WHERE DestinationID = ?");
$stmt->execute([$id]);
$dest = $stmt->fetch();

if (!$dest) {
    echo "<p>Destination not found.</p>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="dashboard-hero">
    <div class="hero-content">
        <h1><?= htmlspecialchars($dest['City']) ?>, <?= htmlspecialchars($dest['Country']) ?></h1>
        <p><?= htmlspecialchars($dest['Description']) ?></p>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <div class="card-body">
            <h3>Location</h3>
            <p>Latitude: <?= $dest['Latitude'] ?></p>
            <p>Longitude: <?= $dest['Longitude'] ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h3>About</h3>
            <p><?= htmlspecialchars($dest['Description']) ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>