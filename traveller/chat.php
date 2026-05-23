<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$partnerId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
if (!$partnerId) {
    header('Location: /traveller/messages.php');
    exit;
}

$agency = getAgencyInfo($partnerId);
if (!$agency) {
    echo '<p class="empty-state">Agency not found.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Get packages booked by this traveller from this agency
$pkgs = $pdo->prepare('
    SELECT DISTINCT p.PackageID, p.Title 
    FROM BOOKS b 
    JOIN PACKAGE p ON b.PackageID = p.PackageID 
    JOIN CURATES c ON p.PackageID = c.PackageID 
    WHERE b.UserID = ? AND c.UserID = ?
');
$pkgs->execute([$_SESSION['user_id'], $partnerId]);
$packages = $pkgs->fetchAll();

// Handle send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($_POST['message'] ?? ''))) {
    $msg = trim($_POST['message']);
    $pkgId = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
    sendMessage($_SESSION['user_id'], $partnerId, $pkgId, $msg);
    header("Location: /traveller/chat.php?user=$partnerId&sent=1");
    exit;
}

markAsRead($_SESSION['user_id'], $partnerId);
$messages = getMessages($_SESSION['user_id'], $partnerId);
?>

<div class="chat-container">
    <div class="chat-header">
        <a href="/traveller/messages.php" class="btn btn-secondary btn-sm">← Back</a>
        <h2>Chat with <?php echo htmlspecialchars($agency['AgencyName']); ?></h2>
        <span class="badge"><?php echo htmlspecialchars($agency['VerificationStatus']); ?></span>
    </div>

    <div class="chat-messages" id="chatMessages">
        <?php if (empty($messages)): ?>
            <p class="text-muted" style="text-align:center;padding:2rem;">No messages yet. Start the conversation below.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="chat-bubble <?php echo $msg['SenderID'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                    <div class="chat-bubble-meta">
                        <strong><?php echo htmlspecialchars($msg['SenderName'] ?? $msg['SenderEmail']); ?></strong>
                        <span class="text-muted"><?php echo date('M d H:i', strtotime($msg['SentDate'])); ?></span>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($msg['Message'])); ?></p>
                    <?php if ($msg['PackageID']): ?>
                        <?php
                        $pkgStmt = $pdo->prepare('SELECT Title FROM PACKAGE WHERE PackageID = ?');
                        $pkgStmt->execute([$msg['PackageID']]);
                        $refPkg = $pkgStmt->fetch();
                        if ($refPkg): ?>
                            <span class="chat-ref">Re: <?php echo htmlspecialchars($refPkg['Title']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <form method="POST" action="" class="chat-form">
        <div class="chat-form-row">
            <select name="package_id" class="chat-pkg-select">
                <option value="">No package reference</option>
                <?php foreach ($packages as $p): ?>
                    <option value="<?php echo $p['PackageID']; ?>"><?php echo htmlspecialchars($p['Title']); ?></option>
                <?php endforeach; ?>
            </select>
            <textarea name="message" rows="2" placeholder="Type your message..." required></textarea>
            <button type="submit" class="btn btn-primary">Send</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('chatMessages');
    if (container) container.scrollTop = container.scrollHeight;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
