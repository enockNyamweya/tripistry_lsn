<?php
require_once __DIR__ . '/../includes/header.php';
requireTraveller();

$bookingId = $_GET['booking_id'] ?? null;

if (!$bookingId) {
    die('Invalid booking.');
}

$stmt = $pdo->prepare("
    SELECT
        b.BookingID,
        b.BookingDate,
        p.Title,
        p.Description,
        p.DurationDays
    FROM BOOKING b
    JOIN TRAVEL_PACKAGE p 
        ON b.PackageID = p.PackageID
    WHERE b.BookingID = ?
      AND b.UserID = ?
");

$stmt->execute([$bookingId, $_SESSION['user_id']]);

$trip = $stmt->fetch();

if (!$trip) {
    die('Trip not found.');
}

/*
|--------------------------------------------------------------------------
| Calendar Dates
|--------------------------------------------------------------------------
*/

$startDate = date('Ymd', strtotime($trip['BookingDate']));

$duration = max(1, (int)$trip['DurationDays']);

$endDate = date(
    'Ymd',
    strtotime($trip['BookingDate'] . " +{$duration} days")
);

$title = $trip['Title'];
$description = $trip['Description'] ?? 'Trip booked via Tripistry';

$filename = 'tripistry-trip-' . $trip['BookingID'] . '.ics';

/*
|--------------------------------------------------------------------------
| Generate ICS File
|--------------------------------------------------------------------------
*/

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//Tripistry//EN\r\n";

echo "BEGIN:VEVENT\r\n";
echo "UID:" . uniqid() . "@tripistry.com\r\n";
echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
echo "DTSTART;VALUE=DATE:" . $startDate . "\r\n";
echo "DTEND;VALUE=DATE:" . $endDate . "\r\n";
echo "SUMMARY:" . addslashes($title) . "\r\n";
echo "DESCRIPTION:" . addslashes($description) . "\r\n";
echo "END:VEVENT\r\n";

echo "END:VCALENDAR\r\n";

exit;