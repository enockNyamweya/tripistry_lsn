TRIPISTRY — Travel Package Management Web Application
COS 221 Practical Assignment 5

Authors: Angela Ramaboea, Patrick Simuyemba, Nicole Bare, Enock Nyamweya, Angelo Anthony
Module: COS 221
Date: May 2026

1. PROJECT OVERVIEW

Tripistry is a full-stack web application built from scratch using vanilla
PHP, HTML5, CSS3, and JavaScript — no frameworks or external libraries.
It manages a travel booking platform where Travellers can browse, compare,
book, and review travel packages, while Travel Agencies can create, edit, and
delete packages and manage associated entities.

The database contains 22 normalized tables including Group Trip enrolments.
A headless JSON REST API layer serves all data with pagination.
A private 1-to-1 chat system connects travellers with agencies in real time.

2. ARCHITECTURE AND FILE STRUCTURE

tripistry_lsn/
├── config/
│   ├── env.php                — Environment variables parser
│   └── database.php           — PDO connection and dynamic BASE_URL
├── includes/
│   ├── auth.php               — Authentication functions and role guards
│   ├── header.php             — Shared HTML head, role-based navbar
│   ├── footer.php             — Shared footer and core JS includes
│   ├── security.php           — CSRF protection, rate limiting, session hardening
│   ├── sentiment.php          — Lexicon-based sentiment analysis for reviews
│   └── group_matcher.php      — Group matching algorithm for solo travellers
├── database/
│   ├── schema.sql             — Full 22-table schema (DDL)
│   ├── import_schema.php      — Schema import script
│   ├── chat_migration.sql     — Chat messaging table (auxiliary)
│   └── fetch-interactions/    — Seed data scripts and CSV
├── assets/
│   ├── css/                   — reset, variables, layout, components, pages
│   └── js/                    — main.js, ui.js, package.js, group_trips.js
├── api/
│   ├── index.php              — Central REST API router
│   └── routes/                — Endpoint handlers for all resources
├── traveller/
│   ├── dashboard.php          — Traveller dashboard with recommendations
│   ├── browse.php             — Tabbed browsing (5 tabs)
│   ├── packages.php           — Package search, filter, sort, compare
│   ├── package_detail.php     — Package detail, booking, reviews with sentiment
│   ├── bookings.php           — Booking history with Google Calendar export
│   ├── chat.php               — Private chat with agencies
│   ├── destination.php        — Destination detail page
│   ├── accommodation_detail.php — Accommodation detail with amenities
│   ├── restaurant_detail.php  — Restaurant detail with nearby places
│   ├── attraction_detail.php  — Attraction detail with packages
│   └── group_matches.php     — Algorithmic group trip recommendations
├── agency/
│   ├── dashboard.php          — Stats dashboard and recent bookings
│   ├── packages.php           — CRUD list with edit/delete
│   ├── create_package.php     — Package creation with auto date calculation
│   ├── edit_package.php       — Edit form with association management
│   ├── manage_items.php       — Add/remove associated entities
│   ├── group_trips.php        — Group trip status management
│   ├── bookings.php           — Booking confirmation/cancellation
│   ├── chat.php               — Private chat with travellers
│   ├── cancel_group_trip.php  — AJAX group trip cancel
│   └── undo_group_trip.php    — AJAX group trip restore
├── presentation/
│   └── index.html             — 17-slide demonstration deck
├── .github/workflows/
│   └── ci.yml                 — PHP lint and validation CI pipeline
├── setup_db.php               — One-click DB setup and seeder
├── index.php                  — Landing page
├── login.php                  — Login with CSRF and rate limiting
├── register.php               — Registration (Traveller/Agency)
└── dashboard.php              — Role-based router

3. DATABASE DESIGN (22 TABLES)

The database maintains referential integrity using foreign keys with ON DELETE CASCADE.

 1. USER                  — Superclass: UserID, Email, Password, UserType, DateCreated, Address, Phone
 2. TRAVELLER             — Subclass: FirstName, LastName, PreferenceID
 3. TRAVEL_AGENCY         — Subclass: AgencyName, VerificationStatus, CommissionRate
 4. TRAVELLER_PREFERENCE  — PreferenceID, BudgetRange, TravelPace
 5. TRAVELLER_PREFERENCE_TAGS — PreferenceID FK, PreferenceTag
 6. TRAVEL_PACKAGE        — PackageID, Title, Description, Price, DurationDays, IsGroupTrip, Status, ImageURL, AgencyID FK
 7. GROUP_TRIP            — GroupTripID, TripName, MaxCapacity, Status, AgencyID FK, PackageID FK
 8. BOOKING               — BookingID, BookingDate, TotalCost, Status, UserID FK, PackageID FK
 9. PAYMENT               — BookingID FK, PaymentSeq, Amount, PaymentDate, Status
