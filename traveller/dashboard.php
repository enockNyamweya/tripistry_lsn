<?php
include __DIR__ . '/../includes/header.php';
requireTraveller();
?>

<div class="dashboard-hero">
    <div class="hero-content">
        <h1>Welcome back, Explorer</h1>
        <p>
            Discover top-rated trips, personalized recommendations,
            and trending destinations.
        </p>
    </div>
</div>

<!-- STATS -->
<div class="stats-grid">

<?php
$stats = [
    [
        'label' => 'Destinations',
        'value' => $pdo->query("SELECT COUNT(*) FROM DESTINATION")->fetchColumn()
    ],
    [
        'label' => 'Flights',
        'value' => $pdo->query("SELECT COUNT(*) FROM FLIGHT")->fetchColumn()
    ],
    [
        'label' => 'Restaurants',
        'value' => $pdo->query("SELECT COUNT(*) FROM RESTAURANT")->fetchColumn()
    ],
    [
        'label' => 'Attractions',
        'value' => $pdo->query("SELECT COUNT(*) FROM ATTRACTION")->fetchColumn()
    ]
];

foreach ($stats as $s): ?>

    <div class="stat-card hover-lift">
        <div class="card-body">
            <h2><?= number_format($s['value']) ?></h2>
            <p><?= htmlspecialchars($s['label']) ?></p>
        </div>
    </div>

<?php endforeach; ?>

</div>

<!-- TOP PACKAGES -->
<h2 class="section-title">Top Rated Packages</h2>

<div class="card-grid">

<?php
$stmt = $pdo->query("
    SELECT
        p.*,
        COALESCE(AVG(r.RatingScore), 0) AS avg_rating

    FROM TRAVEL_PACKAGE p

    LEFT JOIN REVIEW r
        ON p.PackageID = r.PackageID

    GROUP BY p.PackageID

    ORDER BY avg_rating DESC

    LIMIT 8
");

$topPackages = $stmt->fetchAll();

foreach ($topPackages as $p): ?>

    <div class="card feature-card hover-lift">

        <div class="card-body">

            <h3><?= htmlspecialchars($p['Title']) ?></h3>

            <p>
                <?= htmlspecialchars(substr($p['Description'] ?? '', 0, 120)) ?>...
            </p>

            <div class="card-footer">

                <span class="badge">
                    ★ <?= number_format($p['avg_rating'], 1) ?>
                </span>

                <span class="badge">
                    R<?= number_format($p['Price'] ?? 0, 2) ?>
                </span>

            </div>

        </div>

    </div>

<?php endforeach; ?>

</div>

<!-- RECOMMENDATION ENGINE -->
<h2 class="section-title">Recommended For You</h2>
<!--score =
(accommodation_count * 1.5)
+ (restaurant_count * 1.2)
+ (attraction_count * 2) -->
<div class="card-grid">

<?php

/*
    Recommendation Engine:
    - prioritizes destinations with:
        • more attractions
        • more restaurants
        • more accommodations
*/

$stmt = $pdo->query("

    SELECT

        d.*,

        COUNT(DISTINCT a.AccommodationID) AS accommodation_count,

        COUNT(DISTINCT r.RestaurantID) AS restaurant_count,

        COUNT(DISTINCT atn.AttractionID) AS attraction_count

    FROM DESTINATION d

    LEFT JOIN ACCOMMODATION a
        ON a.DestinationID = d.DestinationID

    LEFT JOIN RESTAURANT r
        ON r.DestinationID = d.DestinationID

    LEFT JOIN ATTRACTION atn
        ON atn.DestinationID = d.DestinationID

    GROUP BY d.DestinationID

");

$destinations = $stmt->fetchAll();

$recommendations = [];

foreach ($destinations as $d) {

    $score =
        ($d['accommodation_count'] * 1.5)
        + ($d['restaurant_count'] * 1.2)
        + ($d['attraction_count'] * 2);

    $d['score'] = $score;

    $recommendations[] = $d;
}

/* Sort descending */
usort($recommendations, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

/* Top 6 */
$topRecommendations = array_slice($recommendations, 0, 6);

foreach ($topRecommendations as $d): ?>

    <a href="<?= BASE_URL ?>/traveller/destination.php?id=<?= $d['DestinationID'] ?>"
       class="card feature-card hover-lift">

        <div class="card-media">
            <?php
            $imgUrl = $d['ImageURL'] ?? '';
            if ($imgUrl !== '' && str_starts_with($imgUrl, '..')) {
                $imgUrl = BASE_URL . '/' . ltrim(substr($imgUrl, 3), '/');
            } elseif ($imgUrl !== '' && str_starts_with($imgUrl, '/')) {
                $imgUrl = BASE_URL . $imgUrl;
            }
            $hasImg = $imgUrl !== '';
            $placeholderHidden = $hasImg ? ' is-hidden' : '';
            ?>
            <?php if ($hasImg): ?>
            <img src="<?= htmlspecialchars($imgUrl) ?>"
                 alt="<?= htmlspecialchars($d['City'] . ', ' . $d['Country']) ?>"
                 class="card-img" loading="lazy"
                 onerror="this.classList.add('is-hidden');var n=this.nextElementSibling;if(n)n.classList.remove('is-hidden');">
            <?php endif; ?>
            <div class="card-img-placeholder<?= $placeholderHidden ?>"<?= $hasImg ? ' aria-hidden="true"' : '' ?>>
                <span class="card-img-placeholder-icon"><?= strtoupper(substr($d['City'] ?? '?', 0, 1)) ?></span>
                <span class="card-img-placeholder-text">No photo</span>
            </div>
        </div>

        <div class="card-body">

            <h3>
                <?= htmlspecialchars($d['City']) ?>,
                <?= htmlspecialchars($d['Country']) ?>
            </h3>

            <p>
                <?= htmlspecialchars(substr($d['Description'] ?? '', 0, 100)) ?>...
            </p>

            <div class="card-footer">

                <span class="badge">
                    Recommendation Score <?= number_format($d['score'], 1) ?>
                </span>

            </div>

        </div>

    </a>

<?php endforeach; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>