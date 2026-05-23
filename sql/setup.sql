DROP DATABASE IF EXISTS tripistry_lsn;
CREATE DATABASE tripistry_lsn;
USE tripistry_lsn;

CREATE TABLE USER (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(255) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    UserType ENUM('Traveller','Agency') NOT NULL,
    DateCreated DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE TRAVELLER (
    UserID INT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    PassportNum VARCHAR(20),
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE
);

CREATE TABLE TRAVEL_AGENCY (
    UserID INT PRIMARY KEY,
    AgencyName VARCHAR(200) NOT NULL,
    VerificationStatus VARCHAR(30),
    CommissionRate DECIMAL(5,2),
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE
);

CREATE TABLE DESTINATION (
    DestinationID INT AUTO_INCREMENT PRIMARY KEY,
    City VARCHAR(100) NOT NULL,
    Country VARCHAR(100) NOT NULL,
    Latitude DECIMAL(9,6),
    Longitude DECIMAL(9,6),
    Description TEXT,
    ImageURL VARCHAR(500)
);

CREATE TABLE FLIGHT (
    FlightID INT AUTO_INCREMENT PRIMARY KEY,
    Airline VARCHAR(100) NOT NULL,
    FlightNumber VARCHAR(20) NOT NULL,
    DepartureCity VARCHAR(100) NOT NULL,
    ArrivalCity VARCHAR(100) NOT NULL,
    DepartureTime DATETIME NOT NULL,
    ArrivalTime DATETIME NOT NULL,
    Price DECIMAL(10,2) NOT NULL
);

CREATE TABLE ACCOMODATION (
    AccomodationID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    Type VARCHAR(50),
    StarRating INT DEFAULT 3,
    PricePerNight DECIMAL(10,2) NOT NULL,
    Address VARCHAR(300),
    DestinationID INT,
    FOREIGN KEY (DestinationID) REFERENCES DESTINATION(DestinationID) ON DELETE SET NULL
);

CREATE TABLE RESTAURANT (
    RestaurantID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    CuisineType VARCHAR(100),
    PriceRange VARCHAR(50),
    Address VARCHAR(300),
    Rating DECIMAL(2,1) DEFAULT 0,
    DestinationID INT,
    FOREIGN KEY (DestinationID) REFERENCES DESTINATION(DestinationID) ON DELETE SET NULL
);

CREATE TABLE ATTRACTION (
    AttractionID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(200) NOT NULL,
    Type VARCHAR(100),
    EntryFee DECIMAL(10,2) DEFAULT 0,
    Description TEXT,
    OpeningHours VARCHAR(200),
    DestinationID INT,
    FOREIGN KEY (DestinationID) REFERENCES DESTINATION(DestinationID) ON DELETE SET NULL
);

CREATE TABLE PACKAGE (
    PackageID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(200) NOT NULL,
    Description TEXT,
    Price DECIMAL(12,2) NOT NULL,
    DurationDays INT DEFAULT 1,
    StartDate DATE,
    EndDate DATE,
    MaxTravellers INT DEFAULT 10,
    IsGroupTrip TINYINT(1) DEFAULT 0,
    ImageURL VARCHAR(500),
    Status VARCHAR(20) DEFAULT 'Active'
);

CREATE TABLE CURATES (
    UserID INT,
    PackageID INT,
    PRIMARY KEY (PackageID),
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE
);

CREATE TABLE VISITS (
    PackageID INT,
    DestinationID INT,
    PRIMARY KEY (PackageID, DestinationID),
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE,
    FOREIGN KEY (DestinationID) REFERENCES DESTINATION(DestinationID) ON DELETE CASCADE
);

CREATE TABLE INCLUDES_FLIGHT (
    PackageID INT,
    FlightID INT,
    PRIMARY KEY (FlightID),
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE,
    FOREIGN KEY (FlightID) REFERENCES FLIGHT(FlightID) ON DELETE CASCADE
);

CREATE TABLE INCLUDES_STAY (
    PackageID INT,
    AccomodationID INT,
    PRIMARY KEY (AccomodationID),
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE,
    FOREIGN KEY (AccomodationID) REFERENCES ACCOMODATION(AccomodationID) ON DELETE CASCADE
);

CREATE TABLE PACKAGE_RESTAURANT (
    PackageID INT,
    RestaurantID INT,
    PRIMARY KEY (PackageID, RestaurantID),
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE,
    FOREIGN KEY (RestaurantID) REFERENCES RESTAURANT(RestaurantID) ON DELETE CASCADE
);

CREATE TABLE PACKAGE_ATTRACTION (
    PackageID INT,
    AttractionID INT,
    PRIMARY KEY (PackageID, AttractionID),
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE,
    FOREIGN KEY (AttractionID) REFERENCES ATTRACTION(AttractionID) ON DELETE CASCADE
);

CREATE TABLE REVIEW (
    ReviewID INT AUTO_INCREMENT,
    UserID INT,
    PackageID INT,
    Comment TEXT,
    RatingScore TINYINT NOT NULL CHECK (RatingScore BETWEEN 1 AND 5),
    DatePosted DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ReviewID, UserID, PackageID),
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE
);

