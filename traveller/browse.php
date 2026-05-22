<?php include __DIR__ . '/../includes/header.php'; requireTraveller(); ?>

<h1>Browse Tripistry_lsn</h1>

<div class="browse-tabs">
    <button class="tab-btn active" onclick="showTab('destinations')">Destinations</button>
    <button class="tab-btn" onclick="showTab('flights')">Flights</button>
    <button class="tab-btn" onclick="showTab('accommodations')">Accommodations</button>
    <button class="tab-btn" onclick="showTab('restaurants')">Restaurants</button>
    <button class="tab-btn" onclick="showTab('attractions')">Attractions</button>
</div>

<?php
// Destinations
$stmt = $pdo->query('SELECT * FROM DESTINATION ORDER BY Country, City');
$destinations = $stmt->fetchAll();

// Flights
$stmt = $pdo->query('SELECT * FROM FLIGHT ORDER BY DepartureTime');
$flights = $stmt->fetchAll();

// Accommodations
$stmt = $pdo->query('SELECT a.*, d.City, d.Country FROM ACCOMMODATION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID ORDER BY a.StarRating DESC');
$accommodations = $stmt->fetchAll();

// Restaurants
$stmt = $pdo->query('SELECT r.*, d.City, d.Country FROM RESTAURANT r LEFT JOIN DESTINATION d ON r.DestinationID = d.DestinationID ORDER BY r.Rating DESC');
$restaurants = $stmt->fetchAll();

// Attractions
$stmt = $pdo->query('SELECT a.*, d.City, d.Country FROM ATTRACTION a LEFT JOIN DESTINATION d ON a.DestinationID = d.DestinationID ORDER BY a.Name');
$attractions = $stmt->fetchAll();
?>

<div id="tab-destinations" class="tab-content active">
    <div class="card-grid">
        <?php foreach ($destinations as $dest): ?>
            <div class="card">
                <?php if ($dest['ImageURL']): ?>
                    <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($dest['ImageURL']); ?>" alt="<?php echo htmlspecialchars($dest['City']); ?>" class="card-img">
                <?php endif; ?>
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($dest['City']); ?>, <?php echo htmlspecialchars($dest['Country']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($dest['Description'] ?? '', 0, 150)); ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="tab-flights" class="tab-content" style="display:none;">
    <table class="data-table">
        <thead>
            <tr><th>Airline</th><th>Flight #</th><th>From</th><th>To</th><th>Departure</th><th>Arrival</th><th>Price</th></tr>
        </thead>
        <tbody>
            <?php foreach ($flights as $f): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['Airline']); ?></td>
                    <td><?php echo htmlspecialchars($f['FlightNumber']); ?></td>
                    <td><?php echo htmlspecialchars($f['DepartureCity']); ?></td>
                    <td><?php echo htmlspecialchars($f['ArrivalCity']); ?></td>
                    <td><?php echo date('M d Y H:i', strtotime($f['DepartureTime'])); ?></td>
                    <td><?php echo date('M d Y H:i', strtotime($f['ArrivalTime'])); ?></td>
                    <td>R<?php echo number_format($f['Price'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="tab-accommodations" class="tab-content" style="display:none;">
    <div class="card-grid">
        <?php foreach ($accommodations as $acc): ?>
            <div class="card">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($acc['Name']); ?></h3>
                    <span class="badge"><?php echo htmlspecialchars($acc['Type']); ?></span>
                    <span class="stars"><?php echo str_repeat('★', $acc['StarRating']); ?></span>
                    <p>R<?php echo number_format($acc['PricePerNight'], 2); ?> / night</p>
                    <p class="text-muted"><?php echo htmlspecialchars($acc['Address']); ?></p>
                    <?php if ($acc['City']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($acc['City'] . ', ' . $acc['Country']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="tab-restaurants" class="tab-content" style="display:none;">
    <div class="card-grid">
        <?php foreach ($restaurants as $r): ?>
            <div class="card">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($r['Name']); ?></h3>
                    <span class="badge"><?php echo htmlspecialchars($r['CuisineType']); ?></span>
                    <span class="badge"><?php echo htmlspecialchars($r['PriceRange']); ?></span>
                    <p>Rating: <?php echo number_format($r['Rating'], 1); ?> / 5</p>
                    <p class="text-muted"><?php echo htmlspecialchars($r['Address']); ?></p>
                    <?php if ($r['City']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($r['City'] . ', ' . $r['Country']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="tab-attractions" class="tab-content" style="display:none;">
    <div class="card-grid">
        <?php foreach ($attractions as $attr): ?>
            <div class="card">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($attr['Name']); ?></h3>
                    <span class="badge"><?php echo htmlspecialchars($attr['Type']); ?></span>
                    <p><?php echo htmlspecialchars(substr($attr['Description'] ?? '', 0, 120)); ?>...</p>
                    <p>Entry: R<?php echo number_format($attr['EntryFee'], 2); ?></p>
                    <p class="text-muted"><?php echo htmlspecialchars($attr['OpeningHours']); ?></p>
                    <?php if ($attr['City']): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($attr['City'] . ', ' . $attr['Country']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
