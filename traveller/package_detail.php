<?php include __DIR__ . '/../includes/header.php'; requireTraveller();

$packageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$packageId) {
    header('Location: packages.php');
    exit;
}

// Package with agency info
$stmt = $pdo->prepare('
    SELECT p.*, ta.AgencyName, ta.VerificationStatus, u.Email as AgencyEmail, u.UserID as AgencyUserID,
        (SELECT AVG(RatingScore) FROM REVIEW r2 WHERE r2.PackageID = p.PackageID) as AvgRating,
        (SELECT COUNT(*) FROM REVIEW r3 WHERE r3.PackageID = p.PackageID) as ReviewCount
    FROM PACKAGE p
    JOIN CURATES c ON p.PackageID = c.PackageID
    JOIN USER u ON c.UserID = u.UserID
    JOIN TRAVEL_AGENCY ta ON u.UserID = ta.UserID
    WHERE p.PackageID = ?
');
$stmt->execute([$packageId]);
$package = $stmt->fetch();

if (!$package) {
    echo '<p class="empty-state">Package not found.</p>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Destinations
$stmt = $pdo->prepare('SELECT d.* FROM DESTINATION d JOIN VISITS v ON d.DestinationID = v.DestinationID WHERE v.PackageID = ?');
$stmt->execute([$packageId]);
$destinations = $stmt->fetchAll();

// Flights
$stmt = $pdo->prepare('SELECT f.* FROM FLIGHT f JOIN INCLUDES_FLIGHT i ON f.FlightID = i.FlightID WHERE i.PackageID = ?');
$stmt->execute([$packageId]);
$flights = $stmt->fetchAll();

// Accommodations
$stmt = $pdo->prepare('SELECT a.* FROM ACCOMODATION a JOIN INCLUDES_STAY i ON a.AccomodationID = i.AccomodationID WHERE i.PackageID = ?');
$stmt->execute([$packageId]);
$accommodations = $stmt->fetchAll();

// Restaurants
$stmt = $pdo->prepare('SELECT r.* FROM RESTAURANT r JOIN PACKAGE_RESTAURANT pr ON r.RestaurantID = pr.RestaurantID WHERE pr.PackageID = ?');
$stmt->execute([$packageId]);
$restaurants = $stmt->fetchAll();

// Attractions
$stmt = $pdo->prepare('SELECT a.* FROM ATTRACTION a JOIN PACKAGE_ATTRACTION pa ON a.AttractionID = pa.AttractionID WHERE pa.PackageID = ?');
$stmt->execute([$packageId]);
$attractions = $stmt->fetchAll();

// Reviews
$stmt = $pdo->prepare('SELECT r.*, t.FirstName, t.LastName FROM REVIEW r JOIN TRAVELLER t ON r.UserID = t.UserID WHERE r.PackageID = ? ORDER BY r.DatePosted DESC');
$stmt->execute([$packageId]);
$reviews = $stmt->fetchAll();

// Check if user already reviewed
$hasReviewed = false;
if (isTraveller()) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM REVIEW WHERE UserID = ? AND PackageID = ?');
    $stmt->execute([$_SESSION['user_id'], $packageId]);
    $hasReviewed = $stmt->fetchColumn() > 0;
}

// Check if user already booked this package
$hasBooked = false;
$stmt = $pdo->prepare('SELECT COUNT(*) FROM BOOKS WHERE UserID = ? AND PackageID = ?');
$stmt->execute([$_SESSION['user_id'], $packageId]);
$hasBooked = $stmt->fetchColumn() > 0;

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'review') {
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $reviewError = 'Please provide a rating between 1 and 5.';
    } elseif (!$hasBooked) {
        $reviewError = 'You can only review packages you have booked.';
    } elseif ($hasReviewed) {
        $reviewError = 'You have already reviewed this package.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO REVIEW (UserID, PackageID, Comment, RatingScore) VALUES (?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], $packageId, $comment, $rating]);
        header("Location: package_detail.php?id=$packageId&reviewed=1");
        exit;
    }
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $numTravellers = max(1, (int)($_POST['num_travellers'] ?? 1));
    $totalCost = $package['Price'] * $numTravellers;

    $stmt = $pdo->prepare('INSERT INTO BOOKS (UserID, PackageID, TotalCost, NumTravellers, Status) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$_SESSION['user_id'], $packageId, $totalCost, $numTravellers, 'Confirmed']);
    header("Location: package_detail.php?id=$packageId&booked=1");
    exit;
}
?>