CREATE TABLE BOOKS (
    BookingID INT AUTO_INCREMENT,
    UserID INT,
    PackageID INT,
    BookingDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalCost DECIMAL(12,2) NOT NULL,
    NumTravellers INT DEFAULT 1,
    Status VARCHAR(20) DEFAULT 'Pending',
    PRIMARY KEY (BookingID, UserID, PackageID),
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE
);

CREATE TABLE TRAVELLER_PHONE (
    UserID INT,
    PhoneNumber VARCHAR(20),
    PRIMARY KEY (UserID, PhoneNumber),
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE
);

CREATE TABLE ACCOMODATION_AMENITY (
    AccomodationID INT,
    Amenity VARCHAR(100),
    PRIMARY KEY (AccomodationID, Amenity),
    FOREIGN KEY (AccomodationID) REFERENCES ACCOMODATION(AccomodationID) ON DELETE CASCADE
);

CREATE TABLE GROUP_TRIP (
    GroupTripID INT AUTO_INCREMENT PRIMARY KEY,
    PackageID INT NOT NULL,
    GroupName VARCHAR(200) NOT NULL,
    MinParticipants INT DEFAULT 2,
    MaxParticipants INT DEFAULT 20,
    Status VARCHAR(20) DEFAULT 'Open',
    DepartureDate DATE,
    ReturnDate DATE,
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE CASCADE
);

