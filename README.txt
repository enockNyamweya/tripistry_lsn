TRIPISTRY - Travel Package Management Web Application
COS 221 Practical Assignment 5 (Task 5 & REST API Backend Implementation)

Authors:   Angela Ramaboea, Patrick Simuyemba, Nicole Bare, Enock Nyamweya, Angelo Anthony
Module:    COS 221
Tutor:     Johan Nel (Johan.nel@tuks.co.za)


1. PROJECT OVERVIEW

Tripistry is a complete full-stack web application built using vanilla PHP, HTML5, CSS3, and JavaScript (with no external libraries or frameworks). It facilitates a travel booking system where Travellers can search, compare, book, and review packages. Travel Agencies can create and manage travel packages and associate them with various flights, accommodations, attractions, and restaurants.

The database is built on a highly normalized 22-table MariaDB schema derived directly from the relational mapping of the project's Enhanced Entity-Relationship (EER) model. Additionally, a robust headless JSON REST API layer has been built to expose all normalized entities.


2. ARCHITECTURE & FILE STRUCTURE

The directory layout enforces a strict Separation of Concerns (SoC) structure:

tripistry/
├── config/
│   └── database.php           # PDO database credentials & connection (Singleton)
├── includes/
│   ├── auth.php               # User registration, bcrypt authentication, & session guards
│   ├── Database.php           # Internal singleton database class for REST layer
│   ├── header.php             # Unified HTML header & role-based navbar
│   ├── footer.php             # Unified HTML footer + dynamic alerts handler
│   └── PackageService.php     # Reusable business logic for travel packages
├── assets/
│   ├── css/
│   │   ├── reset.css          # Baseline CSS resets
│   │   ├── variables.css      # Design tokens (Aesthetics: Restored MVP Light Theme colors)
│   │   ├── layout.css         # Page shell structure, header/footer spacing
│   │   ├── components.css     # Buttons, inputs, tables, and badge layouts
│   │   └── pages.css          # Views for browse, bookings, reviews, and comparison
│   └── js/
│       ├── ui.js              # Client-side tab toggling, live calculations, & modals
│       └── alerts.js          # Graceful UI notifications auto-dimmer
├── database/
│   ├── schema.sql             # Unified 22-Table schema DDL
│   ├── import_schema.php      # Developer CLI tool to recreate database & build schema
│   ├── seed_db.php            # Developer CLI tool to truncate & seed all 22 tables
│   ├── test_db.php            # Database health verification script
├── api/
│   ├── index.php              # Central API router (Path-Info routing fallback enabled)
│   └── routes/
│       ├── destinations.php   # REST endpoints for Destination resource
│       ├── packages.php       # REST endpoints for Travel Packages + Compare query filter
│       ├── flights.php        # REST endpoints for Flight resources + Departure/Arrival filters
│       ├── accommodations.php # REST endpoints for Accommodation + StarRating filters
│       ├── restaurants.php    # REST endpoints for Restaurant resources + CuisineType filter
│       └── attractions.php    # REST endpoints for Attraction resources
├── index.php                  # Public landing / home page
├── login.php                  # Bcrypt login form with demo logins
├── register.php               # Joint registration form with dynamic subclasses toggle
├── logout.php                 # Safe session destruction
├── dashboard.php              # Unified landing gate after registration or login
├── traveller/                 # TRAVELLER INTERFACES
│   ├── browse.php             # Directory of destinations, flights, stays, food, attractions
│   ├── packages.php           # Packages search & comparison page
│   ├── package_detail.php     # Package details page, Booking Form, Reviews Form & Feed
│   └── bookings.php           # Traveller booking history
├── agency/                    # AGENCY INTERFACES
│   ├── dashboard.php          # Visual statistics and metrics dashboard
│   ├── packages.php           # Agency packages inventory lists & deletion handlers
│   ├── create_package.php     # Dynamic multi-select form to curate new packages
│   ├── edit_package.php       # Pre-filled package details & associations update form
│   ├── manage_items.php       # Granular package item attachment/detachment panel
│   └── group_trips.php        # Group trip bookings tracker and manager
├── uploads/                   # Destination & package image upload storage


3. DATABASE DESIGN & SCHEMA (22 TABLES)

The system operates on exactly 22 tables. Names are kept case-insensitive for compatibility. Referential integrity is strictly maintained via foreign keys.

