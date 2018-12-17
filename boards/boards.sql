CREATE SCHEMA optimal;
CREATE TABLE boards (
    bID int PRIMARY KEY AUTO_INCREMENT,
    size nvarchar(1) NOT NULL DEFAULT 'm', -- s,m,l for 5x4,6x5,7x6
    pattern nvarchar(42) NOT NULL, -- board pattern as a string
	combo int NOT NULL, -- number of combos
	description nvarchar(100)
);