CREATE TABLE MESSAGE (
    MessageID INT AUTO_INCREMENT PRIMARY KEY,
    SenderID INT NOT NULL,
    ReceiverID INT NOT NULL,
    PackageID INT,
    Message TEXT NOT NULL,
    IsRead TINYINT(1) DEFAULT 0,
    SentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SenderID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (ReceiverID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (PackageID) REFERENCES PACKAGE(PackageID) ON DELETE SET NULL
);

CREATE TABLE GROUP_TRIP_ENROLMENT (
    EnrolmentID INT AUTO_INCREMENT PRIMARY KEY,
    GroupTripID INT NOT NULL,
    UserID INT NOT NULL,
    EnrolmentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Status VARCHAR(20) DEFAULT 'Confirmed',
    FOREIGN KEY (GroupTripID) REFERENCES GROUP_TRIP(GroupTripID) ON DELETE CASCADE,
    FOREIGN KEY (UserID) REFERENCES USER(UserID) ON DELETE CASCADE
);

-- Seed data
INSERT INTO USER (Email, Password, UserType) VALUES
('admin@tripistry.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agency'),
('traveller@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Traveller'),
('agency2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Agency');

INSERT INTO TRAVEL_AGENCY (UserID, AgencyName, VerificationStatus, CommissionRate) VALUES
(1, 'Tripistry Official', 'Verified', 10.00),
(3, 'Wanderlust Travel Co', 'Verified', 12.50);

INSERT INTO TRAVELLER (UserID, FirstName, LastName, PassportNum) VALUES
(2, 'John', 'Doe', 'AB123456');

INSERT INTO DESTINATION (City, Country, Latitude, Longitude, Description, ImageURL) VALUES
('Cape Town', 'South Africa', -33.9249, 18.4241, 'Stunning coastal city with Table Mountain, beaches, and vibrant culture.', 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600'),
('Paris', 'France', 48.8566, 2.3522, 'The City of Light — art, cuisine, and iconic landmarks like the Eiffel Tower.', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600'),
('Tokyo', 'Japan', 35.6762, 139.6503, 'A dazzling mix of ultramodern and traditional, from neon-lit skyscrapers to historic temples.', 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600'),
('Bali', 'Indonesia', -8.3405, 115.0920, 'Tropical paradise known for rice terraces, temples, and surf beaches.', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600'),
('New York', 'USA', 40.7128, -74.0060, 'The Big Apple — world-class dining, Broadway shows, and iconic skyline.', 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600'),
('Dubai', 'UAE', 25.2048, 55.2708, 'Futuristic city with luxury shopping, ultramodern architecture, and desert adventures.', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600');

INSERT INTO FLIGHT (Airline, FlightNumber, DepartureCity, ArrivalCity, DepartureTime, ArrivalTime, Price) VALUES
('Emirates', 'EK770', 'Johannesburg', 'Cape Town', '2026-07-01 08:00:00', '2026-07-01 10:00:00', 2500.00),
('Air France', 'AF990', 'Johannesburg', 'Paris', '2026-07-01 20:00:00', '2026-07-02 06:00:00', 8500.00),
('Qatar Airways', 'QR1365', 'Johannesburg', 'Tokyo', '2026-07-01 14:00:00', '2026-07-02 10:00:00', 12000.00),
('Singapore Airlines', 'SQ478', 'Johannesburg', 'Bali', '2026-07-02 09:00:00', '2026-07-02 22:00:00', 9500.00),
('British Airways', 'BA054', 'Johannesburg', 'New York', '2026-07-01 19:00:00', '2026-07-02 06:00:00', 11000.00),
('Emirates', 'EK765', 'Johannesburg', 'Dubai', '2026-07-01 22:00:00', '2026-07-02 08:00:00', 7800.00);

INSERT INTO ACCOMODATION (Name, Type, StarRating, PricePerNight, Address, DestinationID) VALUES
('Table Bay Hotel', 'Hotel', 5, 3500.00, 'Quay 6, V&A Waterfront, Cape Town', 1),
('Four Seasons George V', 'Hotel', 5, 8000.00, '31 Avenue George V, Paris', 2),
('Park Hyatt Tokyo', 'Hotel', 5, 6500.00, '3-7-1-2 Nishi-Shinjuku, Tokyo', 3),
('Ayana Resort Bali', 'Resort', 5, 4000.00, 'Jl. Karang Mas Sejahtera, Bali', 4),
('The Plaza Hotel', 'Hotel', 5, 9000.00, '768 5th Ave, New York', 5),
('Burj Al Arab', 'Hotel', 7, 15000.00, 'Jumeirah Beach Rd, Dubai', 6),
('Mojo Hotel Cape Town', 'Hostel', 3, 500.00, 'Regent Rd, Sea Point, Cape Town', 1),
('Ibis Paris Montmartre', 'Hotel', 3, 1200.00, '5 Rue Caulaincourt, Paris', 2);

INSERT INTO RESTAURANT (Name, CuisineType, PriceRange, Address, Rating, DestinationID) VALUES
('The Test Kitchen', 'Fine Dining', '$$$', 'The Old Biscuit Mill, Cape Town', 4.8, 1),
('Le Jules Verne', 'French', '$$$', 'Eiffel Tower, Paris', 4.7, 2),
('Narisawa', 'Japanese', '$$$', '2-6-15 Minami Aoyama, Tokyo', 4.9, 3),
('Locavore', 'Indonesian', '$$$', 'Jl. Dewi Sita, Bali', 4.6, 4),
('Le Bernardin', 'Seafood', '$$$', '155 W 51st St, New York', 4.8, 5),
('Nobu Dubai', 'Japanese', '$$$', 'Atlantis The Palm, Dubai', 4.5, 6);

INSERT INTO ATTRACTION (Name, Type, EntryFee, Description, OpeningHours, DestinationID) VALUES
('Table Mountain', 'Nature', 350.00, 'Iconic flat-topped mountain with cable car access.', '08:00 - 18:00', 1),
('Eiffel Tower', 'Landmark', 700.00, 'Iconic iron lattice tower with observation decks.', '09:00 - 23:45', 2),
('Senso-ji Temple', 'Temple', 0.00, 'Oldest temple in Tokyo with vibrant market street.', '06:00 - 17:00', 3),
('Uluwatu Temple', 'Temple', 50.00, 'Clifftop temple with stunning sunset views.', '09:00 - 18:00', 4),
('Statue of Liberty', 'Landmark', 500.00, 'Iconic American symbol on Liberty Island.', '08:30 - 16:00', 5),
('Burj Khalifa', 'Landmark', 900.00, 'World\'s tallest building with observation deck.', '08:30 - 23:00', 6),
('V&A Waterfront', 'Shopping', 0.00, 'Harbourfront shopping and entertainment hub.', '10:00 - 21:00', 1),
('Louvre Museum', 'Museum', 450.00, 'World\'s largest art museum, home to Mona Lisa.', '09:00 - 18:00', 2);

INSERT INTO PACKAGE (Title, Description, Price, DurationDays, StartDate, EndDate, MaxTravellers, IsGroupTrip, ImageURL, Status) VALUES
('Cape Town Explorer', 'Discover the Mother City with a luxury stay at the V&A Waterfront, Table Mountain cable car, and fine dining.', 8500.00, 5, '2026-07-01', '2026-07-05', 10, 0, 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600', 'Active'),
('Paris Romance', 'Romantic getaway to Paris including Eiffel Tower, Louvre, Michelin-star dining, and 5-star accommodation.', 22000.00, 7, '2026-07-01', '2026-07-07', 8, 0, 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600', 'Active'),
('Tokyo Adventure', 'Explore the best of Tokyo — temples, sushi, neon lights, and luxury at Park Hyatt.', 28000.00, 7, '2026-07-02', '2026-07-08', 6, 0, 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600', 'Active'),
('Bali Bliss', 'Tropical escape to Bali with resort stay, temple visits, and authentic cuisine.', 16000.00, 7, '2026-07-03', '2026-07-09', 12, 1, 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600', 'Active'),
('NYC Luxury Weekend', 'Experience New York in style — The Plaza, Broadway, and world-class dining.', 25000.00, 4, '2026-07-08', '2026-07-11', 4, 0, 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600', 'Active'),
('Dubai Extravaganza', 'Ultimate luxury in Dubai — Burj Al Arab, desert safari, and Burj Khalifa.', 32000.00, 5, '2026-07-05', '2026-07-09', 6, 1, 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600', 'Active'),
('Budget Cape Town', 'Affordable Cape Town experience with hostel stay and top attractions.', 3500.00, 4, '2026-07-10', '2026-07-13', 20, 1, 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=600', 'Active');

INSERT INTO CURATES (UserID, PackageID) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(3, 5), (3, 6), (3, 7);

INSERT INTO VISITS (PackageID, DestinationID) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 1);

INSERT INTO INCLUDES_FLIGHT (PackageID, FlightID) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6);

INSERT INTO INCLUDES_STAY (PackageID, AccomodationID) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6), (7, 7);

INSERT INTO PACKAGE_RESTAURANT (PackageID, RestaurantID) VALUES
(1, 1), (2, 2), (3, 3), (4, 4), (5, 5), (6, 6);

INSERT INTO PACKAGE_ATTRACTION (PackageID, AttractionID) VALUES
(1, 1), (1, 7), (2, 2), (2, 8), (3, 3), (4, 4), (5, 5), (6, 6);

INSERT INTO GROUP_TRIP (PackageID, GroupName, MinParticipants, MaxParticipants, Status, DepartureDate, ReturnDate) VALUES
(4, 'Bali Group Adventure July', 4, 12, 'Open', '2026-07-03', '2026-07-09'),
(6, 'Dubai Luxury Group', 3, 6, 'Open', '2026-07-05', '2026-07-09'),
(7, 'Budget Cape Town Crew', 5, 20, 'Open', '2026-07-10', '2026-07-13');

INSERT INTO TRAVELLER_PHONE (UserID, PhoneNumber) VALUES
(2, '+27123456789');

INSERT INTO REVIEW (UserID, PackageID, Comment, RatingScore) VALUES
(2, 1, 'Amazing trip! Cape Town is beautiful and the hotel was outstanding.', 5),
(2, 2, 'Paris was magical. Well organized package.', 4);
