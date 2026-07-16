DROP DATABASE IF EXISTS pulseroom;
CREATE DATABASE IF NOT EXISTS pulseroom;

USE pulseroom;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('free', 'pro', 'admin') DEFAULT 'free',
    active BOOLEAN DEFAULT FALSE,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attempts INT DEFAULT 0,
    timedout BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        filetype VARCHAR(50) NOT NULL,
        filedata LONGBLOB NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT NOT NULL REFERENCES users(id),
        visibility TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS tokens (
    email VARCHAR(100) PRIMARY KEY,
    token_hash CHAR(64) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    purpose ENUM('register', 'reset') DEFAULT 'register'
);
