<?php include __DIR__ . '/includes/header.php'; ?>

<?php if (isLoggedIn()): ?>
    <?php header('Location: /dashboard.php'); exit; ?>
<?php else: ?>
<div class="hero">
    <h1>Welcome to Tripistry</h1>
    <p>Compare travel packages across agencies. Book your dream trip with confidence.</p>
    <div class="hero-cta">
        <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary">Login</a>
        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-secondary">Register</a>
    </div>
</div>

<div class="features">
    <div class="feature-card">
        <h3>Browse Packages</h3>
        <p>Explore flights, accommodations, attractions and restaurants curated by top agencies.</p>
    </div>
    <div class="feature-card">
        <h3>Compare & Book</h3>
        <p>Compare packages side-by-side across different agencies and book the best deal.</p>
    </div>
    <div class="feature-card">
        <h3>Leave Reviews</h3>
        <p>Share your experience with ratings and reviews for agencies and their packages.</p>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
