-- SensoTech — Schema do banco de dados MySQL
-- Importe este arquivo antes de usar a aplicação:
--   mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS sensotech CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sensotech;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  reset_token VARCHAR(10) DEFAULT NULL,
  reset_expiry DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(10) NOT NULL UNIQUE,
  name VARCHAR(200) NOT NULL,
  question TEXT,
  test_type VARCHAR(20) NOT NULL,
  samples TEXT NOT NULL,           -- JSON array de strings
  owner_id INT NOT NULL,
  status ENUM('ativa','encerrada') NOT NULL DEFAULT 'ativa',
  ai_analysis TEXT DEFAULT NULL,   -- JSON com o resultado da análise de IA
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS responses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  judge_name VARCHAR(150) NOT NULL,
  answers TEXT NOT NULL,           -- JSON com as respostas do julgador
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
