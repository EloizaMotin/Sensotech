<?php
require_once __DIR__ . '/includes/functions.php';

// Link de convite para julgador: index.php?code=ABC123
if (!empty($_GET['code'])) {
    redirect(BASE_URL . '/julgador/entrar.php?code=' . urlencode($_GET['code']));
}

if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/pesquisador/dashboard.php');
}

redirect(BASE_URL . '/auth/login.php');