3.1 Relational Database Schema Table Index
1.  USER                       - Superclass: Credentials and registration base details.
2.  TRAVELLER                  - Subclass: Details linked to Traveller user type.
3.  TRAVEL_AGENCY              - Subclass: Agency name, verification status, and commission rates.
4.  TRAVELLER_PREFERENCE       - Travel preference parameters (budget, pace).
5.  TRAVELLER_PREFERENCE_TAGS  - Multivalued attributes: preference tags.
6.  TRAVEL_PACKAGE             - Detailed travel package record.
7.  GROUP_TRIP                 - Group trip records linking to curated packages.
8.  BOOKING                    - Bookings placed by Travellers.
9.  PAYMENT                    - Record of payment schedules/sequences per booking.
10. REVIEW                     - Review entries containing comments and rating scores.
11. DESTINATION                - Geographic destinations.
12. FLIGHT                     - Detailed airline transport details.
13. ACCOMMODATION              - Hospitality stays (hotels, hostels, resorts).
14. ACCOMMODATION_AMENITIES    - Multivalued attributes: accommodation amenities list.
15. RESTAURANT                 - Dining venues linked to destinations.
16. ATTRACTION                 - Landmarks and excursions.
17. INCLUDES_FLIGHT            - M:N relation: packages mapping to flights.
18. INCLUDES_ACCOM             - M:N relation: packages mapping to accommodations.
19. INCLUDES_RESTAURANT        - M:N relation: packages mapping to restaurants.
20. INCLUDES_ATTRACTION        - M:N relation: packages mapping to attractions.
21. HAS_DESTINATION            - M:N relation: packages mapping to destinations.
22. ENROLS                     - M:N relation: Travellers enrolled into group trips.

3.2 Schema Enhancements & DB Resolutions
* Column Syncing: Ensured columns used in the PHP queries (such as StarRating, PricePerNight, CuisineType, PriceRange, Rating, EntryFee, Description, ImageURL) exist directly on their respective tables.
* Junction Table naming: Unified database tables to map directly with SQL requests (e.g., using INCLUDES_ACCOM and ENROLS consistently).
* MariaDB/MySQL compatibility: Modified transactional scripts and seeders to remove PostgreSQL syntax (e.g. CASCADE inside TRUNCATE TABLE queries). Moved the transaction boundary after the truncation sequence to prevent implicit DDL commits.


4. REST API LAYOUT (TASK B)

A robust REST API layer resides inside /api/index.php. It responds exclusively in JSON format and provides headless database query access:

* GET /api/destinations - Retrieve all geographic destinations.
* GET /api/packages - Retrieve active travel packages. Supports comparison parameters (e.g., ?compare=1,2,3) for side-by-side structure reviews.
* GET /api/flights - Retrieve flight listings. Supports query filters ?departure=JNB and ?arrival=CPT.
* GET /api/accommodations - Retrieve stays. Supports filtering by minimum star rating ?min_stars=4 and sorting by price ?sort=price_desc.
* GET /api/restaurants - Retrieve dining venues. Supports filtering by cuisine ?cuisine=French.
* GET /api/attractions - Retrieve local attractions.

Note: Since standard Apache installations do not have URL rewrite modules enabled by default, the router handles Path-Info queries. You can query endpoints by appending them directly after the file name, for example: /api/index.php/destinations.


5. SECURITY IMPLEMENTATION

1. SQL Injection Immunity: 100% of queries across both the frontend web app and the REST API endpoints are parameterized using PDO prepared statements (execute()).
2. Cryptographic Authentication: Password inputs are encrypted using bcrypt hashing (password_hash with PASSWORD_DEFAULT) and verified with password_verify().
3. Role Guards: Session access constraints enforce strict user roles. Non-authorized attempts redirect users back to their respective landing dashboards.
4. XSS Defenses: All browser outputs are encoded with htmlspecialchars().


6. HOW TO SETUP AND RUN

Prerequisites:
* XAMPP (with Apache and MariaDB/MySQL running)
* PHP 7.4+

Configuration Steps:

1. Database Configuration:
   * WARNING: Do NOT place your database credentials in the `.env` file in the project root! Doing so will leak your private passwords to GitHub.
   * Instead, you should create a file named `.tripistry_lsn.env` in your user home directory (e.g., `C:\Users\<Username>\.tripistry_lsn.env`).
   * Add the following configuration to your local `.tripistry_lsn.env` file:
     DB_HOST=localhost
     DB_NAME=tripistry_lsn
     DB_USER=root
     DB_PASS=local_db_password

2. Import Database Schema & Seed Data:
   Run the following commands using the XAMPP PHP CLI from your project root directory:

   - Recreate the database structure (22 tables):
     C:\xampp\php\php.exe database/import_schema.php

   - Seed all 22 entities (Flights, Packages, Agencies, Bookings, etc.):
     C:\xampp\php\php.exe database/seed_db.php

   - Verify database health and connectivity:
     C:\xampp\php\php.exe database/test_db.php

4. Launch & Verify:
   * Open your browser to: http://localhost/tripistry_lsn/
   * Log in using the following demo credentials:
     * Traveller Account: traveller@test.com (password: password)
     * Agency Account: admin@tripistry_lsn.com (password: password)
     * Agency Account 2: agency2@test.com (password: password)
   * Access the phpMyAdmin console at: http://localhost/phpmyadmin/


7. TROUBLESHOOTING NOTES

* phpMyAdmin Connection Error: If phpMyAdmin displays Access denied for user 'pma'@'localhost' or auth_gssapi_client errors, check that controluser attributes are commented out inside C:\xampp\phpMyAdmin\config.inc.php and set $cfg['Servers'][$i]['password'] to your actual MariaDB password.
* Pathing issues in API: If endpoints throw file include warnings, check that requires inside api/index.php are prefixed with __DIR__ to enforce absolute routing pathing.


