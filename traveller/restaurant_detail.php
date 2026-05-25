<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    echo '<div class="page-container"><p class="empty-state">Restaurant not found.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT r.*, d.City, d.Country, d.Description as DestDescription FROM RESTAURANT r LEFT JOIN DESTINATION d ON r.DestinationID = d.DestinationID WHERE r.RestaurantID = ?');
$stmt->execute([$id]);
$rest = $stmt->fetch();

if (!$rest) {
    echo '<div class="page-container"><p class="empty-state">Restaurant not found.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$rating = (float)($rest['Rating'] ?? 0);
$starDisplay = $rating > 0 ? str_repeat('★', max(1, (int)round($rating))) . ' ' . number_format($rating, 1) : 'No ratings yet';

$pkgStmt = $pdo->prepare('
    SELECT p.*, ta.AgencyName,
        (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating
    FROM TRAVEL_PACKAGE p
    JOIN INCLUDES_RESTAURANT ir ON p.PackageID = ir.PackageID
    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
    WHERE ir.RestaurantID = ? AND p.Status = "Active"
    ORDER BY p.Price ASC
    LIMIT 6
');
$pkgStmt->execute([$id]);
$packages = $pkgStmt->fetchAll();

$nearbyAcc = $pdo->prepare('SELECT * FROM ACCOMMODATION WHERE DestinationID = ? LIMIT 4');
$nearbyAcc->execute([$rest['DestinationID'] ?? 0]);
$accommodations = $nearbyAcc->fetchAll();
?>

<div class="page-container">
<div class="detail-hero">
    <div class="detail-hero-content" style="flex:1;">
        <span class="badge">🍽️ <?= htmlspecialchars($rest['CuisineType'] ?? 'Various Cuisine') ?></span>
        <h1><?= htmlspecialchars($rest['Name']) ?></h1>
        <div class="detail-rating"><?= $starDisplay ?></div>
        <?php if (!empty($rest['City'])): ?>
            <p class="detail-location">📍 <?= htmlspecialchars($rest['City'] . ', ' . $rest['Country']) ?></p>
        <?php endif; ?>
        <?php if (!empty($rest['Address'])): ?>
            <p class="detail-address">📍 <?= htmlspecialchars($rest['Address']) ?></p>
        <?php endif; ?>
        <?php if (!empty($rest['PriceRange'])): ?>
            <span class="badge" style="font-size:1rem;"><?= htmlspecialchars($rest['PriceRange']) ?></span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($rest['DestDescription'])): ?>
<div class="detail-section">
    <h2>About the Area</h2>
    <p><?= htmlspecialchars($rest['DestDescription']) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($packages)): ?>
<div class="detail-section">
    <h2>Packages including this restaurant</h2>
    <div class="card-grid">
        <?php foreach ($packages as $p): ?>
            <a href="<?= BASE_URL ?>/traveller/package_detail.php?id=<?= $p['PackageID'] ?>" class="card hover-lift" style="text-decoration:none;color:inherit;">
                <div class="card-body">
                    <h3><?= htmlspecialchars($p['Title']) ?></h3>
                    <p class="text-muted">by <?= htmlspecialchars($p['AgencyName']) ?></p>
                    <p style="font-weight:600;color:var(--primary);">R<?= number_format((float)$p['Price'], 2) ?></p>
                    <p><?= (int)$p['DurationDays'] ?> days · <?= $p['AvgRating'] ? number_format($p['AvgRating'], 1) . '★' : 'No ratings' ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($accommodations)): ?>
<div class="detail-section">
    <h2>Nearby Accommodations</h2>
    <div class="card-grid">
        <?php foreach ($accommodations as $a): ?>
            <a href="<?= BASE_URL ?>/traveller/accommodation_detail.php?id=<?= $a['AccommodationID'] ?>" class="card" style="text-decoration:none;color:inherit;">
                <div class="card-body">
                    <h4><?= htmlspecialchars($a['Name']) ?></h4>
                    <span class="badge"><?= htmlspecialchars($a['Type'] ?? 'Hotel') ?></span>
                    <span style="color:#f59e0b;"><?= str_repeat('★', (int)($a['StarRating'] ?? 0)) ?></span>
                    <p style="color:#059669;font-weight:600;">R<?= number_format((float)$a['PricePerNight'], 2) ?>/night</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div>

<style>
.detail-hero { display: flex; gap: 2rem; margin-bottom: 2rem; }
.detail-hero-content { flex: 1; display: flex; flex-direction: column; gap: 0.5rem; }
.detail-hero-content h1 { font-size: 2rem; color: #1e293b; }
.detail-rating { font-size: 1.2rem; color: #f59e0b; }
.detail-location { color: #64748b; font-size: 1rem; }
.detail-address { color: #94a3b8; font-size: 0.9rem; }
.detail-section { margin-bottom: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
.detail-section h2 { margin-bottom: 1rem; color: #1e293b; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
