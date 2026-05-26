<?php include __DIR__ . '/includes/header.php'; ?>

<?php if (isLoggedIn()): ?>
    <?php header('Location: ' . BASE_URL . '/dashboard.php'); exit; ?>
<?php else: ?>

<style>
.welcome-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 120px);
    text-align: center;
    padding: 2rem;
}
.welcome-content {
    max-width: 640px;
    animation: welcomeFadeIn 1s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes welcomeFadeIn {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}
.welcome-logo {
    width: 88px; height: 88px;
    background: var(--primary);
    border-radius: 22px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 2.6rem; font-weight: 800; color: white;
    box-shadow: 0 20px 60px rgba(37, 99, 235, 0.2);
    margin-bottom: 2rem;
}
.welcome-page h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 700;
    color: #0f172a;
    line-height: 1.15;
    margin-bottom: 1.25rem;
}
.welcome-page h1 span { color: var(--primary); }
.welcome-page .welcome-subtitle {
    font-size: 1.15rem;
    color: #64748b;
    line-height: 1.7;
    max-width: 500px;
    margin: 0 auto 2.5rem;
}
.welcome-buttons {
    display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;
}
.welcome-buttons .btn-primary {
    background: var(--primary);
    color: white; padding: 0.9rem 2.2rem; border-radius: 12px;
    font-size: 1rem; font-weight: 600; text-decoration: none;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.2);
    transition: transform 0.2s, background-color 0.2s, box-shadow 0.2s;
}
.welcome-buttons .btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(37, 99, 235, 0.3);
}
.welcome-buttons .btn-outline {
    background: white; color: var(--primary); padding: 0.9rem 2.2rem;
    border-radius: 12px; font-size: 1rem; font-weight: 600;
    text-decoration: none; border: 2px solid var(--border-light);
    transition: border-color 0.2s, background 0.2s;
}
.welcome-buttons .btn-outline:hover { border-color: var(--primary); background: var(--bg-base); }

body.theme-dark .welcome-page h1 {
    color: #ffffff;
}
body.theme-dark .welcome-page .welcome-subtitle {
    color: #cbd5e1;
}
body.theme-dark .welcome-buttons .btn-outline {
    background: transparent;
    color: #ffffff;
    border-color: #334155;
}
body.theme-dark .welcome-buttons .btn-outline:hover {
    background: #112A46;
    border-color: #60a5fa;
}
</style>

<div class="welcome-page">
    <div class="welcome-content">
        <div class="welcome-logo">T</div>
        <h1>Tripistry<span>.</span></h1>
        <p class="welcome-subtitle">
            Discover curated travel packages from top agencies.
            Compare flights, accommodations, and attractions, all in one place.
        </p>
        <div class="welcome-buttons">
            <a href="<?php echo BASE_URL; ?>/login.php" class="btn-primary">Log In</a>
            <a href="<?php echo BASE_URL; ?>/register.php" class="btn-outline">Create Account</a>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
