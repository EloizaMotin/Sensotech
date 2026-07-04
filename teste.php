<?php
require_once __DIR__ . '/../includes/functions.php';
requireJudge();

$stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = ?');
$stmt->execute([$_SESSION['judge_room_code']]);
$room = $stmt->fetch();

if (!$room || $room['status'] !== 'ativa') {
    unset($_SESSION['judge_room_code']);
    redirect(BASE_URL . '/julgador/entrar.php');
}

$samples = json_decode($room['samples'], true) ?: [];
$letters = ['A','B','C','D','E','F','G','H','I','J'];
$error = $_SESSION['judge_error'] ?? null;
unset($_SESSION['judge_error']);

$pageTitle = h($room['name']) . ' — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2><?= h($room['name']) ?></h2>
  <p class="desc"><?= h(TEST_TYPES[$room['test_type']]) ?> · Julgador: <?= h($_SESSION['judge_name']) ?></p>
  <?php if ($room['question']): ?><div class="question-box"><?= h($room['question']) ?></div><?php endif; ?>

  <form method="post" action="enviar.php">
    <?php if ($room['test_type'] === 'hedonica'): ?>
      <?php foreach ($samples as $s): ?>
        <div class="scale-wrap">
          <div class="scale-title"><?= h($s) ?></div>
          <div class="scale-ticks">
            <?php for ($n = 1; $n <= 9; $n++): ?>
              <label class="tick">
                <input type="radio" name="scale[<?= h($s) ?>]" value="<?= $n ?>" required>
                <span><?= $n ?></span>
              </label>
            <?php endfor; ?>
          </div>
          <div class="scale-labels"><span>Desgostei muitíssimo</span><span>Gostei muitíssimo</span></div>
        </div>
      <?php endforeach; ?>

    <?php elseif ($room['test_type'] === 'triangular'): ?>
      <p class="desc">Avalie as três amostras e indique qual delas é diferente das outras duas.</p>
      <?php foreach ($samples as $i => $s): ?>
        <label class="sample-card">
          <input type="radio" name="diferente" value="<?= h($s) ?>" required>
          <div class="sample-letter"><?= $letters[$i] ?></div>
          <div><?= h($s) ?></div>
        </label>
      <?php endforeach; ?>

    <?php elseif ($room['test_type'] === 'duotrio'):
      $referencia = $samples[0] ?? '';
      $candidatas = array_slice($samples, 1);
    ?>
      <div class="scale-wrap"><div class="scale-title">Amostra de referência</div><div><?= h($referencia) ?></div></div>
      <p class="desc">Compare as duas amostras abaixo com a referência e indique qual delas é igual a ela.</p>
      <?php foreach ($candidatas as $i => $s): ?>
        <label class="sample-card">
          <input type="radio" name="igual" value="<?= h($s) ?>" required>
          <div class="sample-letter"><?= $letters[$i] ?></div>
          <div><?= h($s) ?></div>
        </label>
      <?php endforeach; ?>

    <?php elseif ($room['test_type'] === 'pareada'): ?>
      <p class="desc">Compare as duas amostras e indique qual delas você prefere.</p>
      <?php foreach ($samples as $i => $s): ?>
        <label class="sample-card">
          <input type="radio" name="preferida" value="<?= h($s) ?>" required>
          <div class="sample-letter"><?= $letters[$i] ?></div>
          <div><?= h($s) ?></div>
        </label>
      <?php endforeach; ?>

    <?php elseif ($room['test_type'] === 'ordenacao'):
      $n = count($samples);
    ?>
      <p class="desc">Ordene as amostras atribuindo uma posição de 1 (menos preferida/intensa) a <?= $n ?> (mais preferida/intensa) para cada uma. Não repita posições.</p>
      <?php foreach ($samples as $s): ?>
        <div class="scale-wrap" style="display:flex;align-items:center;justify-content:space-between;">
          <div class="scale-title" style="margin:0;"><?= h($s) ?></div>
          <select name="ordem[<?= h($s) ?>]" style="width:90px;" required>
            <option value="">–</option>
            <?php for ($o = 1; $o <= $n; $o++): ?><option value="<?= $o ?>"><?= $o ?></option><?php endfor; ?>
          </select>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Enviar respostas</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
