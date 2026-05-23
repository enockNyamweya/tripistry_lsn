<?php include __DIR__ . '/../includes/header.php'; requireAgency();

$conversations = getConversations($_SESSION['user_id']);
?>

<h1>Messages from Travellers</h1>

<?php if (empty($conversations)): ?>
    <p class="empty-state">No messages yet. Travellers can message you from their bookings or package pages.</p>
<?php else: ?>
    <div class="chat-list">
        <?php foreach ($conversations as $conv): ?>
            <a href="/agency/chat.php?user=<?php echo $conv['PartnerID']; ?>" class="chat-list-item <?php echo $conv['Unread'] > 0 ? 'unread' : ''; ?>">
                <div class="chat-list-avatar">
                    <?php echo strtoupper(substr($conv['FirstName'] ?? 'T', 0, 1)); ?>
                </div>
                <div class="chat-list-body">
                    <div class="chat-list-name">
                        <?php echo htmlspecialchars($conv['FirstName'] . ' ' . $conv['LastName']); ?>
                        <?php if ($conv['Unread'] > 0): ?>
                            <span class="msg-badge"><?php echo $conv['Unread']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="chat-list-preview"><?php echo htmlspecialchars(substr($conv['LastMessage'] ?? 'No messages', 0, 80)); ?></div>
                </div>
                <div class="chat-list-time"><?php echo $conv['LastDate'] ? date('M d H:i', strtotime($conv['LastDate'])) : ''; ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
