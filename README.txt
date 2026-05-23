================================================================================
  TRIPISTRY — Travel Package Management Web Application
  COS 221 Practical Assignment 5 (Task 5: Web-Based Application)
================================================================================

Authors:   Angela Ramaboea, Patrick Simuyemba, Nicole Bare, Enock Nyamweya, Angelo Anthony
Module:    COS 221
Tutor:     Johan Nel (Johan.nel@tuks.co.za)

================================================================================
1.  PROJECT OVERVIEW
================================================================================

Tripistry is a full-stack web application built from scratch using vanilla
PHP, HTML5, CSS3, and JavaScript — no frameworks or external libraries.
It manages a travel booking platform where Travellers can browse, compare,
book, and review travel packages, while Travel Agencies can create, edit, and
delete packages and manage associated entities (destinations, flights,
accommodation, restaurants, and attractions).

The database is derived directly from the EER-to-Relational mapping (Steps 1–9)
and contains all 17 relations plus additional tables for restaurants and
attractions as required by the project specification.

================================================================================
2.  ARCHITECTURE & FILE STRUCTURE
================================================================================

tripistry/
├── config/
│   └── database.php           # PDO database connection (singleton pattern)
├── includes/
│   ├── auth.php               # Authentication functions (login, register, role guards)
│   ├── header.php             # Shared HTML head, navbar with role-based links
│   └── footer.php             # Shared footer + closing tags + JS include
├── sql/
│   └── setup.sql              # Full database schema + seed data
├── assets/
│   ├── css/style.css          # Responsive CSS (600+ lines)
│   └── js/main.js             # Client-side interactivity (tabs, alerts, totals)
│
├── index.php                  # Landing page (hero + feature cards)
├── login.php                  # Login form (email + password)
├── register.php               # Registration with dynamic Traveller/Agency fields
├── logout.php                 # Session destroy + redirect
├── dashboard.php              # Role-based routing
│
├── traveller/                  # TRAVELLER SECTION
│   ├── browse.php             # Tabbed view: Destinations | Flights | Accoms | Restaurants | Attractions
│   ├── packages.php           # Package listing with search/sort/filter + comparison
│   ├── package_detail.php     # Full detail view with booking form + review submission
│   └── bookings.php           # Booking history for the logged-in traveller
│
├── agency/                     # AGENCY SECTION
│   ├── dashboard.php          # Stats dashboard + recent bookings table
│   ├── packages.php           # CRUD list with edit/delete actions
│   ├── create_package.php     # Multi-field form with entity checkboxes + group trip config
│   ├── edit_package.php       # Pre-populated edit form with association management
│   ├── manage_items.php       # Granular add/remove of associated entities
│   └── group_trips.php        # Group trip status management (Open/Closed/Cancelled)
│
└── uploads/                   # Image upload directory (reserved for future use)

================================================================================
3.  DATABASE DESIGN (Derived from EER-to-Relational Mapping)
================================================================================

3.1  Relation Summary (19 tables)
---------------------------------------------------------------

 1. USER             — Superclass: UserID, Email, Password, UserType, DateCreated
 2. TRAVELLER        — Subclass (UserID FK): FirstName, LastName, PassportNum
 3. TRAVEL_AGENCY    — Subclass (UserID FK): AgencyName, VerificationStatus, CommissionRate
 4. DESTINATION      — City, Country, Latitude, Longitude, Description, ImageURL
 5. FLIGHT           — Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price
 6. ACCOMODATION     — Name, Type, StarRating, PricePerNight, Address, DestinationID FK
 7. RESTAURANT       — Name, CuisineType, PriceRange, Address, Rating, DestinationID FK
 8. ATTRACTION       — Name, Type, EntryFee, Description, OpeningHours, DestinationID FK
 9. PACKAGE          — Title, Description, Price, DurationDays, StartDate, EndDate, MaxTravellers, IsGroupTrip, ImageURL, Status

