<?php
session_start();
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $userType = $_POST['user_type'] ?? '';

    if (empty($email) || empty($password) || empty($confirm) || empty($userType)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($userType, ['Traveller', 'Agency'])) {
        $error = 'Invalid user type.';
    } else {
        $extra = [];
        if ($userType === 'Traveller') {
            $extra['first_name'] = trim($_POST['first_name'] ?? '');
            $extra['last_name'] = trim($_POST['last_name'] ?? '');
            if (empty($extra['first_name']) || empty($extra['last_name'])) {
                $error = 'First name and last name are required.';
            }
        } else {
            $extra['agency_name'] = trim($_POST['agency_name'] ?? '');
            if (empty($extra['agency_name'])) {
                $error = 'Agency name is required.';
            }
        }

        if (!$error) {
            $result = registerUser($email, $password, $userType, $extra);
            if ($result) {
                $success = 'Registration successful! You can now <a href="' . BASE_URL . '/login.php">login</a>.';
            } else {
                $error = 'Email already registered or an error occurred.';
            }
        }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

    <div class="auth-form" style="max-width: 500px; margin: 40px auto; background: var(--glass-bg); padding: 2rem; border-radius: var(--border-radius); border: 1px solid var(--glass-border); box-shadow: var(--shadow-md);">
        <h1>Register</h1>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="user_type">I am a</label>
                <select id="user_type" name="user_type" required onchange="toggleFields()">
                    <option value="">Select type...</option>
                    <option value="Traveller">Traveller</option>
                    <option value="Agency">Travel Agency</option>
                </select>
            </div>
            <div id="travellerFields" style="display:none;">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name">
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name">
                </div>
            </div>
            <div id="agencyFields" style="display:none;">
                <div class="form-group">
                    <label for="agency_name">Agency Name</label>
                    <input type="text" id="agency_name" name="agency_name">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        <p class="auth-link">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login here</a></p>
    </div>

<script>
function toggleFields() {
    var type = document.getElementById('user_type').value;
    document.getElementById('travellerFields').style.display = type === 'Traveller' ? 'block' : 'none';
    document.getElementById('agencyFields').style.display = type === 'Agency' ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
