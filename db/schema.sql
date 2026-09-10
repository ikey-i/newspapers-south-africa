-- Newspapers South Africa — database schema
-- MySQL 5.7+ / MariaDB 10.2+ , InnoDB, utf8mb4.
-- Safe to run repeatedly: every statement uses IF NOT EXISTS.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Newspapers (the public directory)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS newspapers (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug             VARCHAR(160) NOT NULL,
    name             VARCHAR(160) NOT NULL,
    type             ENUM('community', 'independent', 'regional', 'national', 'online') NOT NULL DEFAULT 'community',
    tagline          VARCHAR(255) NULL,
    about            TEXT NULL,
    province         VARCHAR(64) NULL,
    city             VARCHAR(120) NULL,
    languages        VARCHAR(255) NULL,          -- comma-separated
    established_year SMALLINT UNSIGNED NULL,
    website          VARCHAR(255) NULL,
    email            VARCHAR(160) NULL,
    phone            VARCHAR(60) NULL,
    facebook         VARCHAR(255) NULL,
    twitter          VARCHAR(255) NULL,
    instagram        VARCHAR(255) NULL,
    logo_path        VARCHAR(255) NULL,
    is_featured      TINYINT(1) NOT NULL DEFAULT 0,
    status           ENUM('pending', 'active', 'rejected', 'suspended') NOT NULL DEFAULT 'pending',
    admin_notes      TEXT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    reviewed_at      DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_newspapers_slug (slug),
    KEY idx_newspapers_status (status, is_featured),
    KEY idx_newspapers_province (province, status),
    KEY idx_newspapers_type (type, status),
    FULLTEXT KEY ft_newspapers_search (name, about, city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Publisher accounts (newsroom logins; self-registered, admin-approved)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publishers (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    newspaper_id      INT UNSIGNED NOT NULL,
    name              VARCHAR(120) NOT NULL,
    email             VARCHAR(160) NOT NULL,
    password_hash     VARCHAR(255) NOT NULL,
    role              ENUM('owner', 'editor') NOT NULL DEFAULT 'owner',
    is_active         TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at DATETIME NULL,
    verify_token      CHAR(64) NULL,
    verify_sent_at    DATETIME NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at     DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_publishers_email (email),
    KEY idx_publishers_newspaper (newspaper_id),
    KEY idx_publishers_verify (verify_token),
    CONSTRAINT fk_publishers_newspaper FOREIGN KEY (newspaper_id) REFERENCES newspapers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Failed publisher login attempts (brute-force throttling)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS publisher_login_attempts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash     CHAR(64) NOT NULL,
    email       VARCHAR(160) NULL,
    successful  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pub_attempts_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Password reset tokens (publishers)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(160) NOT NULL,
    token_hash  CHAR(64) NOT NULL,
    ip_hash     CHAR(64) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    used_at     DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_resets_email (email, created_at),
    KEY idx_resets_ip (ip_hash, created_at),
    KEY idx_resets_token (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Public sign-up attempts (rate limiting newsroom registration by IP)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS signup_attempts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash     CHAR(64) NOT NULL,
    email       VARCHAR(160) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_signup_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Print / PDF editions (one dated issue of a newspaper)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS editions (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    newspaper_id  INT UNSIGNED NOT NULL,
    title         VARCHAR(200) NOT NULL,
    edition_date  DATE NULL,
    description   TEXT NULL,
    cover_path    VARCHAR(255) NULL,
    pdf_path      VARCHAR(255) NULL,
    pdf_size      INT UNSIGNED NULL,
    is_published  TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_editions_paper (newspaper_id, is_published, edition_date),
    CONSTRAINT fk_editions_newspaper FOREIGN KEY (newspaper_id) REFERENCES newspapers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Articles (the online newspaper: stories published by each newsroom)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    newspaper_id  INT UNSIGNED NOT NULL,
    edition_id    INT UNSIGNED NULL,
    slug          VARCHAR(200) NOT NULL,
    title         VARCHAR(255) NOT NULL,
    standfirst    VARCHAR(400) NULL,
    body_html     MEDIUMTEXT NULL,               -- sanitised HTML
    section       VARCHAR(60) NULL,
    hero_image_path VARCHAR(255) NULL,
    author_name   VARCHAR(120) NULL,
    status        ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    published_at  DATETIME NULL,
    views         INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_articles_paper_slug (newspaper_id, slug),
    KEY idx_articles_feed (newspaper_id, status, published_at),
    KEY idx_articles_section (section, status, published_at),
    KEY idx_articles_edition (edition_id),
    FULLTEXT KEY ft_articles_search (title, standfirst, body_html),
    CONSTRAINT fk_articles_newspaper FOREIGN KEY (newspaper_id) REFERENCES newspapers (id) ON DELETE CASCADE,
    CONSTRAINT fk_articles_edition FOREIGN KEY (edition_id) REFERENCES editions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Media library (images uploaded from the article editor)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS media (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    newspaper_id  INT UNSIGNED NOT NULL,
    path          VARCHAR(255) NOT NULL,
    bytes         INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_media_paper (newspaper_id, created_at),
    CONSTRAINT fk_media_newspaper FOREIGN KEY (newspaper_id) REFERENCES newspapers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Admin users (single admin to start; table supports more later)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username      VARCHAR(60) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Failed admin login attempts (brute-force throttling)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_login_attempts (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip_hash     CHAR(64) NOT NULL,
    username    VARCHAR(60) NULL,
    successful  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_attempts_ip (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Key/value settings (AdSense IDs, contact address, feature toggles)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    name       VARCHAR(80) NOT NULL,
    value      TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (name, value) VALUES
    ('ads_enabled', '0'),
    ('adsense_publisher_id', ''),
    ('adsense_auto_ads', '0'),
    ('adsense_slot_leaderboard', ''),
    ('adsense_slot_infeed', ''),
    ('adsense_slot_article', ''),
    ('adsense_slot_sidebar', ''),
    ('contact_email', '')
ON DUPLICATE KEY UPDATE name = name;