<div class="package-detail">
    <?php if (isset($_GET['reviewed'])): ?>
        <div class="alert alert-success">Your review has been submitted. Thank you!</div>
    <?php endif; ?>
    <?php if (isset($_GET['booked'])): ?>
        <div class="alert alert-success">Package booked successfully! Check My Bookings for details.</div>
    <?php endif; ?>

    <div class="detail-header">
        <?php if ($package['ImageURL']): ?>
            <img src="<?php echo htmlspecialchars($package['ImageURL']); ?>" alt="<?php echo htmlspecialchars($package['Title']); ?>" class="detail-hero-img">
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($package['Title']); ?></h1>
        <div class="detail-meta">
            <span class="agency-badge"><?php echo htmlspecialchars($package['AgencyName']); ?></span>
            <?php if ($package['VerificationStatus'] === 'Verified'): ?>
                <span class="verified-badge">Verified Agency</span>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/traveller/chat.php?user=<?php echo $package['AgencyUserID']; ?>" class="btn btn-secondary btn-sm">Message Agency</a>
            <?php if ($package['AvgRating']): ?>
                <span class="rating-badge"><?php echo str_repeat('★', round($package['AvgRating'])); ?> <?php echo number_format($package['AvgRating'], 1); ?></span>
            <?php endif; ?>
        </div>
        <p class="detail-price">R<?php echo number_format($package['Price'], 2); ?> <small>/ person</small></p>
        <p><?php echo nl2br(htmlspecialchars($package['Description'])); ?></p>
    </div>

    <div class="detail-grid">
        <div class="detail-section">
            <h2>Itinerary</h2>
            <table class="data-table">
                <tr><th>Duration</th><td><?php echo $package['DurationDays']; ?> days</td></tr>
                <tr><th>Start Date</th><td><?php echo date('F j, Y', strtotime($package['StartDate'])); ?></td></tr>
                <tr><th>End Date</th><td><?php echo date('F j, Y', strtotime($package['EndDate'])); ?></td></tr>
                <tr><th>Max Travellers</th><td><?php echo $package['MaxTravellers']; ?></td></tr>
                <tr><th>Group Trip</th><td><?php echo $package['IsGroupTrip'] ? 'Yes' : 'No'; ?></td></tr>
            </table>
        </div>

        <div class="detail-section">
            <h2>Destinations</h2>
            <?php foreach ($destinations as $dest): ?>
                <div class="detail-item">
                    <strong><?php echo htmlspecialchars($dest['City']); ?>, <?php echo htmlspecialchars($dest['Country']); ?></strong>
                    <p class="text-muted"><?php echo htmlspecialchars(substr($dest['Description'] ?? '', 0, 200)); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="detail-section">
            <h2>Flights</h2>
            <?php foreach ($flights as $f): ?>
                <div class="detail-item">
                    <strong><?php echo htmlspecialchars($f['Airline']); ?> #<?php echo htmlspecialchars($f['FlightNumber']); ?></strong>
                    <p><?php echo htmlspecialchars($f['DepartureCity']); ?> → <?php echo htmlspecialchars($f['ArrivalCity']); ?></p>
                    <p class="text-muted"><?php echo date('M d H:i', strtotime($f['DepartureTime'])); ?> — <?php echo date('M d H:i', strtotime($f['ArrivalTime'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="detail-section">
            <h2>Accommodation</h2>
            <?php foreach ($accommodations as $a): ?>
                <div class="detail-item">
                    <strong><?php echo htmlspecialchars($a['Name']); ?></strong>
                    <span class="badge"><?php echo htmlspecialchars($a['Type']); ?></span>
                    <span class="stars"><?php echo str_repeat('★', $a['StarRating']); ?></span>
                    <p>R<?php echo number_format($a['PricePerNight'], 2); ?> / night</p>
                    <p class="text-muted"><?php echo htmlspecialchars($a['Address']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="detail-section">
            <h2>Restaurants</h2>
            <?php foreach ($restaurants as $r): ?>
                <div class="detail-item">
                    <strong><?php echo htmlspecialchars($r['Name']); ?></strong>
                    <span class="badge"><?php echo htmlspecialchars($r['CuisineType']); ?></span>
                    <span class="badge"><?php echo htmlspecialchars($r['PriceRange']); ?></span>
                    <p class="text-muted"><?php echo htmlspecialchars($r['Address']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="detail-section">
            <h2>Attractions</h2>
            <?php foreach ($attractions as $attr): ?>
                <div class="detail-item">
                    <strong><?php echo htmlspecialchars($attr['Name']); ?></strong>
                    <span class="badge"><?php echo htmlspecialchars($attr['Type']); ?></span>
                    <p>Entry: R<?php echo number_format($attr['EntryFee'], 2); ?></p>
                    <p class="text-muted"><?php echo htmlspecialchars($attr['OpeningHours']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="booking-section">
        <h2>Book This Package</h2>
        <form method="POST" action="" class="booking-form">
            <input type="hidden" name="action" value="book">
            <div class="form-group" style="flex:1;min-width:140px;">
                <label for="num_travellers">Number of Travellers</label>
                <input type="number" id="num_travellers" name="num_travellers" value="1" min="1" max="<?php echo $package['MaxTravellers']; ?>" required>
            </div>
            <div class="form-group" style="flex:1;min-width:160px;">
                <label for="trip_start">Trip Start Date</label>
                <input type="date" id="trip_start" value="<?php echo $package['StartDate']; ?>" onchange="calcEndDate()">
            </div>
            <div class="form-group" style="flex:0.6;min-width:90px;">
                <label for="trip_days">Days</label>
                <input type="number" id="trip_days" value="<?php echo $package['DurationDays']; ?>" min="1" onchange="calcEndDate()" style="text-align:center;">
            </div>
            <div class="form-group" style="flex:1;min-width:160px;">
                <label>Calculated End Date</label>
                <span id="calcEndDate" class="calc-end-date"><?php 
                    $end = new DateTime($package['StartDate']);
                    $end->modify('+'.($package['DurationDays']-1).' days');
                    echo $end->format('F j, Y');
                ?></span>
            </div>
            <div style="width:100%;">
                <p><strong>Total Cost:</strong> R<?php echo number_format($package['Price'], 2); ?> x <span id="travellerCount">1</span> = <strong>R<span id="totalCost"><?php echo number_format($package['Price'], 2); ?></span></strong></p>
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Book Now</button>
        </form>
    </div>

    <div class="reviews-section">
        <h2>Reviews (<?php echo $package['ReviewCount']; ?>)</h2>

        <?php if (isTraveller() && $hasBooked && !$hasReviewed): ?>
            <div class="review-form">
                <h3>Leave a Review</h3>
                <?php if (isset($reviewError)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($reviewError); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="review">
                    <div class="form-group">
                        <label>Rating</label>
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comment">Comment</label>
                        <textarea id="comment" name="comment" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            </div>
        <?php elseif (isTraveller() && $hasReviewed): ?>
            <p class="text-muted">You have already reviewed this package.</p>
        <?php elseif (isTraveller() && !$hasBooked): ?>
            <p class="text-muted">Book this package to leave a review.</p>
        <?php endif; ?>

        <?php if (empty($reviews)): ?>
            <p>No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
                <div class="review-card">
                    <div class="review-header">
                        <strong><?php echo htmlspecialchars($rev['FirstName'] . ' ' . $rev['LastName']); ?></strong>
                        <span class="stars"><?php echo str_repeat('★', $rev['RatingScore']); ?></span>
                        <span class="text-muted"><?php echo date('M d Y', strtotime($rev['DatePosted'])); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($rev['Comment']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    var price = <?php echo $package['Price']; ?>;
    var numInput = document.getElementById('num_travellers');
    var countSpan = document.getElementById('travellerCount');
    var totalSpan = document.getElementById('totalCost');
    if (numInput) {
        numInput.addEventListener('input', function() {
            var count = parseInt(this.value) || 1;
            countSpan.textContent = count;
            totalSpan.textContent = (price * count).toLocaleString('en-ZA', { minimumFractionDigits: 2 });
        });
    }

    window.calcEndDate = function() {
        var startInput = document.getElementById('trip_start');
        var daysInput = document.getElementById('trip_days');
        var display = document.getElementById('calcEndDate');
        if (startInput && daysInput && display) {
            var start = new Date(startInput.value + 'T00:00:00');
            var days = parseInt(daysInput.value) || 1;
            if (!isNaN(start.getTime())) {
                start.setDate(start.getDate() + days - 1);
                var options = { year: 'numeric', month: 'long', day: 'numeric' };
                display.textContent = start.toLocaleDateString('en-US', options);
            } else {
                display.textContent = '—';
            }
        }
    };
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
