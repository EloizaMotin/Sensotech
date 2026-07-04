<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$code = strtoupper(trim($_GET['code'] ?? ''));
$stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = ? AND owner_id = ?');
$stmt->execute([$code, $_SESSION['user_id']]);
$room = $stmt->fetch();
if (!$room) {
    http_response_code(404);
    die('Sala não encontrada.');
}

$stmt = $pdo->prepare('SELECT * FROM responses WHERE room_id = ? ORDER BY submitted_at ASC');
$stmt->execute([$room['id']]);
$responses = $stmt->fetchAll();
$samples = json_decode($room['samples'], true) ?: [];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="resultados_' . $room['code'] . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM para acentuação correta no Excel

if ($room['test_type'] === 'hedonica') {
    fputcsv($out, array_merge(['julgador'], $samples));
    foreach ($responses as $r) {
        $a = json_decode($r['answers'], true);
        $row = [$r['judge_name']];
        foreach ($samples as $s) $row[] = $a[$s] ?? '';
        fputcsv($out, $row);
    }
} elseif ($room['test_type'] === 'triangular') {
    fputcsv($out, ['julgador', 'amostra_diferente']);
    foreach ($responses as $r) {
        $a = json_decode($r['answers'], true);
        fputcsv($out, [$r['judge_name'], $a['diferente'] ?? '']);
    }
} elseif ($room['test_type'] === 'duotrio') {
    fputcsv($out, ['julgador', 'referencia', 'amostra_igual']);
    foreach ($responses as $r) {
        $a = json_decode($r['answers'], true);
        fputcsv($out, [$r['judge_name'], $samples[0] ?? '', $a['igual'] ?? '']);
    }
} elseif ($room['test_type'] === 'pareada') {
    fputcsv($out, ['julgador', 'amostra_preferida']);
    foreach ($responses as $r) {
        $a = json_decode($r['answers'], true);
        fputcsv($out, [$r['judge_name'], $a['preferida'] ?? '']);
    }
} elseif ($room['test_type'] === 'ordenacao') {
    fputcsv($out, array_merge(['julgador'], $samples));
    foreach ($responses as $r) {
        $a = json_decode($r['answers'], true);
        $ordem = $a['ordem'] ?? [];
        $row = [$r['judge_name']];
        foreach ($samples as $s) $row[] = $ordem[$s] ?? '';
        fputcsv($out, $row);
    }
}

fclose($out);
exit;
