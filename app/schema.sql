CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(40) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    plain_password VARCHAR(80) NOT NULL,
    phone VARCHAR(40) NULL,
    last_name VARCHAR(120) NULL,
    first_name VARCHAR(120) NULL,
    has_completed_profile TINYINT(1) NOT NULL DEFAULT 0,
    has_logged_in TINYINT(1) NOT NULL DEFAULT 0,
    has_participated TINYINT(1) NOT NULL DEFAULT 0,
    account_locked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tournament_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL UNIQUE,
    level ENUM('easy', 'medium', 'hard') NOT NULL,
    elapsed_seconds INT NOT NULL,
    attempts_count INT NOT NULL,
    success TINYINT(1) NOT NULL,
    is_random_mode TINYINT(1) NOT NULL DEFAULT 1,
    random_number VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tournament_participant
        FOREIGN KEY (participant_id) REFERENCES participants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tournament_level_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    level ENUM('easy', 'medium', 'hard') NOT NULL,
    status ENUM('started', 'completed', 'game_over', 'abandoned') NOT NULL DEFAULT 'started',
    elapsed_seconds INT NULL,
    attempts_count INT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    is_random_mode TINYINT(1) NOT NULL DEFAULT 1,
    random_number VARCHAR(20) NULL,
    started_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_participant_level (participant_id, level),
    CONSTRAINT fk_tournament_level_participant
        FOREIGN KEY (participant_id) REFERENCES participants(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
