CREATE TABLE authors ( id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, salt TEXT, url TEXT, about TEXT , rating INTEGER);
CREATE TABLE languages ( id INTEGER PRIMARY KEY, name TEXT, url TEXT );
CREATE TABLE snippets ( id INTEGER PRIMARY KEY, language INTEGER, author INTEGER, title TEXT, code TEXT , rating INTEGER, created_at INTEGER, updated_at INTEGER);
