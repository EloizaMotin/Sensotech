<?php
require_once __DIR__ . '/../includes/functions.php';
requireJudge();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/julgador/teste.php');
}

$stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = ?');
$stmt->execute([$_SESSION['judge_room_code']]);
$room = $stmt->fetch();

if (!$room) {
    unset($_SESSION['judge_room_code']);
    redirect(BASE_URL . '/julgador/entrar.php');
}
if ($room['status'] !== 'ativa') {
    $_SESSION['judge_error'] = 'Esta sala foi encerrada antes do envio. Fale com o pesquisador.';
    redirect(BASE_URL . '/julgador/teste.php');
}

$samples = json_decode($room['samples'], true) ?: [];
$answers = [];

if ($room['test_type'] === 'hedonica') {
    $scale = $_POST['scale'] ?? [];
    foreach ($samples as $s) {
        if (!isset($scale[$s]) || $scale[$s] === '') {
            $_SESSION['judge_error'] = 'Responda a escala para todas as amostras antes de enviar.';
            redirect(BASE_URL . '/julgador/teste.php');
        }
        $answers[$s] = (int)$scale[$s];
    }
} elseif ($room['test_type'] === 'triangular') {
    $val = $_POST['diferente'] ?? '';
    if (!$val || !in_array($val, $samples, true)) {
        $_SESSION['judge_error'] = 'Selecione qual amostra é diferente das demais.';
        redirect(BASE_URL . '/julgador/teste.php');
    }
    $answers['diferente'] = $val;
} elseif ($room['test_type'] === 'duotrio') {
    $candidatas = array_slice($samples, 1);
    $val = $_POST['igual'] ?? '';
    if (!$val || !in_array($val, $candidatas, true)) {
        $_SESSION['judge_error'] = 'Selecione qual amostra é igual à referência.';
        redirect(BASE_URL . '/julgador/teste.php');
    }
    $answers['igual'] = $val;
} elseif ($room['test_type'] === 'pareada') {
    $val = $_POST['preferida'] ?? '';
    if (!$val || !in_array($val, $samples, true)) {
        $_SESSION['judge_error'] = 'Selecione qual amostra você prefere.';
        redirect(BASE_URL . '/julgador/teste.php');
    }
    $answers['preferida'] = $val;
} elseif ($room['test_type'] === 'ordenacao') {
    $ordemRaw = $_POST['ordem'] ?? [];
    $ordem = [];
    $usados = [];
    foreach ($samples as $s) {
        $v = $ordemRaw[$s] ?? '';
        if ($v === '') {
            $_SESSION['judge_error'] = 'Atribua uma posição para todas as amostras.';
            redirect(BASE_URL . '/julgador/teste.php');
        }
        if (in_array($v, $usados, true)) {
            $_SESSION['judge_error'] = 'Cada posição só pode ser usada uma vez.';
            redirect(BASE_URL . '/julgador/teste.php');
        }
        $usados[] = $v;
        $ordem[$s] = (int)$v;
    }
    $answers['ordem'] = $ordem;
}

$stmt = $pdo->prepare('INSERT INTO responses (room_id, judge_name, answers) VALUES (?, ?, ?)');
$stmt->execute([$room['id'], $_SESSION['judge_name'], json_encode($answers, JSON_UNESCAPED_UNICODE)]);

unset($_SESSION['judge_room_code'], $_SESSION['judge_name']);
$_SESSION['judge_thanks'] = true;
redirect(BASE_URL . '/julgador/obrigado.php');
