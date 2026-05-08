CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS participants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    plain_password TEXT NOT NULL,
    phone TEXT NULL,
    last_name TEXT NULL,
    first_name TEXT NULL,
    has_completed_profile INTEGER NOT NULL DEFAULT 0,
    has_logged_in INTEGER NOT NULL DEFAULT 0,
    has_participated INTEGER NOT NULL DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER IF NOT EXISTS participants_updated_at
AFTER UPDATE ON participants
FOR EACH ROW
BEGIN
    UPDATE participants SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TABLE IF NOT EXISTS tournament_results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    participant_id INTEGER NOT NULL UNIQUE,
    level TEXT NOT NULL CHECK(level IN ('easy', 'medium', 'hard')),
    elapsed_seconds INTEGER NOT NULL,
    attempts_count INTEGER NOT NULL,
    success INTEGER NOT NULL,
    is_random_mode INTEGER NOT NULL DEFAULT 1,
    random_number TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
);
