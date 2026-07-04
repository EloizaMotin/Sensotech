<?php
require_once __DIR__ . '/../config.php';

const TEST_TYPES = [
    'hedonica'   => 'Escala Hedônica',
    'triangular' => 'Teste Triangular',
    'duotrio'    => 'Teste Duo-Trio',
    'pareada'    => 'Comparação Pareada',
    'ordenacao'  => 'Ordenação',
];

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        redirect(BASE_URL . '/auth/login.php');
    }
}

function requireJudge() {
    if (empty($_SESSION['judge_name']) || empty($_SESSION['judge_room_code'])) {
        redirect(BASE_URL . '/julgador/entrar.php');
    }
}

function genCode(PDO $pdo) {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare('SELECT id FROM rooms WHERE code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetch());
    return $code;
}

function genResetToken() {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $token = '';
    for ($i = 0; $i < 6; $i++) {
        $token .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $token;
}

function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) < 2) return $email;
    [$local, $domain] = $parts;
    $visible = substr($local, 0, min(2, strlen($local)));
    $hidden = str_repeat('*', max(1, strlen($local) - strlen($visible)));
    return $visible . $hidden . '@' . $domain;
}

function formatDateBR($datetime) {
    if (!$datetime) return '';
    $ts = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
    return date('d/m/Y \à\s H:i', $ts);
}

/**
 * Calcula os resultados agregados de uma sala a partir das respostas,
 * de acordo com o tipo de teste. Retorna um array de linhas prontas
 * para exibição em forma de barras.
 */
function calculateResults(array $room, array $responses) {
    $samples = json_decode($room['samples'], true) ?: [];
    $rows = [];

    if ($room['test_type'] === 'hedonica') {
        foreach ($samples as $s) {
            $vals = [];
            foreach ($responses as $r) {
                $answers = json_decode($r['answers'], true);
                if (isset($answers[$s])) $vals[] = (float)$answers[$s];
            }
            $avg = count($vals) ? array_sum($vals) / count($vals) : 0;
            $rows[] = ['label' => $s, 'pct' => ($avg / 9) * 100, 'display' => number_format($avg, 1) . '/9'];
        }
    } elseif ($room['test_type'] === 'triangular') {
        $counts = array_fill_keys($samples, 0);
        foreach ($responses as $r) {
            $answers = json_decode($r['answers'], true);
            if (isset($answers['diferente']) && array_key_exists($answers['diferente'], $counts)) {
                $counts[$answers['diferente']]++;
            }
        }
        $max = max(1, ...array_values($counts));
        foreach ($samples as $s) {
            $rows[] = ['label' => $s, 'pct' => ($counts[$s] / $max) * 100, 'display' => $counts[$s] . 'x'];
        }
    } elseif ($room['test_type'] === 'duotrio') {
        $referencia = $samples[0] ?? '';
        $candidatas = array_slice($samples, 1);
        $counts = array_fill_keys($candidatas, 0);
        foreach ($responses as $r) {
            $answers = json_decode($r['answers'], true);
            if (isset($answers['igual']) && array_key_exists($answers['igual'], $counts)) {
                $counts[$answers['igual']]++;
            }
        }
        $max = max(1, ...array_values($counts ?: [0]));
        foreach ($candidatas as $s) {
            $rows[] = ['label' => $s, 'pct' => ($counts[$s] / $max) * 100, 'display' => $counts[$s] . 'x'];
        }
        $rows = ['_referencia' => $referencia, 'itens' => $rows];
        return $rows;
    } elseif ($room['test_type'] === 'pareada') {
        $counts = array_fill_keys($samples, 0);
        $total = count($responses);
        foreach ($responses as $r) {
            $answers = json_decode($r['answers'], true);
            if (isset($answers['preferida']) && array_key_exists($answers['preferida'], $counts)) {
                $counts[$answers['preferida']]++;
            }
        }
        foreach ($samples as $s) {
            $pct = $total ? ($counts[$s] / $total) * 100 : 0;
            $rows[] = ['label' => $s, 'pct' => $pct, 'display' => $counts[$s] . 'x'];
        }
    } elseif ($room['test_type'] === 'ordenacao') {
        $sums = array_fill_keys($samples, 0);
        $counts = array_fill_keys($samples, 0);
        foreach ($responses as $r) {
            $answers = json_decode($r['answers'], true);
            $ordem = $answers['ordem'] ?? [];
            foreach ($samples as $s) {
                if (isset($ordem[$s])) {
                    $sums[$s] += (float)$ordem[$s];
                    $counts[$s]++;
                }
            }
        }
        $avgs = [];
        foreach ($samples as $s) {
            $avgs[$s] = $counts[$s] ? $sums[$s] / $counts[$s] : 0;
        }
        $maxAvg = max(1, ...array_values($avgs ?: [0]));
        foreach ($samples as $s) {
            $rows[] = ['label' => $s, 'pct' => ($avgs[$s] / $maxAvg) * 100, 'display' => number_format($avgs[$s], 1)];
        }
    }

    return $rows;
}
