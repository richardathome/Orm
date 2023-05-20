CREATE TABLE datatypes
(
    id         INTEGER PRIMARY KEY,
    allow_null INTEGER,
    not_null   INTEGER NOT NULL,
    integer    INTEGER,
    real       REAL,
    text       TEXT,
    blob       BLOB
);