10. CURATES           — M:N Agency–Package (UserID, PackageID) [PK on PackageID]
11. VISITS            — M:N Package–Destination (PackageID, DestinationID)
12. INCLUDES_FLIGHT   — 1:N Package–Flight (PackageID, FlightID) [PK on FlightID]
13. INCLUDES_STAY     — 1:N Package–Accommodation (PackageID, AccomodationID) [PK on AccomodationID]
14. PACKAGE_RESTAURANT— M:N Package–Restaurant (PackageID, RestaurantID)
15. PACKAGE_ATTRACTION— M:N Package–Attraction (PackageID, AttractionID)
16. REVIEW            — Weak entity: ReviewID, UserID, PackageID, Comment, RatingScore, DatePosted
17. BOOKS             — Booking relation: BookingID, UserID, PackageID, BookingDate, TotalCost, NumTravellers, Status
18. GROUP_TRIP        — GroupTripID, PackageID FK, GroupName, MinParticipants, MaxParticipants, Status, Dates
19. GROUP_TRIP_ENROLMENT — EnrolmentID, GroupTripID FK, UserID FK, EnrolmentDate, Status

Additional tables from Step 6 (Multivalued Attributes):
20. TRAVELLER_PHONE   — (UserID, PhoneNumber)
21. ACCOMODATION_AMENITY — (AccomodationID, Amenity)

3.2  Key Design Decisions
---------------------------------------------------------------
- Specialisation: Option 8A used — separate TRAVELLER and TRAVEL_AGENCY
  tables, each with UserID as PK/FK to USER. Disjoint constraint enforced
  by UserType ENUM and application-level logic.
- Weak Entity (REVIEW): Identified by UserID (traveller) + PackageID + ReviewID.
  ReviewID placed first in composite PK to satisfy MySQL 8.0 AUTO_INCREMENT rules.
- M:N Relationships: Implemented as cross-reference tables (VISITS,
  INCLUDES_FLIGHT, INCLUDES_STAY, PACKAGE_RESTAURANT, PACKAGE_ATTRACTION).
- 1:N Relationships: Cross-reference approach for CURATES (one agency per
  package — PK on PackageID ensures uniqueness).
- All FKs use ON DELETE CASCADE for referential integrity.
- Price stored as DECIMAL(12,2) for financial precision (no floating-point issues).
- DurationDays is stored rather than derived for query performance.

================================================================================
4.  REQUIREMENT-BY-REQUIREMENT IMPLEMENTATION
================================================================================

4.1  "Log in and manage two distinct user types: Travellers and Travel Agencies"
---------------------------------------------------------------
IMPLEMENTATION:
  - Registration: register.php with dynamic form fields. User selects
    'Traveller' or 'Agency'; JavaScript shows/hides relevant inputs.
    Superclass row inserted into USER, subclass row into TRAVELLER or
    TRAVEL_AGENCY in a single transaction (includes/auth.php: registerUser()).
  - Login: login.php verifies email + bcrypt-hashed password against USER
    table. Session stores user_id, user_type, email.
  - Role Guards: includes/auth.php provides requireLogin(), requireTraveller(),
    requireAgency() — each page calls the appropriate guard which redirects
    unauthorised users.
  - Navbar: Dynamically renders different links based on user_type stored in
    session (includes/header.php lines 19–34).
  - Logout: Destroys session and redirects to login page.

FILES: login.php, register.php, logout.php, includes/auth.php, includes/header.php
TABLES: USER, TRAVELLER, TRAVEL_AGENCY

4.2  "Allow travellers to browse destinations, flights, accommodations,
     tourist attractions and restaurants"
---------------------------------------------------------------
IMPLEMENTATION:
  - traveller/browse.php implements a 5-tab interface using pure CSS/JS
    (no framework). Each tab is a <div> toggled by showTab() in main.js.
  - Destinations: Card grid with city/country, description, Unsplash image.
  - Flights: Sortable HTML table with airline, flight number, route, times, price.
  - Accommodations: Card grid with name, type badge, star rating, price/night, address.
  - Restaurants: Card grid with cuisine type badge, price range, rating.
  - Attractions: Card grid with type badge, entry fee, opening hours.
  - All data fetched from the database via PDO queries with no user input
    (read-only browsing — no injection risk but still uses PDO for consistency).

FILES: traveller/browse.php, assets/js/main.js (showTab function)
TABLES: DESTINATION, FLIGHT, ACCOMODATION, RESTAURANT, ATTRACTION

4.3  "Allow travellers to compare travel packages across different
     agencies and book a selected package"
