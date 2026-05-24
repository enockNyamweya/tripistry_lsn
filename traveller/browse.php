<?php include __DIR__ . '/../includes/header.php'; requireTraveller(); ?>

<h1>Browse Tripistry</h1>

<div class="browse-tabs">
    <button class="tab-btn active" onclick="showTab('destinations')">Destinations</button>
    <button class="tab-btn" onclick="showTab('flights')">Flights</button>
    <button class="tab-btn" onclick="showTab('accommodations')">Accommodations</button>
    <button class="tab-btn" onclick="showTab('restaurants')">Restaurants</button>
    <button class="tab-btn" onclick="showTab('attractions')">Attractions</button>
</div>

<?php
$destinations = $pdo->query('SELECT * FROM DESTINATION ORDER BY Country, City')->fetchAll();
$flights = $pdo->query('SELECT * FROM FLIGHT ORDER BY DepartureTime')->fetchAll();

$accommodations = $pdo->query('
    SELECT a.*, d.City, d.Country
    FROM ACCOMMODATION a
    LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID
    ORDER BY a.StarRating DESC
')->fetchAll();

$restaurants = $pdo->query('
    SELECT r.*, d.City, d.Country
    FROM RESTAURANT r
    LEFT JOIN DESTINATION d ON r.DestinationID = d.DestinationID
    ORDER BY r.Rating DESC
')->fetchAll();

$attractions = $pdo->query('
    SELECT a.*, d.City, d.Country
    FROM ATTRACTION a
    LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID
    ORDER BY a.Name
')->fetchAll();
?>

<!-- DESTINATIONS -->
<div id="tab-destinations" class="tab-content active">
    <div class="card-grid">
        <?php foreach ($destinations as $dest): ?>
            <a href="<?= BASE_URL ?>/traveller/destination.php?id=<?= $dest['DestinationID'] ?>"
               class="card feature-card hover-lift">

                <?php if (!empty($dest['ImageURL'])): ?>
                    <img src="<?= htmlspecialchars($dest['ImageURL']) ?>"
                         alt="<?= htmlspecialchars($dest['City']) ?>"
                         class="card-img">
                <?php endif; ?>

                <div class="card-body">
                    <h3><?= htmlspecialchars($dest['City']) ?>, <?= htmlspecialchars($dest['Country']) ?></h3>
                    <p><?= htmlspecialchars(substr($dest['Description'] ?? '', 0, 120)) ?>...</p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- FLIGHTS -->
<div id="tab-flights" class="tab-content" style="display:none;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Airline</th><th>Flight #</th><th>From</th><th>To</th>
                <th>Departure</th><th>Arrival</th><th>Price</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($flights as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['Airline']) ?></td>
                <td><?= htmlspecialchars($f['FlightNumber']) ?></td>
                <td><?= htmlspecialchars($f['DepartureCity']) ?></td>
                <td><?= htmlspecialchars($f['ArrivalCity']) ?></td>
                <td><?= date('M d Y H:i', strtotime($f['DepartureTime'])) ?></td>
                <td><?= date('M d Y H:i', strtotime($f['ArrivalTime'])) ?></td>
                <td>R<?= number_format($f['Price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ACCOMMODATIONS -->
<div id="tab-accommodations" class="tab-content" style="display:none;">
    <div class="card-grid">
        <?php foreach ($accommodations as $acc): ?>
            <div class="card">
                <div class="card-body">
                    <h3><?= htmlspecialchars($acc['Name']) ?></h3>
                    <span class="badge"><?= htmlspecialchars($acc['Type']) ?></span>
                    <span class="stars"><?= str_repeat('★', $acc['StarRating']) ?></span>
                    <p>R<?= number_format($acc['PricePerNight'], 2) ?> / night</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- RESTAURANTS -->
<div id="tab-restaurants" class="tab-content" style="display:none;">
    <div class="card-grid">
        <?php foreach ($restaurants as $r): ?>
            <div class="card">
                <div class="card-body">
                    <h3><?= htmlspecialchars($r['Name']) ?></h3>
                    <span class="badge"><?= htmlspecialchars($r['CuisineType']) ?></span>
                    <p>Rating: <?= number_format($r['Rating'], 1) ?>/5</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ATTRACTIONS -->
<div id="tab-attractions" class="tab-content" style="display:none;">
    <div class="card-grid">
        <?php foreach ($attractions as $attr): ?>
            <div class="card">
                <div class="card-body">
                    <h3><?= htmlspecialchars($attr['Name']) ?></h3>
                    <span class="badge"><?= htmlspecialchars($attr['Type']) ?></span>
                    <p><?= htmlspecialchars(substr($attr['Description'] ?? '', 0, 120)) ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>