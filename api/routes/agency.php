<?php
// api/routes/agency.php : session-scoped agency/admin list endpoints

function requireAgencyApi() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../../includes/auth.php';
    if (!isAgency()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Agency authentication required.']);
        exit;
    }
    return (int)$_SESSION['user_id'];
}

function handleAgencyRequest($method, $resource, $subResource) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        return;
    }

    $agencyId = requireAgencyApi();
    $pdo = Database::getInstance();

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 15;

    switch ($resource) {
        case 'packages':
            $where = ' FROM TRAVEL_PACKAGE p WHERE p.AgencyID = :agency_id';
            $params = [':agency_id' => $agencyId];
            $countQuery = 'SELECT COUNT(1)' . $where;
            $selectQuery = "
                SELECT p.*,
                    (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating,
                    (SELECT COUNT(*) FROM BOOKING b WHERE b.PackageID = p.PackageID) as BookingCount,
                    (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
            " . $where . ' ORDER BY p.PackageID DESC';
            echo json_encode(getPaginatedResponse($pdo, $countQuery, $selectQuery, $params, $page, $limit));
            break;

        case 'bookings':
            $where = '
                FROM BOOKING b
                JOIN TRAVEL_PACKAGE p ON b.PackageID = p.PackageID
                JOIN TRAVELLER t ON b.UserID = t.UserID
                JOIN USER u ON t.UserID = u.UserID
                WHERE p.AgencyID = :agency_id';
            $params = [':agency_id' => $agencyId];
            $countQuery = 'SELECT COUNT(1)' . $where;
            $selectQuery = "
                SELECT b.*, p.Title as PackageTitle, t.FirstName, t.LastName, NULL as PassportNum,
                    u.Phone as PhoneNumber, ROUND(b.TotalCost / p.Price) as NumTravellers
            " . $where . ' ORDER BY b.BookingDate DESC';
            echo json_encode(getPaginatedResponse($pdo, $countQuery, $selectQuery, $params, $page, $limit));
            break;

        case 'group-trips':
            $where = '
                FROM GROUP_TRIP g
                JOIN TRAVEL_PACKAGE p ON g.PackageID = p.PackageID
                WHERE g.AgencyID = :agency_id';
            $params = [':agency_id' => $agencyId];
            $countQuery = 'SELECT COUNT(1)' . $where;
            $selectQuery = "
                SELECT g.*, g.TripName as GroupName, 2 as MinParticipants, g.MaxCapacity as MaxParticipants,
                    COALESCE(g.Status, 'Open') as Status, CURRENT_DATE() as DepartureDate,
                    DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) as ReturnDate,
                    p.Title as PackageTitle, p.PackageID as PID,
                    (SELECT COUNT(*) FROM ENROLS ge WHERE ge.GroupTripID = g.GroupTripID) as EnrolmentCount,
                    (SELECT d.City FROM HAS_DESTINATION v JOIN DESTINATION d ON v.DestinationID = d.DestinationID WHERE v.PackageID = p.PackageID LIMIT 1) as DestinationCity
            " . $where . ' ORDER BY DepartureDate ASC';
            echo json_encode(getPaginatedResponse($pdo, $countQuery, $selectQuery, $params, $page, $limit));
            break;

        case 'available':
            $packageId = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
            if ($packageId < 1) {
                http_response_code(400);
                echo json_encode(['message' => 'package_id is required.']);
                return;
            }
            $own = $pdo->prepare('SELECT COUNT(1) FROM TRAVEL_PACKAGE WHERE PackageID = ? AND AgencyID = ?');
            $own->execute([$packageId, $agencyId]);
            if ((int)$own->fetchColumn() === 0) {
                http_response_code(403);
                echo json_encode(['message' => 'Package not found or access denied.']);
                return;
            }

            $type = $subResource ?? '';
            $maps = [
                'destinations' => [
                    'count' => 'SELECT COUNT(1) FROM DESTINATION WHERE DestinationID NOT IN (SELECT DestinationID FROM HAS_DESTINATION WHERE PackageID = :pid)',
                    'select' => 'SELECT DestinationID, City, Country, City as NameCol, Country as SubCol FROM DESTINATION WHERE DestinationID NOT IN (SELECT DestinationID FROM HAS_DESTINATION WHERE PackageID = :pid) ORDER BY Country, City',
                    'id' => 'DestinationID', 'add_type' => 'destination',
                ],
                'flights' => [
                    'count' => 'SELECT COUNT(1) FROM FLIGHT WHERE FlightID NOT IN (SELECT FlightID FROM INCLUDES_FLIGHT WHERE PackageID = :pid)',
                    'select' => 'SELECT FlightID, Airline, FlightNumber, Airline as NameCol, FlightNumber as SubCol FROM FLIGHT WHERE FlightID NOT IN (SELECT FlightID FROM INCLUDES_FLIGHT WHERE PackageID = :pid) ORDER BY DepartureTime',
                    'id' => 'FlightID', 'add_type' => 'flight',
                ],
                'accommodations' => [
                    'count' => 'SELECT COUNT(1) FROM ACCOMMODATION WHERE AccommodationID NOT IN (SELECT AccommodationID FROM INCLUDES_ACCOM WHERE PackageID = :pid)',
                    'select' => 'SELECT AccommodationID, Name, Type, Name as NameCol, Type as SubCol FROM ACCOMMODATION WHERE AccommodationID NOT IN (SELECT AccommodationID FROM INCLUDES_ACCOM WHERE PackageID = :pid) ORDER BY Name',
                    'id' => 'AccommodationID', 'add_type' => 'accommodation',
                ],
                'restaurants' => [
                    'count' => 'SELECT COUNT(1) FROM RESTAURANT WHERE RestaurantID NOT IN (SELECT RestaurantID FROM INCLUDES_RESTAURANT WHERE PackageID = :pid)',
                    'select' => 'SELECT RestaurantID, Name, CuisineType, Name as NameCol, CuisineType as SubCol FROM RESTAURANT WHERE RestaurantID NOT IN (SELECT RestaurantID FROM INCLUDES_RESTAURANT WHERE PackageID = :pid) ORDER BY Name',
                    'id' => 'RestaurantID', 'add_type' => 'restaurant',
                ],
                'attractions' => [
                    'count' => 'SELECT COUNT(1) FROM ATTRACTION WHERE AttractionID NOT IN (SELECT AttractionID FROM INCLUDES_ATTRACTION WHERE PackageID = :pid)',
                    'select' => 'SELECT AttractionID, Name, Type, Name as NameCol, Type as SubCol FROM ATTRACTION WHERE AttractionID NOT IN (SELECT AttractionID FROM INCLUDES_ATTRACTION WHERE PackageID = :pid) ORDER BY Name',
                    'id' => 'AttractionID', 'add_type' => 'attraction',
                ],
            ];

            if (!isset($maps[$type])) {
                http_response_code(404);
                echo json_encode(['message' => "Unknown available type '$type'."]);
                return;
            }

            $cfg = $maps[$type];
            $params = [':pid' => $packageId];
            $response = getPaginatedResponse($pdo, $cfg['count'], $cfg['select'], $params, $page, $limit);
            $response['meta'] = ['add_type' => $cfg['add_type'], 'id_field' => $cfg['id']];
            echo json_encode($response);
            break;

        default:
            http_response_code(404);
            echo json_encode(['message' => "Agency resource '$resource' not recognized."]);
            break;
    }
}