---------------------------------------------------------------
IMPLEMENTATION:
  Comparison:
  - traveller/packages.php lists all Active packages with agency name,
    price, rating, destination, duration.
  - Each package card has a "Compare" button. Clicking it appends the
    package ID to a ?compare= URL parameter (comma-separated, max 3).
  - When compare IDs are present, a comparison table is rendered ABOVE the
    listing showing: agency, price, duration, destination, rating,
    max travellers, group trip status — side by side.
  
  Booking:
  - traveller/package_detail.php shows a "Book This Package" form.
  - User selects number of travellers. JavaScript updates total cost
    (price × count) in real-time.
  - On submit, a BOOKS row is inserted with TotalCost = Price × NumTravellers,
    status = 'Confirmed'. Confirmation message appears.
  - Bookings appear in traveller/bookings.php (booking history).

FILES: traveller/packages.php, traveller/package_detail.php, traveller/bookings.php
TABLES: PACKAGE, CURATES, USER, TRAVEL_AGENCY, BOOKS

4.4  "Allow travellers to leave reviews and ratings for agencies
     and their packages"
---------------------------------------------------------------
IMPLEMENTATION:
  - Review form embedded in traveller/package_detail.php below the package
    details.
  - Star rating uses CSS-only radio button technique: 5 radio inputs with
    labels styled as stars. Reverse flexbox direction ensures correct
    hover/fill behaviour. No JavaScript needed for the stars.
  - Three business rules enforced server-side:
      1. User must be logged in as Traveller.
      2. User must have previously BOOKED the package (check BOOKS table).
      3. User cannot review the same package twice (check REVIEW table).
  - On submit: INSERT into REVIEW with UserID, PackageID, Comment, RatingScore.
  - Reviews display below the form sorted by newest first, showing
    traveller name (from TRAVELLER table), star rating, comment, date.
  - Average rating computed via SQL AVG() subquery on the REVIEW table
    and displayed as stars + numeric score.

FILES: traveller/package_detail.php (review section, lines ~100–180)
TABLES: REVIEW, BOOKS, TRAVELLER

4.5  "Allow travel agencies to create, edit and delete travel
     packages and group trip offerings"
---------------------------------------------------------------
IMPLEMENTATION:
  Create Package (agency/create_package.php):
  - Comprehensive form with fields: title*, price*, description, duration,
    dates, max travellers, image URL, status dropdown.
  - "Group Trip" checkbox toggles GROUP_TRIP settings section (JS).
  - Associated entities selected via checkboxes in scrollable lists:
    destinations, flights, accommodations, restaurants, attractions.
  - On submit: PACKAGE row inserted, CURATES links agency, then
    INSERT IGNORE for each association table (VISITS, INCLUDES_FLIGHT,
    INCLUDES_STAY, PACKAGE_RESTAURANT, PACKAGE_ATTRACTION).
  - If group trip enabled: GROUP_TRIP row auto-created.
  - All in a single database transaction (PDO beginTransaction/commit).

  Edit Package (agency/edit_package.php):
  - Pre-populates all form fields from existing PACKAGE data.
  - Checkboxes pre-checked based on current associations (fetched from
    VISITS, INCLUDES_FLIGHT, etc.).
  - On submit: UPDATE PACKAGE, DELETE all old associations, re-INSERT
    selected ones. GROUP_TRIP updated or created.
  - Ownership verified: only the curating agency can edit.

  Delete Package (agency/packages.php):
  - "Delete" button with JavaScript confirm() dialog.
  - Server verifies ownership before DELETE.
  - CASCADE deletes clean up all associated records automatically.

  Group Trip Management (agency/group_trips.php):
  - Lists all group trips for the agency with participant counts.
  - Status toggle: Open ↔ Closed, Cancel (with confirmation).
  - Links to edit the underlying package.

FILES: agency/create_package.php, agency/edit_package.php, agency/packages.php,
       agency/group_trips.php
TABLES: PACKAGE, CURATES, GROUP_TRIP, and all association tables

4.6  "Allow travel agencies to manage the destinations, flights,
     accommodations, restaurants and attractions associated with a package"
---------------------------------------------------------------
IMPLEMENTATION:
  - agency/manage_items.php provides a dedicated interface per package.
  - Five sections (one per entity type), each showing:
      * Currently associated items with "Remove" button.
      * Dropdown of available (not-yet-associated) items with "Add" button.
  - "Remove" deletes the cross-reference row.
  - "Add" INSERT IGNOREs into the appropriate association table.
  - Real-time: page refreshes after each action with success feedback.
  - Ownership verified before any operation.
  - Also integrated into create/edit package forms as checkboxes.

FILES: agency/manage_items.php
TABLES: VISITS, INCLUDES_FLIGHT, INCLUDES_STAY, PACKAGE_RESTAURANT, PACKAGE_ATTRACTION

