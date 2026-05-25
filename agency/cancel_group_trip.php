<?php
require_once '../includes/db.php';
session_start();

$id = $_POST['id'] ?? null;

if (!$id) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE GROUP_TRIP
    SET Status = 'Cancelled'
    WHERE GroupTripID = ?
");

$stmt->execute([$id]);

echo "OK";