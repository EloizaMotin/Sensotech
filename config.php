<?php
/**
 * SensoTech — Configuração e conexão com o banco de dados.
 * Ajuste as credenciais abaixo de acordo com o seu servidor MySQL.
 */

session_start();

// ---- Credenciais do banco de dados ----
$DB_HOST = 'localhost';
$DB_NAME = 'sensotech';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Erro de conexão com o banco de dados. Verifique as credenciais em config.php.');
}

// ---- Chave da API Anthropic (usada na análise com IA) ----
// Defina como variável de ambiente no servidor (recomendado) ou substitua abaixo.
// NUNCA suba uma chave de API real para um repositório público.
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');
define('ANTHROPIC_MODEL', 'claude-sonnet-5');

// Caminho base da aplicação (ajuste se instalar em um subdiretório)
define('BASE_URL', '/sensotech-php');
