CREATE SCHEMA optimal;
CREATE TABLE Boards (
    bID int PRIMARY KEY AUTO_INCREMENT,
    size nvarchar(1) NOT NULL DEFAULT 'm', -- s,m,l for 5x4,6x5,7x6
    pattern nvarchar(42) NOT NULL, -- board pattern as a string
	combo int NOT NULL, -- number of combos
    style nvarchar(20) NOT NULL, -- row,combo,tpa etc
	styleAtt nvarchar(1), -- the att being counted
	styleCount int, -- number of row,combo,etc
	R int NOT NULL,
	B int NOT NULL,
	G int NOT NULL,
	L int NOT NULL,
	D int NOT NULL,
	H int NOT NULL,
	J int NOT NULL,
	X int NOT NULL,
	P int NOT NULL,
	M int NOT NULL,
	description nvarchar(100)
);