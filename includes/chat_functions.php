<?php
require_once __DIR__ . '/auth.php';

function getUnreadCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM MESSAGE WHERE ReceiverID = ? AND IsRead = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getConversations($userId) {
    global $pdo;
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Agency') {
        $stmt = $pdo->prepare('
            SELECT DISTINCT m.SenderID as PartnerID, t.FirstName, t.LastName, u.Email,
                (SELECT COUNT(*) FROM MESSAGE m2 WHERE m2.SenderID = m.SenderID AND m2.ReceiverID = ? AND m2.IsRead = 0) as Unread,
                (SELECT Message FROM MESSAGE m3 WHERE m3.SenderID = m.SenderID AND m3.ReceiverID = ? OR m3.SenderID = ? AND m3.ReceiverID = m.SenderID ORDER BY m3.SentDate DESC LIMIT 1) as LastMessage,
                (SELECT SentDate FROM MESSAGE m3 WHERE m3.SenderID = m.SenderID AND m3.ReceiverID = ? OR m3.SenderID = ? AND m3.ReceiverID = m.SenderID ORDER BY m3.SentDate DESC LIMIT 1) as LastDate
            FROM MESSAGE m
            JOIN TRAVELLER t ON m.SenderID = t.UserID
            JOIN USER u ON m.SenderID = u.UserID
            WHERE m.ReceiverID = ?
            ORDER BY LastDate DESC
        ');
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    } else {
        $stmt = $pdo->prepare('
            SELECT DISTINCT m.ReceiverID as PartnerID, ta.AgencyName as AgencyName, u.Email,
                (SELECT COUNT(*) FROM MESSAGE m2 WHERE m2.SenderID = m.ReceiverID AND m2.ReceiverID = ? AND m2.IsRead = 0) as Unread,
                (SELECT Message FROM MESSAGE m3 WHERE m3.SenderID = m.ReceiverID AND m3.ReceiverID = ? OR m3.SenderID = ? AND m3.ReceiverID = m.ReceiverID ORDER BY m3.SentDate DESC LIMIT 1) as LastMessage,
                (SELECT SentDate FROM MESSAGE m3 WHERE m3.SenderID = m.ReceiverID AND m3.ReceiverID = ? OR m3.SenderID = ? AND m3.ReceiverID = m.ReceiverID ORDER BY m3.SentDate DESC LIMIT 1) as LastDate
            FROM MESSAGE m
            JOIN TRAVEL_AGENCY ta ON m.ReceiverID = ta.UserID
            JOIN USER u ON m.ReceiverID = u.UserID
            WHERE m.SenderID = ?
            ORDER BY LastDate DESC
        ');
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    }
    return $stmt->fetchAll();
}

function getMessages($userId, $partnerId) {
    global $pdo;
    $stmt = $pdo->prepare('
        SELECT m.*, 
               us.Email as SenderEmail,
               ur.Email as ReceiverEmail,
               COALESCE(t.FirstName, ta.AgencyName) as SenderName
        FROM MESSAGE m
        JOIN USER us ON m.SenderID = us.UserID
        JOIN USER ur ON m.ReceiverID = ur.UserID
        LEFT JOIN TRAVELLER t ON m.SenderID = t.UserID
        LEFT JOIN TRAVEL_AGENCY ta ON m.SenderID = ta.UserID
        WHERE (m.SenderID = ? AND m.ReceiverID = ?) OR (m.SenderID = ? AND m.ReceiverID = ?)
        ORDER BY m.SentDate ASC
    ');
    $stmt->execute([$userId, $partnerId, $partnerId, $userId]);
    return $stmt->fetchAll();
}

function sendMessage($senderId, $receiverId, $packageId, $message) {
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO MESSAGE (SenderID, ReceiverID, PackageID, Message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$senderId, $receiverId, $packageId ?: null, $message]);
    return $pdo->lastInsertId();
}

function markAsRead($userId, $partnerId) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE MESSAGE SET IsRead = 1 WHERE ReceiverID = ? AND SenderID = ? AND IsRead = 0');
    $stmt->execute([$userId, $partnerId]);
}
