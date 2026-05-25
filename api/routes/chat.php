<?php
// api/routes/chat.php — Private chat between travellers and agencies

function requireChatAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
    return (int)$_SESSION['user_id'];
}

function handleChatRequest($method, $resource, $subResource) {
    $userId = requireChatAuth();
    $pdo = Database::getInstance();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;

    switch ($resource) {
        case 'conversations':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['message' => 'Method not allowed']);
                return;
            }
            // List distinct users this user has exchanged messages with,
            // plus the last message and unread count
            $sql = "
                SELECT
                    other.UserID,
                    other.Email,
                    other.UserType,
                    COALESCE(ta.AgencyName, CONCAT(t.FirstName, ' ', t.LastName)) as DisplayName,
                    (SELECT m2.Message FROM MESSAGE m2
                     WHERE (m2.SenderID = :uid AND m2.ReceiverID = other.UserID)
                        OR (m2.SenderID = other.UserID AND m2.ReceiverID = :uid2)
                     ORDER BY m2.SentAt DESC LIMIT 1) as LastMessage,
                    (SELECT m2.SentAt FROM MESSAGE m2
                     WHERE (m2.SenderID = :uid3 AND m2.ReceiverID = other.UserID)
                        OR (m2.SenderID = other.UserID AND m2.ReceiverID = :uid4)
                     ORDER BY m2.SentAt DESC LIMIT 1) as LastMessageAt,
                    (SELECT COUNT(*) FROM MESSAGE m2
                     WHERE m2.SenderID = other.UserID AND m2.ReceiverID = :uid5 AND m2.IsRead = 0) as UnreadCount
                FROM USER other
                LEFT JOIN TRAVEL_AGENCY ta ON other.UserID = ta.UserID
                LEFT JOIN TRAVELLER t ON other.UserID = t.UserID
                WHERE other.UserID IN (
                    SELECT DISTINCT
                        CASE WHEN m.SenderID = :uid6 THEN m.ReceiverID ELSE m.SenderID END
                    FROM MESSAGE m
                    WHERE m.SenderID = :uid7 OR m.ReceiverID = :uid8
                )
                ORDER BY LastMessageAt DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId,
                ':uid4' => $userId, ':uid5' => $userId, ':uid6' => $userId,
                ':uid7' => $userId, ':uid8' => $userId
            ]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'messages':
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['message' => 'Method not allowed']);
                return;
            }
            $otherId = $subResource ? (int)$subResource : 0;
            if ($otherId < 1) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID required (chat/messages/{user_id})']);
                return;
            }

            // Mark messages from other user as read
            $mark = $pdo->prepare('UPDATE MESSAGE SET IsRead = 1 WHERE SenderID = ? AND ReceiverID = ? AND IsRead = 0');
            $mark->execute([$otherId, $userId]);

            $offset = ($page - 1) * $limit;
            $countStmt = $pdo->prepare('
                SELECT COUNT(*) FROM MESSAGE
                WHERE (SenderID = :uid AND ReceiverID = :oid)
                   OR (SenderID = :oid2 AND ReceiverID = :uid2)
            ');
            $countStmt->execute([
                ':uid' => $userId, ':oid' => $otherId,
                ':oid2' => $otherId, ':uid2' => $userId
            ]);
            $total = (int)$countStmt->fetchColumn();

            $msgStmt = $pdo->prepare('
                SELECT m.*,
                       CASE WHEN m.SenderID = :uid THEN 1 ELSE 0 END as IsMine
                FROM MESSAGE m
                WHERE (m.SenderID = :uid2 AND m.ReceiverID = :oid)
                   OR (m.SenderID = :oid2 AND m.ReceiverID = :uid3)
                ORDER BY m.SentAt ASC
                LIMIT :lim OFFSET :off
            ');
            $msgStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $msgStmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
            $msgStmt->bindValue(':uid3', $userId, PDO::PARAM_INT);
            $msgStmt->bindValue(':oid', $otherId, PDO::PARAM_INT);
            $msgStmt->bindValue(':oid2', $otherId, PDO::PARAM_INT);
            $msgStmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $msgStmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $msgStmt->execute();
            $messages = $msgStmt->fetchAll();

            // Get other user info
            $userStmt = $pdo->prepare('
                SELECT u.UserID, u.Email, u.UserType,
                       COALESCE(ta.AgencyName, CONCAT(t.FirstName, \' \', t.LastName)) as DisplayName
                FROM USER u
                LEFT JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
                LEFT JOIN TRAVELLER t ON u.UserID = t.UserID
                WHERE u.UserID = ?
            ');
            $userStmt->execute([$otherId]);
            $otherUser = $userStmt->fetch();

            echo json_encode([
                'success' => true,
                'other_user' => $otherUser,
                'pagination' => [
                    'total_records' => $total,
                    'total_pages' => (int)ceil($total / $limit),
                    'current_page' => $page,
                    'limit' => $limit
                ],
                'data' => $messages
            ]);
            break;

        case 'send':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['message' => 'Method not allowed']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $receiverId = isset($input['receiver_id']) ? (int)$input['receiver_id'] : 0;
            $message = trim($input['message'] ?? '');

            if ($receiverId < 1 || $message === '') {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'receiver_id and message are required.']);
                return;
            }

            // Verify receiver exists
            $check = $pdo->prepare('SELECT UserID, UserType FROM USER WHERE UserID = ?');
            $check->execute([$receiverId]);
            $receiver = $check->fetch();
            if (!$receiver) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Receiver not found.']);
                return;
            }

            $stmt = $pdo->prepare('INSERT INTO MESSAGE (SenderID, ReceiverID, Message) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $receiverId, $message]);
            $msgId = $pdo->lastInsertId();

            $fetch = $pdo->prepare('SELECT * FROM MESSAGE WHERE MessageID = ?');
            $fetch->execute([$msgId]);
            $newMsg = $fetch->fetch();
            $newMsg['IsMine'] = 1;

            echo json_encode(['success' => true, 'data' => $newMsg]);
            break;

        case 'contacts':
            // List potential contacts based on user type
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['message' => 'Method not allowed']);
                return;
            }
            $userType = $_SESSION['user_type'] ?? '';
            $search = trim($_GET['search'] ?? '');

            if ($userType === 'Traveller') {
                // Travellers can see all agencies (and agencies they've booked with)
                $where = "u.UserType = 'Agency'";
                $params = [];
                if ($search !== '') {
                    $where .= " AND (ta.AgencyName LIKE :search OR u.Email LIKE :search2)";
                    $params[':search'] = "%$search%";
                    $params[':search2'] = "%$search%";
                }
            } else {
                // Agencies can see travellers who have booked their packages
                $where = "u.UserType = 'Traveller' AND u.UserID IN (
                    SELECT DISTINCT b.UserID FROM BOOKING b
                    JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
                    WHERE p.AgencyID = :agency_id
                )";
                $params[':agency_id'] = $userId;
                if ($search !== '') {
                    $where .= " AND (CONCAT(t.FirstName, ' ', t.LastName) LIKE :search OR u.Email LIKE :search2)";
                    $params[':search'] = "%$search%";
                    $params[':search2'] = "%$search%";
                }
            }

            $sql = "
                SELECT u.UserID, u.Email, u.UserType,
                       COALESCE(ta.AgencyName, CONCAT(t.FirstName, ' ', t.LastName)) as DisplayName
                FROM USER u
                LEFT JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
                LEFT JOIN TRAVELLER t ON u.UserID = t.UserID
                WHERE $where
                ORDER BY DisplayName
                LIMIT 50
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        default:
            http_response_code(404);
            echo json_encode(['message' => "Chat resource '$resource' not recognized."]);
            break;
    }
}
