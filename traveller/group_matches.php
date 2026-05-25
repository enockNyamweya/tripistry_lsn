<?php include __DIR__ . '/../includes/header.php'; requireTraveller();
require_once __DIR__ . '/../includes/group_matcher.php';

$matches = match_traveller_to_groups($pdo, $_SESSION['user_id']);
?>

<div class="page-container">
<h1>Recommended Group Trips</h1>
<p class="text-muted">Based on your travel history and preferences, these group trips are the best match for you.</p>

<?php if (empty($matches)): ?>
    <p class="empty-state">No matching group trips found. Book some packages first so we can learn your preferences, or check back later for new group trips.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($matches as $m): ?>
            <?php
                $capacityPct = $m['MaxCapacity'] > 0 ? round(($m['currentEnrolment'] / $m['MaxCapacity']) * 100) : 0;
                $scoreColor = $m['matchScore'] >= 20 ? '#10b981' : ($m['matchScore'] >= 10 ? '#f59e0b' : '#64748b');
            ?>
            <div class="card hover-lift" style="border: 1px solid #e2e8f0;">
                <div class="card-body">
                    <h3><?= htmlspecialchars($m['TripName']) ?></h3>
                    <p style="font-size:0.9rem;color:#64748b;"><?= htmlspecialchars($m['Title']) ?></p>
                    <p>📍 <?= htmlspecialchars($m['City'] . ', ' . $m['Country']) ?></p>
                    <p>R<?= number_format($m['Price'], 2) ?> · <?= $m['DurationDays'] ?> days</p>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.5rem;">
                        <span class="badge"><?= $m['currentEnrolment'] ?>/<?= $m['MaxCapacity'] ?> enrolled</span>
                        <span style="font-weight:700;color:<?= $scoreColor ?>;font-size:1.1rem;">
                            <?= $m['matchScore'] ?>% match
                        </span>
                    </div>
                    <div style="background:#f1f5f9;border-radius:6px;height:6px;margin-top:0.5rem;">
                        <div style="background:<?= $scoreColor ?>;height:6px;border-radius:6px;width:<?= min(100, $capacityPct) ?>%;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($matches)): ?>
<p style="margin-top:1rem;">
    <small class="text-muted">
        The match score is calculated based on your destination preferences, budget compatibility, trip duration, and booking history. Higher scores indicate a stronger match.
    </small>
</p>
<?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
