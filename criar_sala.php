<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = null;
$name = $question = $amostrasRaw = '';
$tipo = 'hedonica';

$hints = [
    'hedonica'   => ['placeholder' => 'Ex: Amostra 1, Amostra 2', 'hint' => 'Cada amostra listada receberá uma escala hedônica de 1 a 9 para o julgador avaliar.'],
    'triangular' => ['placeholder' => 'Ex: A, B, C (use exatamente 3)', 'hint' => 'O teste triangular exige exatamente 3 amostras — duas serão iguais e uma diferente na aplicação real; aqui todas ficam disponíveis para avaliação.'],
    'duotrio'    => ['placeholder' => 'Ex: Referência, Amostra B, Amostra C', 'hint' => 'Informe exatamente 3 amostras. A primeira da lista é usada como referência; o julgador compara as outras duas com ela.'],
    'pareada'    => ['placeholder' => 'Ex: Amostra A, Amostra B', 'hint' => 'Informe exatamente 2 amostras. O julgador indicará qual das duas prefere.'],
    'ordenacao'  => ['placeholder' => 'Ex: Amostra 1, Amostra 2, Amostra 3, Amostra 4', 'hint' => 'Informe de 3 a 10 amostras. O julgador irá ordená-las da menos para a mais preferida/intensa.'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['nome'] ?? '');
    $question = trim($_POST['pergunta'] ?? '');
    $tipo = $_POST['tipo'] ?? 'hedonica';
    $amostrasRaw = trim($_POST['amostras'] ?? '');
    $samples = array_values(array_filter(array_map('trim', explode(',', $amostrasRaw))));

    if (!$name) {
        $error = 'Dê um nome para a sala.';
    } elseif (!$question) {
        $error = 'Escreva a pergunta que será exibida ao julgador.';
    } elseif (count($samples) < 2) {
        $error = 'Informe pelo menos duas amostras.';
    } elseif ($tipo === 'triangular' && count($samples) !== 3) {
        $error = 'O teste triangular exige exatamente 3 amostras.';
    } elseif ($tipo === 'duotrio' && count($samples) !== 3) {
        $error = 'O teste duo-trio exige exatamente 3 amostras (referência + 2).';
    } elseif ($tipo === 'pareada' && count($samples) !== 2) {
        $error = 'A comparação pareada exige exatamente 2 amostras.';
    } elseif ($tipo === 'ordenacao' && (count($samples) < 3 || count($samples) > 10)) {
        $error = 'A ordenação exige entre 3 e 10 amostras.';
    } else {
        $code = genCode($pdo);
        $stmt = $pdo->prepare('INSERT INTO rooms (code, name, question, test_type, samples, owner_id, status) VALUES (?, ?, ?, ?, ?, ?, "ativa")');
        $stmt->execute([$code, $name, $question, $tipo, json_encode($samples, JSON_UNESCAPED_UNICODE), $_SESSION['user_id']]);
        redirect(BASE_URL . '/pesquisador/sala.php?code=' . urlencode($code));
    }
}

$cfg = $hints[$tipo];
$pageTitle = 'Criar sala — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<a class="back-link" href="dashboard.php">← Voltar</a>
<div class="card">
  <h2>Criar nova sala</h2>
  <p class="desc">Defina a pergunta, o tipo de teste e as amostras que serão avaliadas.</p>
  <form method="post" id="form-criar">
    <label>Nome da sala</label>
    <input type="text" name="nome" placeholder="Ex: Análise de biscoitos integrais" value="<?= h($name) ?>" required>

    <label>Pergunta para o julgador</label>
    <textarea name="pergunta" rows="2" placeholder="Ex: Qual dessas amostras de biscoito você mais gostou em relação à doçura?" required><?= h($question) ?></textarea>
    <p class="hint">Essa é a pergunta exata que o julgador vai ler antes de responder o teste. Escreva do jeito que quer que apareça.</p>

    <label>Tipo de teste</label>
    <select name="tipo" id="c-tipo" onchange="atualizarDica()">
      <?php foreach (TEST_TYPES as $key => $label): ?>
        <option value="<?= h($key) ?>" <?= $tipo === $key ? 'selected' : '' ?>><?= h($label) ?><?php
          if ($key === 'hedonica') echo ' (aceitação, 1 a 9)';
          if ($key === 'triangular') echo ' (3 amostras, uma diferente)';
          if ($key === 'duotrio') echo ' (referência + 2 amostras)';
          if ($key === 'pareada') echo ' (2 amostras, qual prefere)';
          if ($key === 'ordenacao') echo ' (3 a 10 amostras)';
        ?></option>
      <?php endforeach; ?>
    </select>

    <label>Amostras (separadas por vírgula)</label>
    <input type="text" name="amostras" id="c-amostras" placeholder="<?= h($cfg['placeholder']) ?>" value="<?= h($amostrasRaw) ?>" required>
    <p class="hint" id="c-hint"><?= h($cfg['hint']) ?></p>

    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Criar sala e gerar código</button>
    </div>
  </form>
</div>
<script>
const HINTS = <?= json_encode($hints, JSON_UNESCAPED_UNICODE) ?>;
function atualizarDica(){
  const tipo = document.getElementById('c-tipo').value;
  document.getElementById('c-amostras').placeholder = HINTS[tipo].placeholder;
  document.getElementById('c-hint').textContent = HINTS[tipo].hint;
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
