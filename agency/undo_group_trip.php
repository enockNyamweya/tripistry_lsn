<?php
require_once __DIR__ . '/../includes/header.php';
requireAgency();

header('Content-Type: text/plain');

$id = $_POST['id'] ?? null;
if (!$id) { http_response_code(400); echo "Missing ID"; exit; }

$stmt = $pdo->prepare("UPDATE GROUP_TRIP SET Status = 'Open' WHERE GroupTripID = ? AND AgencyID = ?");
$stmt->execute([(int)$id, $_SESSION['user_id']]);
echo "OK";