8. GIT BRANCHING WORKFLOW, DIVISION & SETUP

The project follows a Feature-Branch Workflow model to organize cooperation among the 5 team members. To prevent path conflicts, each developer has a dedicated "Home Base" directory they are in charge of.

Git Branch Tree Structure:
main (Production release branch)
└── develop (Integration branch)
    ├── feature/ui-design            (Developer 1: CSS design system, responsive UI, templates)
    ├── feature/api-routes           (Developer 2: REST API endpoints and routing)
    ├── feature/traveller-portal     (Developer 3: Traveller client pages & search matrices)
    ├── feature/agency-portal        (Developer 4: Agency panels, forms, & uploads)
    └── feature/db-security-backend  (Developer 5: Normalized DB schema, seeds, & auth security)


Directory Ownership & Role Allocations:

* feature/ui-design - (Developer 1)
  - Home Base Directory: `assets/`
  - Responsibilities: Design system stylesheets, CSS variables, responsive layout aesthetics, client-side JS interactions, and global template shells.
  - Directories:
    * `assets/css/` (reset.css, variables.css, layout.css, components.css, pages.css)
    * `assets/js/` (ui.js, alerts.js)
  - Shared Files:
    * `includes/header.php` & `includes/footer.php` (global shell layouts)
    * `index.php` (landing design layout styling)

* feature/api-routes - (Developer 2)
  - Home Base Directory: `api/`
  - Responsibilities: Building out the headless REST API endpoints and data routing.
  - Directories:
    * `api/routes/` (all REST endpoint files: destinations, flights, accommodations, etc.)
  - Shared Files:
    * `api/index.php` (central REST API router)
    * `includes/Database.php` (API Database Singleton)

* feature/traveller-portal - (Developer 3)
  - Home Base Directory: `traveller/`
  - Responsibilities: Traveller search matrices, side-by-side package comparisons, reviews submission feeds, and booking history.
  - Directories:
    * `traveller/` (browse.php, packages.php, package_detail.php, bookings.php)

* feature/agency-portal - (Developer 4)
  - Home Base Directory: `agency/` & `uploads/`
  - Responsibilities: Agency dashboard statistics, package creation/edit forms, package-item association controllers, and media uploads.
  - Directories:
    * `agency/` (dashboard.php, packages.php, create_package.php, edit_package.php, manage_items.php, group_trips.php)
    * `uploads/` (destination & package image storage)

* feature/db-security-backend - (Developer 5)
  - Home Base Directory: `database/` & `config/`
  - Responsibilities: Database schema definitions, large-scale mock seeder scripts (including handling asset mapping), DB health checks, PDO configuration, business services, and backend login security.
  - Directories:
    * `database/` (schema.sql, import_schema.php, seed_db.php, test_db.php)
    * `config/` (database.php connection)
  - Shared Files:
    * `includes/auth.php` (bcrypt password verification & session guards)
    * `includes/PackageService.php` (business logic query helper)
    * `login.php`, `register.php`, `logout.php`, `dashboard.php` (PHP backend logic/handlers)

---

HOW TO SETUP THE GIT REPOSITORY

1. Repository Initialization (Completed by One Team Member)
   Open your terminal in the project root directory and run:
   
   a. Initialize Git locally:
      git init
      
   b. Confirm your .gitignore exists in the root folder with the following contents:
      .env
      .env.local
      .tripistry_lsn.env
      *.log
      
   c. Add and commit the baseline codebase files:
      git add .
      git commit -m "chore: initialize project baseline skeleton"
      
   d. Rename the default branch to main:
      git branch -M main
      
   e. Create a repository on GitHub and link your local project to it:
      git remote add origin https://github.com/enockNyamweya/tripistry_lsn.git
      
   f. Push the baseline code to GitHub:
      git push -u origin main
      
   g. Create the develop branch (where all features will integrate) and push it:
      git checkout -b develop
      git push -u origin develop

2. Starting Work (Completed by Every Group Member)
   Once the repository has been initialized on GitHub:
   
   a. Clone the repository to your local machine:
      Open your terminal, navigate to your XAMPP htdocs folder (do NOT clone into a shared Google Drive), and run:
      cd C:\xampp\htdocs
      git clone https://github.com/enockNyamweya/tripistry_lsn.git
      
   b. Initialize your local database:
      Follow the instructions in Section 6 (HOW TO SETUP AND RUN) to create your local .tripistry_lsn.env file and seed your local MariaDB database. Your local app will not work until you do this!
      
   c. Switch to the integration branch:
      git switch develop
      
   d. Create and switch to your feature branch:
      git switch -c feature/your-assigned-feature
      
   e. Work on your features inside your designated Home Base folder.
   
   f. Commit and push your branch to GitHub when ready:
      git add .
      git commit -m "feat(scope): descriptive action message"
      git push origin feature/your-assigned-feature
      
   g. Open a Pull Request (PR) on GitHub to merge your feature branch back into develop.

