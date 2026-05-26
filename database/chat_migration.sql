-- Chat Migration; adds MESSAGE table for private traveller-agency chat
-- Run AFTER importing schema.sql to add messaging capability
-- This table is intentionally separate from the 22-table core schema

CREATE TABLE IF NOT EXISTS MESSAGE (
    MessageID INT AUTO_INCREMENT PRIMARY KEY,
    SenderID INT NOT NULL,
    ReceiverID INT NOT NULL,
    Message TEXT NOT NULL,
    SentAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    IsRead TINYINT(1) DEFAULT 0,
    FOREIGN KEY (SenderID) REFERENCES USER(UserID) ON DELETE CASCADE,
    FOREIGN KEY (ReceiverID) REFERENCES USER(UserID) ON DELETE CASCADE
);
