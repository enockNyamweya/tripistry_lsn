TRIPISTRY — Travel Package Management Web Application
COS 221 Practical Assignment 5 (Task 5: Web-Based Application & REST API)

Authors:   Angela Ramaboea, Patrick Simuyemba, Nicole Bare, Enock Nyamweya, Angelo Anthony
Module:    COS 221

1.  PROJECT OVERVIEW

Tripistry is a full-stack web application built from scratch using vanilla
PHP, HTML5, CSS3, and JavaScript — no frameworks or external libraries.
It manages a travel booking platform where Travellers can browse, compare,
book, and review travel packages, while Travel Agencies can create, edit, and
delete packages and manage associated entities (destinations, flights,
accommodation, restaurants, and attractions).

The database contains 22 normalized tables, including Group Trip enrolments.
A headless JSON REST API layer has also been implemented.

2.  ARCHITECTURE & FILE STRUCTURE

tripistry_lsn/
├── config/
│   ├── env.php                # Environment variables parser
│   └── database.php           # PDO connection (singleton) & dynamic BASE_URL
├── includes/
│   ├── auth.php               # Authentication functions and role guards
│   ├── header.php             # Shared HTML head, navbar with role-based links
│   └── footer.php             # Shared footer + closing tags
├── sql/
│   └── setup.sql              # Full database schema (22 tables) + seed data
├── assets/
│   ├── css/style.css          # Unified stylesheet
│   └── js/main.js             # Client-side interactivity (tabs, alerts)
├── api/
│   ├── index.php              # Central REST API router with try-catch safety
│   └── routes/                # Endpoint handlers (packages, accommodations, etc)
├── index.php                  # Landing page
├── login.php                  # Login form (bcrypt password hashing)
├── register.php               # Joint registration form for Travellers/Agencies
├── dashboard.php              # Role-based routing
├── traveller/                 # TRAVELLER SECTION
│   ├── browse.php             # Tabbed view: Destinations, Flights, etc.
│   ├── packages.php           # Package listing with search/sort/filter
│   ├── package_detail.php     # Detail view + booking + review forms
│   └── bookings.php           # Booking history
└── agency/                    # AGENCY SECTION
    ├── dashboard.php          # Stats dashboard + recent bookings
    ├── packages.php           # CRUD list with edit/delete actions
    ├── create_package.php     # Multi-field form for package creation
    ├── edit_package.php       # Edit form with association management
    ├── manage_items.php       # Add/remove associated entities
    ├── group_trips.php        # Group trip status management
    └── bookings.php           # List of bookings made by travellers

3.  DATABASE DESIGN & SCHEMA (22 TABLES)

The database (`tripistry_lsn`) strictly maintains referential integrity using
foreign keys with ON DELETE CASCADE rules.

 1. USER                  — Superclass: UserID, Email, Password, UserType, DateCreated, Address, Phone
 2. TRAVELLER             — Subclass (UserID FK): UserID, FirstName, LastName, PreferenceID
 3. TRAVEL_AGENCY         — Subclass (UserID FK): UserID, AgencyName, VerificationStatus, CommissionRate
 4. TRAVELLER_PREFERENCE  — PreferenceID, BudgetRange, TravelPace
 5. TRAVELLER_PREFERENCE_TAGS — PreferenceID FK, PreferenceTag
 6. TRAVEL_PACKAGE        — PackageID, Title, Description, Price, DurationDays, IsGroupTrip, Status, ImageURL, AgencyID FK
 7. GROUP_TRIP            — GroupTripID, TripName, MaxCapacity, AgencyID FK, PackageID FK
 8. BOOKING               — BookingID, BookingDate, TotalCost, Status, UserID FK, PackageID FK
 9. PAYMENT               — BookingID FK, PaymentSeq, Amount, PaymentDate, Status
10. REVIEW                — UserID FK, ReviewID, PackageID FK, Comment, RatingScore, DatePosted, BookingID FK
11. DESTINATION           — DestinationID, City, Country, Latitude, Longitude, Description, ImageURL
12. FLIGHT                — FlightID, Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price
13. ACCOMMODATION         — AccommodationID, Name, Type, StarRating, PricePerNight, Address, DestinationID FK
14. ACCOMMODATION_AMENITIES — AccommodationID FK, Amenity
15. RESTAURANT            — RestaurantID, Name, CuisineType, PriceRange, Address, Rating, DestinationID FK
16. ATTRACTION            — AttractionID, Name, Type, EntryFee, Description, OpeningHours, DestinationID FK
17. INCLUDES_FLIGHT       — PackageID FK, FlightID FK
18. INCLUDES_ACCOM        — PackageID FK, AccommodationID FK
19. INCLUDES_RESTAURANT   — PackageID FK, RestaurantID FK
20. INCLUDES_ATTRACTION   — PackageID FK, AttractionID FK
21. HAS_DESTINATION       — PackageID FK, DestinationID FK
22. ENROLS                — UserID FK, GroupTripID FK

*Note on Seed Data:
To support clean runtime operations, certain relational and transactional tables (specifically GROUP_TRIP, ENROLS, PAYMENT, TRAVELLER_PREFERENCE, TRAVELLER_PREFERENCE_TAGS, and ACCOMMODATION_AMENITIES) do not receive pre-populated seed data during database setup. These tables are intentionally left with 0 entries initially and will be populated dynamically through user actions as the system is utilized (e.g., when travellers specify preferences, when agencies create group trips and travellers enrol in them, or when payments are made).

