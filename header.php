<?php
// Espera que $pageTitle esteja definido pela página que inclui este arquivo.
$currentScript = $_SERVER['SCRIPT_NAME'];
$isJulgador = strpos($currentScript, '/julgador/') !== false;
$isPesquisador = !$isJulgador;

$pesqLink = !empty($_SESSION['user_id'])
    ? BASE_URL . '/pesquisador/dashboard.php'
    : BASE_URL . '/auth/login.php';
$julgLink = BASE_URL . '/julgador/entrar.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle ?? 'SensoTech') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div id="app">
  <div class="brand">
    <img src="<?= BASE_URL ?>/images/logo.png" alt="SensoTech" class="logo-img">
  </div>
  <p class="subtitle">Sistema educacional para gestão de análises sensoriais — IFPR Campus Colombo.</p>

  <div class="tabs">
    <a class="tab <?= $isPesquisador ? 'active' : '' ?>" href="<?= $pesqLink ?>">Pesquisador</a>
    <a class="tab <?= $isJulgador ? 'active' : '' ?>" href="<?= $julgLink ?>">Julgador</a>
  </div>