4.7  "Sort and filter packages based on various criteria
     (price, destination, duration, rating, etc.)"
---------------------------------------------------------------
IMPLEMENTATION:
  - traveller/packages.php has a filter bar at the top with:
      * Text search: Searches Title, Description, AgencyName (LIKE %?%)
      * Destination dropdown: Filters by City or Country (subquery on VISITS)
      * Min/Max Price: Numeric range filter on Price column
      * Sort dropdown: 8 options —
          Price: Low-High, High-Low
          Rating: High-Low, Low-High
          Duration: Short-Long, Long-Short
          Date: Earliest first
          Title: A-Z
  - All filters composable (search + destination + price range + sort
    applied simultaneously).
  - Query built dynamically in PHP with parameterised WHERE clauses.
  - AvgRating computed via subquery in SELECT; HAVING clause for rating filter.
  - "Clear" button resets all filters.

FILES: traveller/packages.php (lines ~8–78: query building logic)
TABLES: PACKAGE, CURATES, USER, TRAVEL_AGENCY, REVIEW, VISITS, DESTINATION

4.8  "Display a detailed package view including pricing, itinerary,
     images, agency information and reviews"
---------------------------------------------------------------
IMPLEMENTATION:
  - traveller/package_detail.php renders a complete single-package view:
      * Hero image (from ImageURL field, Unsplash CDN)
      * Title, agency name with verification badge, star rating
      * Price per person (large, bold)
      * Full description with line breaks preserved
  - Itinerary section: table with duration, dates, max travellers, group trip flag
  - Destinations section: city, country, description snippet per destination
  - Flights section: airline, flight number, route, departure/arrival times
  - Accommodation section: name, type badge, star rating, price/night, address
  - Restaurants section: name, cuisine type, price range, address
  - Attractions section: name, type, entry fee, opening hours
  - Booking section: traveller count input with live total cost calculation
  - Reviews section: all reviews with name, stars, date, comment
  - Agency info: name, verification status throughout
  - Two-column responsive grid layout (collapses to single column on mobile)

FILES: traveller/package_detail.php
TABLES: PACKAGE, CURATES, USER, TRAVEL_AGENCY, VISITS, DESTINATION,
        INCLUDES_FLIGHT, FLIGHT, INCLUDES_STAY, ACCOMODATION,
        PACKAGE_RESTAURANT, RESTAURANT, PACKAGE_ATTRACTION, ATTRACTION,
        REVIEW, TRAVELLER

