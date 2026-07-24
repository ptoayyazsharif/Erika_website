-- Ask Erika — Coaching Knowledge Base schema
-- Paste into phpMyAdmin (SQL tab) after creating the database.

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  cohort_id     INT NOT NULL DEFAULT 1,
  role          ENUM('owner','mentee') NOT NULL DEFAULT 'mentee',
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE transcripts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  recorded_at  DATE NULL,
  raw_text     LONGTEXT NOT NULL,
  status       ENUM('uploaded','processing','done','failed') DEFAULT 'uploaded',
  chunk_total  SMALLINT DEFAULT 0,
  chunk_done   SMALLINT DEFAULT 0,
  last_error   TEXT NULL,
  purge_at     DATE NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chunks (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  transcript_id INT NOT NULL,
  seq           SMALLINT NOT NULL,
  body          MEDIUMTEXT NOT NULL,
  status        ENUM('queued','done','failed') DEFAULT 'queued',
  UNIQUE KEY (transcript_id, seq),
  FOREIGN KEY (transcript_id) REFERENCES transcripts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  transcript_id INT NOT NULL,
  type          ENUM('qa','principle','script','story') DEFAULT 'qa',
  question      VARCHAR(500) NOT NULL,
  answer        TEXT NOT NULL,
  topics        JSON NULL,
  timestamp_ref VARCHAR(20) NULL,
  `sensitive`   TINYINT(1) DEFAULT 0,   -- backticked: SENSITIVE is a reserved word
  status        ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  approved_at   DATETIME NULL,
  INDEX (status),
  INDEX (transcript_id),
  FULLTEXT KEY ft_q (question),
  FOREIGN KEY (transcript_id) REFERENCES transcripts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE name_map (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  real_name   VARCHAR(120) NOT NULL,
  placeholder VARCHAR(40) NOT NULL,
  UNIQUE KEY (real_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE answers_cache (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  question_hash CHAR(64) NOT NULL UNIQUE,
  question_text VARCHAR(500) NOT NULL,
  answer        TEXT NOT NULL,
  item_ids      JSON NULL,
  hit_count     INT DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE queries (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NULL,
  question     VARCHAR(500) NOT NULL,
  was_answered TINYINT(1) DEFAULT 0,
  item_ids     JSON NULL,
  from_cache   TINYINT(1) DEFAULT 0,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (created_at),
  INDEX (was_answered)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
