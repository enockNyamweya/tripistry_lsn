<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    echo '<div class="page-container"><p class="empty-state">Accommodation not found.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stmt = $pdo->prepare('SELECT a.*, d.City, d.Country, d.Description as DestDescription FROM ACCOMMODATION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID WHERE a.AccommodationID = ?');
$stmt->execute([$id]);
$acc = $stmt->fetch();

if (!$acc) {
    echo '<div class="page-container"><p class="empty-state">Accommodation not found.</p></div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$stars = str_repeat('★', max(0, (int)($acc['StarRating'] ?? 0)));

// Get amenities
$amenityStmt = $pdo->prepare('SELECT Amenity FROM ACCOMMODATION_AMENITIES WHERE AccommodationID = ?');
$amenityStmt->execute([$id]);
$amenities = $amenityStmt->fetchAll(PDO::FETCH_COLUMN);

// Get packages that include this accommodation
$pkgStmt = $pdo->prepare('
    SELECT p.*, ta.AgencyName,
        (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating
    FROM TRAVEL_PACKAGE p
    JOIN INCLUDES_ACCOM ia ON p.PackageID = ia.PackageID
    JOIN TRAVEL_AGENCY ta ON p.AgencyID = ta.UserID
    WHERE ia.AccommodationID = ? AND p.Status = "Active"
    ORDER BY p.Price ASC
    LIMIT 6
');
$pkgStmt->execute([$id]);
$packages = $pkgStmt->fetchAll();

// Get nearby restaurants and attractions
$nearbyRest = $pdo->prepare('SELECT * FROM RESTAURANT WHERE DestinationID = ? LIMIT 4');
$nearbyRest->execute([$acc['DestinationID'] ?? 0]);
$restaurants = $nearbyRest->fetchAll();

$nearbyAttr = $pdo->prepare('SELECT * FROM ATTRACTION WHERE DestinationID = ? LIMIT 4');
$nearbyAttr->execute([$acc['DestinationID'] ?? 0]);
$attractions = $nearbyAttr->fetchAll();
?>

<div class="page-container">
<div class="detail-hero">
    <div class="detail-hero-media">
        <?php if (!empty($acc['ImageURL'])): ?>
            <img src="<?= htmlspecialchars($acc['ImageURL']) ?>" alt="<?= htmlspecialchars($acc['Name']) ?>" onerror="this.style.display='none'">
        <?php endif; ?>
    </div>
    <div class="detail-hero-content">
        <span class="badge"><?= htmlspecialchars($acc['Type'] ?? 'Hotel') ?></span>
        <h1><?= htmlspecialchars($acc['Name']) ?></h1>
        <div class="detail-stars"><?= $stars ?> <small>(<?= (int)($acc['StarRating'] ?? 0) ?>-star)</small></div>
        <?php if (!empty($acc['City'])): ?>
            <p class="detail-location"><?= htmlspecialchars($acc['City'] . ', ' . $acc['Country']) ?></p>
        <?php endif; ?>
        <?php if (!empty($acc['Address'])): ?>
            <p class="detail-address"><?= htmlspecialchars($acc['Address']) ?></p>
        <?php endif; ?>
        <div class="detail-price">R<?= number_format((float)$acc['PricePerNight'], 2) ?> <span>/ night</span></div>
    </div>
</div>

<?php if (!empty($amenities)): ?>
<div class="detail-section">
    <h2>Amenities</h2>
    <div class="amenity-tags">
        <?php foreach ($amenities as $a): ?>
            <span class="badge"><?= htmlspecialchars($a) ?></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($acc['DestDescription'])): ?>
<div class="detail-section">
    <h2>About the Area</h2>
    <p><?= htmlspecialchars($acc['DestDescription']) ?></p>
</div>
<?php endif; ?>

<?php if (!empty($packages)): ?>
<div class="detail-section">
    <h2>Packages including this accommodation</h2>
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

<?php if (!empty($attractions)): ?>
<div class="detail-section">
    <h2>Nearby Attractions</h2>
    <div class="card-grid">
        <?php foreach ($attractions as $a): ?>
            <a href="<?= BASE_URL ?>/traveller/attraction_detail.php?id=<?= $a['AttractionID'] ?>" class="card" style="text-decoration:none;color:inherit;">
                <div class="card-body">
                    <h4><?= htmlspecialchars($a['Name']) ?></h4>
                    <span class="badge"><?= htmlspecialchars($a['Type'] ?? 'Attraction') ?></span>
                    <?php if ($a['EntryFee'] > 0): ?><span>R<?= number_format((float)$a['EntryFee'], 2) ?></span><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

</div>

<style>
.detail-hero { display: flex; gap: 2rem; margin-bottom: 2rem; }
.detail-hero-media { flex: 0 0 400px; max-width: 400px; }
.detail-hero-media img { width: 100%; border-radius: 12px; object-fit: cover; max-height: 300px; }
.detail-hero-content { flex: 1; display: flex; flex-direction: column; gap: 0.5rem; }
.detail-hero-content h1 { font-size: 2rem; color: #1e293b; }
.detail-stars { font-size: 1.2rem; color: #f59e0b; }
.detail-location { color: #64748b; font-size: 1rem; }
.detail-address { color: #94a3b8; font-size: 0.9rem; }
.detail-price { font-size: 1.8rem; font-weight: 700; color: #059669; margin-top: 0.5rem; }
.detail-price span { font-size: 1rem; font-weight: 400; color: #64748b; }
.detail-section { margin-bottom: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
.detail-section h2 { margin-bottom: 1rem; color: #1e293b; }
.amenity-tags { display: flex; flex-wrap: wrap; gap: 0.5rem; }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