4.9  "SQL injection prevention"
---------------------------------------------------------------
IMPLEMENTATION:
  - 100% of database queries use PDO Prepared Statements with parameterised
    placeholders (? or named :param). No user input is ever concatenated
    into SQL strings.

  - PDO configuration (config/database.php):
      * ATTR_EMULATE_PREPARES => false
        Forces real server-side prepared statements; prevents client-side
        escaping bypasses.
      * ATTR_ERRMODE => ERRMODE_EXCEPTION
        All PDO errors throw exceptions (fail-closed, not fail-open).
      * utf8mb4 charset ensures proper encoding.

  - Dynamic query building (e.g., package filters):
    WHERE clauses are built by conditionally appending SQL with ?
    placeholders, and parameters are accumulated in an array passed to
    PDOStatement::execute($params). The query string NEVER contains user
    values — only placeholders.

  Example from traveller/packages.php:
    $query = 'SELECT ... WHERE p.Status = ?';
    $params = ['Active'];
    if ($search) {
        $query .= ' AND (p.Title LIKE ? OR p.Description LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);   // Parameters bound server-side

  - All form inputs are escaped with htmlspecialchars() on output to
    prevent XSS attacks (output encoding, defense-in-depth).

  - Passwords are hashed with PHP's password_hash() using bcrypt algorithm
    (PASSWORD_DEFAULT). Never stored in plaintext.
    Verified with password_verify() on login.

  - Sessions are used for authentication state (no client-side tokens).
    Session ID is regenerated implicitly by PHP's session_start().

FILES: config/database.php, all .php files (every query uses prepare/execute)
VERIFICATION: grep -r "->query(" --include="*.php" returns only read-only
              queries on admin-seeded data with zero user input.

================================================================================
5.  SECURITY MEASURES (SUMMARY)
================================================================================

1. SQL Injection:      PDO prepared statements for ALL queries — zero exceptions.
2. Password Storage:   bcrypt via password_hash() — no plaintext ever stored.
3. XSS Prevention:     htmlspecialchars() on all user-origin output.
4. Access Control:     Role-based guards (requireLogin, requireTraveller,
                       requireAgency) on every protected page.
5. Ownership Checks:   Agencies can only edit/delete their own packages
                       (verified via CURATES table query before any mutation).
6. Input Validation:   Server-side validation on all forms (empty checks,
                       type checks, range checks). Client-side validation
                       is cosmetic only (HTML5 required/min attributes).
7. Session Security:   Server-side session storage, regenerated by PHP.
8. Error Handling:     PDO exceptions caught with custom die() messages
                       (no stack traces exposed to users in production).

================================================================================
6.  SEED DATA
================================================================================

The database is pre-populated with realistic sample data for demonstration:

  - 6 Destinations:  Cape Town, Paris, Tokyo, Bali, New York, Dubai
  - 6 Flights:       Emirates, Air France, Qatar Airways, Singapore Airlines,
                     British Airways, Emirates (JNB → each destination)
  - 8 Accommodations: Mix of 5-star hotels, resorts, hostels (R3500–R15000/night)
  - 6 Restaurants:   Fine dining & local cuisine (Rated 4.5–4.9)
  - 8 Attractions:   Table Mountain, Eiffel Tower, Senso-ji Temple, Uluwatu,
                     Statue of Liberty, Burj Khalifa, V&A Waterfront, Louvre
  - 7 Packages:      4 from Tripistry Official, 3 from Wanderlust Travel Co
                     Price range: R3,500 (budget) to R32,000 (luxury)
  - 2 Reviews:       From the demo traveller on packages 1 and 2
  - 3 Group Trips:   Bali Group Adventure, Dubai Luxury Group, Budget Cape Town Crew

  Demo logins (password for all: "password"):
    admin@tripistry.com     → Agency (Tripistry Official, Verified)
    traveller@test.com      → Traveller (John Doe)
    agency2@test.com        → Agency (Wanderlust Travel Co, Verified)

================================================================================
7.  HOW TO RUN
================================================================================

Prerequisites:
  - PHP 7.4+ with PDO MySQL extension
  - MySQL 8.0+ (or MariaDB 10.3+)
  - Web browser

Steps:
  1. Start MySQL/MariaDB service:
       sudo systemctl start mysql

  2. Import the database:
       mysql -u root -p < sql/setup.sql
     (The script creates the 'tripistry' database and all tables with seed data.)

  3. Configure credentials in config/database.php:
       $host = '127.0.0.1';    // Use TCP to avoid socket issues
       $username = 'root';
       $password = 'your_mysql_password';

  4. Start PHP development server from the tripistry/ directory:
       php -S localhost:8000 -t /path/to/tripistry

  5. Open in browser: http://localhost:8000

  6. Login with demo credentials (see Section 6 above).

================================================================================
8.  TECHNICAL NOTES
================================================================================

- Zero frameworks: Entire application uses vanilla PHP, HTML5, CSS3, and
  vanilla JavaScript (ECMAScript 5/6). No jQuery, Bootstrap, React, Laravel,
  or any other external library or framework.

- Front-end: Custom CSS with CSS custom properties (variables) for theming,
  Flexbox/Grid for layout, media queries for responsive design (mobile-first
  breakpoints at 768px). All interactive elements (tabs, star rating, live
  cost calculation) work without any JS framework.

- Back-end: Functional (not OOP) PHP with shared includes for auth, header,
  and footer. Database access via PDO singleton in config/database.php.
  Prepared statements used universally.

- Session Management: PHP native sessions (session_start()) with server-side
  storage. User type and ID stored in $_SESSION after successful login.

- No file uploads are required for this submission; the uploads/ directory is
  reserved for future image upload functionality. Current ImageURL fields
  reference Unsplash CDN URLs.

================================================================================
9.  GIT COMMIT HISTORY
================================================================================

d14bd4f  Fix AUTO_INCREMENT column order in REVIEW/BOOKS tables
         and remove duplicate seed data
8086caf  Initial commit: Tripistry travel booking platform MVP
         - MySQL database schema with 17 relations from EER mapping
         - User auth with Traveller/Agency roles
         - All CRUD operations for packages and group trips
         - Search, sort, filter, compare, book, review workflows
         - Prepared statements throughout for SQL injection prevention

================================================================================
END OF README
================================================================================