4.  REST API LAYOUT (TASK B)

A robust REST API layer resides inside /api/index.php. It responds exclusively 
in JSON format and provides headless database query access. It features a global
try/catch block to gracefully handle 500 Server Errors without breaking the frontend.

* GET /api/index.php/destinations - Retrieve all geographic destinations.
* GET /api/index.php/packages - Retrieve active travel packages. Supports comparison parameters (e.g., ?compare=1,2,3).
* GET /api/index.php/flights - Retrieve flight listings.
* GET /api/index.php/accommodations - Retrieve stays. Supports filtering by minimum star rating ?min_stars=4 and sorting by price ?sort=price_desc.
* GET /api/index.php/restaurants - Retrieve dining venues.
* GET /api/index.php/attractions - Retrieve local attractions.

5.  SECURITY IMPLEMENTATION

1. SQL Injection:      PDO prepared statements for ALL queries — zero exceptions.
2. Password Storage:   bcrypt via password_hash() — no plaintext ever stored.
3. XSS Prevention:     htmlspecialchars() on all user-origin output.
4. Access Control:     Role-based guards on every protected page.
5. Error Handling:     Global try-catch on the API router prevents stack traces.

6.  SEED DATA (DEMO CREDENTIALS)

The database is pre-populated with realistic sample data for demonstration.
Password for all demo accounts: "password"

  - admin@tripistry.com     → Agency (Tripistry Official, Verified)
  - agency2@test.com        → Agency (Wanderlust Travel Co, Verified)
  - traveller@test.com      → Traveller (John Doe)

7.  HOW TO SETUP AND RUN

Prerequisites:
  - PHP 7.4+ with PDO MySQL extension
  - MySQL 8.0+ (or MariaDB 10.3+)
  - XAMPP / LAMP / MAMP or built-in PHP server

Quick Database Installation & Seeding (Recommended):
  Run the unified setup script from the project root:
       php setup_db.php
  This will automatically:
    1. Recreate the schema by running the SQL scripts.
    2. Seed 10,000 packages, 40,000 bookings, and 30,000+ reviews from the dataset.

Steps for XAMPP (Windows):
  1. Clone or extract the project into C:\xampp\htdocs\tripistry_lsn
  2. Start Apache and MySQL from the XAMPP Control Panel.
  3. Run the setup script in PowerShell or Command Prompt:
       php setup_db.php
  4. Open in browser: http://localhost/tripistry_lsn

Steps for Built-in PHP Server (Linux/macOS):
  1. Run the setup script from the project root:
       php setup_db.php
  2. Start the server:
       php -S localhost:8000
  3. Open in browser: http://localhost:8000

Note: The application automatically detects your environment (BASE_URL) so paths 
will dynamically adjust whether you are using an XAMPP sub-directory or the root server.

8.  RECENT UPDATES & BUG FIXES (MERGE CONFLICT RESOLUTIONS)

To ensure the new features from the 'main' branch work seamlessly in all local environments (especially XAMPP), the following critical fixes were applied:

- Database Query Alignment with the 22-Table Schema: 
  Migrated every query and data interaction surface in the codebase away from legacy entities (such as PACKAGE, BOOKS, VISITS, CURATES, INCLUDES_STAY) to the official schema tables (TRAVEL_PACKAGE, BOOKING, HAS_DESTINATION, INCLUDES_ACCOM). Adjusted JOIN criteria to remove the CURATES table completely, joining directly on the AgencyID.
- Spelling & Column Compliance:
  Corrected all references of ACCOMODATION to ACCOMMODATION (double 'm') in database queries. Since the BOOKING table does not possess a NumTravellers column, queries select this dynamically as ROUND(TotalCost / Price) to support the UI without database errors.
- Seeder Performance Cascade Optimization:
  Optimized the database installer and seeder script (fetch_interactions.php) to utilize runtime caching for password hashing, and kept prepared statement generation outside loop structures. This resolved a major CPU bottleneck, cascading to accelerate the database population of 100,000+ items (10k packages, 40k bookings, and 30k+ reviews) from approximately 15 minutes to under 15 seconds.
- Dynamic Pathing Implementation: 
  Hardcoded absolute links (e.g. href="/login.php") across all frontend files (including bookings pages) were refactored to use a dynamic <?php echo BASE_URL; ?> variable. This prevents broken links when hosting the project in a subdirectory.
- README Cleanup: Removed duplicate and conflicting legacy schema definitions to accurately reflect the unified 22-table structure.
- Chat/Messaging Removal: Completely removed all chat, inbox, and messaging interfaces (including traveller/chat.php, traveller/messages.php, agency/chat.php, agency/messages.php, and includes/chat_functions.php) since the MESSAGE table is not part of the official 22-table schema. All corresponding nav links, message buttons, and dashboard notifications have been disabled.
- Seed Data Scope & Empty Tables Note:
  Certain relational tables (like GROUP_TRIP, ENROLS, PAYMENT, TRAVELLER_PREFERENCE, TRAVELLER_PREFERENCE_TAGS, and ACCOMMODATION_AMENITIES) are designed to hold transactional or user-configured data. These do not receive pre-populated seed data from the CSV and are intentionally left empty initially. They are populated dynamically by the application during runtime as agencies create group trips, travellers register their preferences, make bookings, and complete payments.

END OF README
