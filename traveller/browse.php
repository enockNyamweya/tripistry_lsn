<?php include __DIR__ . '/../includes/header.php'; requireTraveller(); ?>

<h1>Browse Tripistry</h1>

<div id="browse-lazy" class="browse-lazy"
     data-api-base="<?= htmlspecialchars(BASE_URL . '/api/index.php', ENT_QUOTES) ?>"
     data-base-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>"
     data-page-size="12">

<div class="browse-tabs">
    <button class="tab-btn active" onclick="showTab('destinations')">Destinations</button>
    <button class="tab-btn" onclick="showTab('flights')">Flights</button>
    <button class="tab-btn" onclick="showTab('accommodations')">Accommodations</button>
    <button class="tab-btn" onclick="showTab('restaurants')">Restaurants</button>
    <button class="tab-btn" onclick="showTab('attractions')">Attractions</button>
</div>

<!-- DESTINATIONS -->
<div id="tab-destinations" class="tab-content active">
    <p class="lazy-status" data-browse-status style="display:none"></p>
    <div class="card-grid" data-browse-content></div>
    <div class="infinite-sentinel" data-infinite-sentinel data-tab="destinations" aria-hidden="true"></div>
</div>

<!-- FLIGHTS -->
<div id="tab-flights" class="tab-content" style="display:none;">
    <p class="lazy-status" data-browse-status style="display:none"></p>
    <div data-browse-content></div>
    <div class="infinite-sentinel" data-infinite-sentinel data-tab="flights" aria-hidden="true"></div>
</div>

<!-- ACCOMMODATIONS -->
<div id="tab-accommodations" class="tab-content" style="display:none;">
    <p class="lazy-status" data-browse-status style="display:none"></p>
    <div class="card-grid" data-browse-content></div>
    <div class="infinite-sentinel" data-infinite-sentinel data-tab="accommodations" aria-hidden="true"></div>
</div>

<!-- RESTAURANTS -->
<div id="tab-restaurants" class="tab-content" style="display:none;">
    <p class="lazy-status" data-browse-status style="display:none"></p>
    <div class="card-grid" data-browse-content></div>
    <div class="infinite-sentinel" data-infinite-sentinel data-tab="restaurants" aria-hidden="true"></div>
</div>

<!-- ATTRACTIONS -->
<div id="tab-attractions" class="tab-content" style="display:none;">
    <p class="lazy-status" data-browse-status style="display:none"></p>
    <div class="card-grid" data-browse-content></div>
    <div class="infinite-sentinel" data-infinite-sentinel data-tab="attractions" aria-hidden="true"></div>
</div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