10. REVIEW                — UserID FK, ReviewID, PackageID FK, Comment, RatingScore, DatePosted
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

Note: The auxiliary MESSAGE table used by the chat feature is created via a separate
migration and is intentionally excluded from the core 22-table EER diagram.

4. CHAT MESSAGING FEATURE

Private 1-to-1 chat between travellers and agencies. Each conversation is scoped
to a single traveller-agency pair — no other users can access messages.

API Endpoints (session-authenticated):
  GET  /api/chat/conversations      — List users with active conversations
  GET  /api/chat/messages/{user_id} — Get message history with a specific user
  POST /api/chat/send               — Send a message
  GET  /api/chat/contacts           — Search potential contacts

The MESSAGE table auto-creates on first use (CREATE TABLE IF NOT EXISTS).

Image Sources:
  All card images for accommodations, restaurants, attractions, and destinations
  are sourced from LoremFlickr (loremflickr.com), a free Creative Commons image
  service. Category-appropriate queries are used:
    - Accommodations: hotel,resort,room
    - Restaurants: food,restaurant,dining
    - Attractions: landmark,tourism,travel
    - Destinations: city,skyline
  Images are locked by item ID for consistent display.

5. PACKAGE AUTO DATE CALCULATOR

When creating or editing a package, entering a start date and duration automatically
calculates the end date. Two-way binding: changing the end date recalculates duration.

6. REST API

All endpoints serve JSON with standardized pagination envelopes.

Endpoints:
  GET /api/destinations             — Geographic destinations
  GET /api/packages                 — Travel packages (search, filter, sort, compare)
  GET /api/flights                  — Flight listings
  GET /api/accommodations           — Stays (filter by stars, destination)
  GET /api/restaurants              — Dining (filter by cuisine, destination)
  GET /api/attractions              — Local attractions (filter by type, destination)
  GET /api/agency/packages          — Agency-scoped packages
  GET /api/agency/bookings          — Agency-scoped bookings
  GET /api/agency/group-trips       — Agency-scoped group trips
  GET /api/chat/conversations       — Chat conversation list
  GET /api/chat/messages/{id}       — Chat message history
  POST /api/chat/send               — Send chat message

7. SECURITY

- SQL Injection:      PDO prepared statements for all queries
- Password Storage:   bcrypt via password_hash()
- XSS Prevention:     htmlspecialchars() on all dynamic output
- CSRF Protection:    Token-based CSRF validation on forms
- Rate Limiting:      IP-based login attempt throttling (5 attempts per 5 minutes)
- Session Security:   Session ID regeneration on login
- Access Control:     Role guards (requireTraveller, requireAgency) on protected pages

8. BONUS FEATURES (Task 11)

1. Enhanced UI/UX — Top-rated packages, recommendation engine scoring destinations by
   attractions/accommodations/restaurants, skeleton loading, hover animations.
2. Security Features — CSRF tokens, login rate limiting, session hardening, XSS wrapper.
3. Group Matching Algorithm — Matches solo travellers to group trips based on destination
   preferences, budget, duration, and booking history (includes/group_matcher.php).
4. AI Integration — Lexicon-based sentiment analysis on reviews, classifying comments as
   Positive/Neutral/Negative with confidence scores (includes/sentiment.php).
5. Advanced Feature — Google Calendar export for bookings and destination recommendation engine.
6. CI/CD Pipeline — GitHub Actions workflow validates PHP syntax, SQL schema, and file structure.

9. DEMO CREDENTIALS

All demo accounts use password: "password"

  admin@tripistry.com      — Agency (Tripistry Official)
  agency2@test.com         — Agency (Wanderlust Travel Co)
  traveller@test.com       — Traveller (John Doe)

Additional 100+ seeded agencies follow the pattern agency{N}@example.com

10. HOW TO SETUP AND RUN

Prerequisites:
  - PHP 7.4+ with PDO MySQL extension
  - MySQL 8.0+ (or MariaDB 10.3+)

Quick Setup:
  1. Configure .env with your database credentials
  2. Run: php setup_db.php
  3. Start server: php -S localhost:8080
  4. Open: http://localhost:8080

The setup script automatically creates all 22 tables, seeds 10,000+ packages,
40,000+ bookings, 30,000+ reviews, and sets up the chat messaging table.

END OF README
