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

The database contains 22 normalized tables, including new features for 
Private Chat Messaging between Travellers and Agencies, and Group Trip enrolments.
A headless JSON REST API layer has also been implemented.

2.  ARCHITECTURE & FILE STRUCTURE

tripistry_lsn/
├── config/
│   ├── env.php                # Environment variables parser
│   └── database.php           # PDO connection (singleton) & dynamic BASE_URL
├── includes/
│   ├── auth.php               # Authentication functions and role guards
│   ├── header.php             # Shared HTML head, navbar with role-based links
│   ├── footer.php             # Shared footer + closing tags
│   └── chat_functions.php     # Helper functions for the messaging system
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
│   ├── bookings.php           # Booking history
│   ├── messages.php           # Inbox for traveller
│   └── chat.php               # 1-on-1 private chat with agencies
└── agency/                    # AGENCY SECTION
    ├── dashboard.php          # Stats dashboard + recent bookings
    ├── packages.php           # CRUD list with edit/delete actions
    ├── create_package.php     # Multi-field form for package creation
    ├── edit_package.php       # Edit form with association management
    ├── manage_items.php       # Add/remove associated entities
    ├── group_trips.php        # Group trip status management
    ├── bookings.php           # List of bookings made by travellers
    ├── messages.php           # Inbox for agency
    └── chat.php               # 1-on-1 private chat with travellers

3.  DATABASE DESIGN & SCHEMA (22 TABLES)

The database (`tripistry_lsn`) strictly maintains referential integrity using
foreign keys with ON DELETE CASCADE rules.

 1. USER                  — Superclass: UserID, Email, Password, UserType
 2. TRAVELLER             — Subclass (UserID FK): FirstName, LastName, PassportNum
 3. TRAVEL_AGENCY         — Subclass (UserID FK): AgencyName, VerificationStatus, CommissionRate
 4. DESTINATION           — City, Country, Latitude, Longitude, Description, ImageURL
 5. FLIGHT                — Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price
 6. ACCOMODATION          — Name, Type, StarRating, PricePerNight, Address, DestinationID FK
 7. RESTAURANT            — Name, CuisineType, PriceRange, Address, Rating, DestinationID FK
 8. ATTRACTION            — Name, Type, EntryFee, Description, OpeningHours, DestinationID FK
 9. PACKAGE               — Title, Description, Price, DurationDays, StartDate, EndDate, MaxTravellers, IsGroupTrip, ImageURL, Status
10. CURATES               — M:N Agency–Package (UserID, PackageID) [PK on PackageID]
11. VISITS                — M:N Package–Destination (PackageID, DestinationID)
12. INCLUDES_FLIGHT       — 1:N Package–Flight (PackageID, FlightID)
13. INCLUDES_STAY         — 1:N Package–Accommodation (PackageID, AccomodationID)
14. PACKAGE_RESTAURANT    — M:N Package–Restaurant (PackageID, RestaurantID)
15. PACKAGE_ATTRACTION    — M:N Package–Attraction (PackageID, AttractionID)
16. REVIEW                — Weak entity: ReviewID, UserID, PackageID, Comment, RatingScore
17. BOOKS                 — Booking relation: BookingID, UserID, PackageID, BookingDate, TotalCost, NumTravellers, Status
18. TRAVELLER_PHONE       — Multivalued attribute: (UserID, PhoneNumber)
19. ACCOMODATION_AMENITY  — Multivalued attribute: (AccomodationID, Amenity)
20. GROUP_TRIP            — GroupTripID, PackageID FK, GroupName, MinParticipants, MaxParticipants, Status
21. GROUP_TRIP_ENROLMENT  — EnrolmentID, GroupTripID FK, UserID FK, Status
22. MESSAGE               — Chat relation: MessageID, SenderID, ReceiverID, PackageID, Message, IsRead, SentDate

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

Steps for XAMPP (Windows):
  1. Clone or extract the project into C:\xampp\htdocs\tripistry_lsn
  2. Start Apache and MySQL from the XAMPP Control Panel.
  3. Open Command Prompt or PowerShell and import the database:
       mysql -u root -p -e "source sql/setup.sql"
     (The script handles dropping and recreating the 'tripistry_lsn' database).
  4. Open in browser: http://localhost/tripistry_lsn

Steps for Built-in PHP Server (Linux/macOS):
  1. Import the database:
       mysql -u root -p < sql/setup.sql
  2. Start the server from the project root:
       php -S localhost:8000
  3. Open in browser: http://localhost:8000

Note: The application automatically detects your environment (BASE_URL) so paths 
will dynamically adjust whether you are using an XAMPP sub-directory or the root server.

8.  RECENT UPDATES & BUG FIXES (MERGE CONFLICT RESOLUTIONS)

To ensure the new features from the 'main' branch work seamlessly in all local environments (especially XAMPP), the following critical fixes were applied:

- Dynamic Pathing Implementation: Hardcoded absolute links (e.g. href="/login.php") across all frontend files (including the new chat and bookings pages) were refactored to use a dynamic <?php echo BASE_URL; ?> variable. This prevents broken links when hosting the project in a subdirectory.
- README Cleanup: Removed duplicate and conflicting legacy schema definitions to accurately reflect the unified 22-table structure.

END OF README
