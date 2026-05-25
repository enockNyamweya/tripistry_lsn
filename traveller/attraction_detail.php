<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    echo '<div class="page-container"><p class="empty-state">Attraction not found.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT a.*, d.City, d.Country, d.Description as DestDescription FROM ATTRACTION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID WHERE a.AttractionID = ?');
$stmt->execute([$id]);
$attr = $stmt->fetch();

if (!$attr) {
    echo '<div class="page-container"><p class="empty-state">Attraction not found.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$fee = (float)($attr['EntryFee'] ?? 0) > 0 ? 'R' . number_format((float)$attr['EntryFee'], 2) : 'Free Entry';

$pkgStmt = $pdo->prepare('
    SELECT p.*, ta.AgencyName,
        (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating
    FROM TRAVEL_PACKAGE p
    JOIN INCLUDES_ATTRACTION ia ON p.PackageID = ia.PackageID
    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
    WHERE ia.AttractionID = ? AND p.Status = "Active"
    ORDER BY p.Price ASC LIMIT 6
');
$pkgStmt->execute([$id]);
$packages = $pkgStmt->fetchAll();

$nearbyRest = $pdo->prepare('SELECT * FROM RESTAURANT WHERE DestinationID = ? LIMIT 4');
$nearbyRest->execute([$attr['DestinationID'] ?? 0]);
$restaurants = $nearbyRest->fetchAll();
?>

<div class="page-container">
<div class="detail-hero">
    <div class="detail-hero-content" style="flex:1;">
        <span class="badge">🎯 <?= htmlspecialchars($attr['Type'] ?? 'Attraction') ?></span>
        <h1><?= htmlspecialchars($attr['Name']) ?></h1>
        <?php if (!empty($attr['City'])): ?>
            <p class="detail-location">📍 <?= htmlspecialchars($attr['City'] . ', ' . $attr['Country']) ?></p>
        <?php endif; ?>
        <span class="badge" style="font-size:1rem;background:#059669;color:#fff;"><?= $fee ?></span>
        <?php if (!empty($attr['OpeningHours'])): ?>
            <p>🕐 <?= htmlspecialchars($attr['OpeningHours']) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($attr['Description'])): ?>
<div class="detail-section">
    <h2>Description</h2>
    <p style="line-height:1.8;font-size:1.05rem;"><?= nl2br(htmlspecialchars($attr['Description'])) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($attr['DestDescription'])): ?>
<div class="detail-section">
    <h2>About the Area</h2>
    <p><?= htmlspecialchars($attr['DestDescription']) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($packages)): ?>
<div class="detail-section">
    <h2>Packages including this attraction</h2>
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

<?php if (!empty($restaurants)): ?>
<div class="detail-section">
    <h2>Nearby Restaurants</h2>
    <div class="card-grid">
        <?php foreach ($restaurants as $r): ?>
            <a href="<?= BASE_URL ?>/traveller/restaurant_detail.php?id=<?= $r['RestaurantID'] ?>" class="card" style="text-decoration:none;color:inherit;">
                <div class="card-body">
                    <h4><?= htmlspecialchars($r['Name']) ?></h4>
                    <span class="badge"><?= htmlspecialchars($r['CuisineType'] ?? 'Various') ?></span>
                    <?php if ($r['Rating']): ?><span style="color:#f59e0b;">★ <?= number_format((float)$r['Rating'], 1) ?></span><?php endif; ?>
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
.detail-location { color: #64748b; font-size: 1rem; }
.detail-section { margin-bottom: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
.detail-section h2 { margin-bottom: 1rem; color: #1e293b; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
