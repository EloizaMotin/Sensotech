<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$code = strtoupper(trim($_GET['code'] ?? ''));
$stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = ? AND owner_id = ?');
$stmt->execute([$code, $_SESSION['user_id']]);
$room = $stmt->fetch();

if (!$room) {
    $pageTitle = 'Sala não encontrada — SensoTech';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="card"><p class="empty">Sala não encontrada.</p></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// ---- Ações (finalizar / excluir) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'finalizar') {
        $pdo->prepare('UPDATE rooms SET status = "encerrada" WHERE id = ?')->execute([$room['id']]);
        redirect(BASE_URL . '/pesquisador/sala.php?code=' . urlencode($code));
    } elseif ($_POST['action'] === 'excluir') {
        $pdo->prepare('DELETE FROM rooms WHERE id = ?')->execute([$room['id']]);
        redirect(BASE_URL . '/pesquisador/dashboard.php');
    } elseif ($_POST['action'] === 'gerar_analise') {
        gerarAnaliseIA($pdo, $room);
        redirect(BASE_URL . '/pesquisador/sala.php?code=' . urlencode($code));
    }
}

$stmt = $pdo->prepare('SELECT * FROM responses WHERE room_id = ? ORDER BY submitted_at ASC');
$stmt->execute([$room['id']]);
$responses = $stmt->fetchAll();
$samples = json_decode($room['samples'], true) ?: [];

function gerarAnaliseIA(PDO $pdo, array $room) {
    if (!ANTHROPIC_API_KEY) {
        $result = ['resumo' => 'Chave de API não configurada no servidor (variável ANTHROPIC_API_KEY). Configure-a para habilitar a análise com IA.', 'grafico' => null];
        $pdo->prepare('UPDATE rooms SET ai_analysis = ? WHERE id = ?')->execute([json_encode($result, JSON_UNESCAPED_UNICODE), $room['id']]);
        return;
    }

    $stmt = $pdo->prepare('SELECT * FROM responses WHERE room_id = ? ORDER BY submitted_at ASC');
    $stmt->execute([$room['id']]);
    $responses = $stmt->fetchAll();
    if (!$responses) return;

    $samples = json_decode($room['samples'], true) ?: [];
    $lines = [];
    $lines[] = "Sala: {$room['name']}";
    $lines[] = "Pergunta feita ao julgador: " . ($room['question'] ?: '(não informada)');
    $lines[] = "Tipo de teste: " . TEST_TYPES[$room['test_type']];
    $lines[] = "Amostras: " . implode(', ', $samples);
    $lines[] = "Total de julgadores: " . count($responses);
    $lines[] = "Respostas brutas:";
    foreach ($responses as $r) {
        $lines[] = "- {$r['judge_name']}: {$r['answers']}";
    }
    $dataset = implode("\n", $lines);

    $prompt = <<<PROMPT
Você é um analista de dados especializado em análise sensorial de alimentos. Abaixo estão os resultados brutos de um teste sensorial aplicado a alunos do IFPR.

Responda ESTRITAMENTE em JSON válido, sem markdown, sem texto antes ou depois, no seguinte formato:
{
  "resumo": "um parágrafo curto (até 4 frases) em português explicando a principal conclusão da análise",
  "grafico": {
    "titulo": "título curto do gráfico",
    "eixo": "o que o valor de cada barra representa, ex: nível de aceitação de 0 a 100",
    "itens": [
      { "amostra": "nome da amostra", "valor": numero de 0 a 100, "observacao": "frase curta explicando esse valor" }
    ]
  }
}

Calcule "valor" de forma coerente com o tipo de teste: para escala hedônica, converta a média (1-9) para uma escala de 0 a 100; para testes de escolha (triangular, duo-trio, comparação pareada), use a porcentagem de julgadores que escolheram aquela amostra; para ordenação, inverta a posição média para que valores mais altos representem maior preferência (100 = mais preferida). Inclua uma linha em "itens" para cada amostra da sala.

Dados:
{$dataset}
PROMPT;

    $payload = json_encode([
        'model' => ANTHROPIC_MODEL,
        'max_tokens' => 1200,
        'messages' => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = ['resumo' => 'Não foi possível gerar a análise no momento. Tente novamente em instantes.', 'grafico' => null];
    if ($response) {
        $data = json_decode($response, true);
        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if (isset($block['text'])) $text .= $block['text'];
        }
        $text = trim($text);
        $text = preg_replace('/^```json/i', '', $text);
        $text = preg_replace('/^```/', '', $text);
        $text = preg_replace('/```$/', '', $text);
        $parsed = json_decode(trim($text), true);
        if ($parsed) $result = $parsed;
    }

    $pdo->prepare('UPDATE rooms SET ai_analysis = ? WHERE id = ?')
        ->execute([json_encode($result, JSON_UNESCAPED_UNICODE), $room['id']]);
}

$pageTitle = h($room['name']) . ' — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<a class="back-link" href="dashboard.php">← Voltar às salas</a>

<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
    <div>
      <h2><?= h($room['name']) ?></h2>
      <p class="desc"><?= h(TEST_TYPES[$room['test_type']]) ?> · <?= count($responses) ?> resposta(s) ·
        <span class="status-pill <?= $room['status'] === 'ativa' ? 'status-ativa' : 'status-encerrada' ?>"><?= h($room['status']) ?></span>
      </p>
      <p class="hint" style="margin-top:4px;">Criada em <?= formatDateBR($room['created_at']) ?></p>
      <?php if ($room['question']): ?>
        <p class="hint" style="margin-top:6px;"><strong>Pergunta:</strong> <?= h($room['question']) ?></p>
      <?php endif; ?>
    </div>
    <div class="row" style="margin-top:0;">
      <?php if ($room['status'] === 'ativa'): ?>
        <form method="post" onsubmit="return confirm('Finalizar esta sala? Ela deixará de aceitar novas respostas.');">
          <input type="hidden" name="action" value="finalizar">
          <button class="btn btn-ghost btn-sm" type="submit">Finalizar sala</button>
        </form>
      <?php endif; ?>
      <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir esta sala e todas as respostas? Esta ação não pode ser desfeita.');">
        <input type="hidden" name="action" value="excluir">
        <button class="btn btn-danger btn-sm" type="submit">Excluir</button>
      </form>
    </div>
  </div>
  <div class="code-display"><?= h($room['code']) ?></div>
  <p class="hint" style="text-align:center;">Compartilhe este código com os julgadores.</p>
</div>

<div class="card">
  <h2>Resultados</h2>
  <p class="desc">Médias e contagens calculadas em tempo real a partir das respostas.</p>
  <?php if (!$responses): ?>
    <p class="empty">Nenhuma resposta recebida ainda. Compartilhe o código com os julgadores.</p>
  <?php else:
    $results = calculateResults($room, $responses);
    if ($room['test_type'] === 'duotrio'):
      $referencia = $results['_referencia'];
      $itens = $results['itens'];
  ?>
    <p class="hint">Referência: <strong><?= h($referencia) ?></strong> — contagem de quantas vezes cada amostra foi apontada como igual à referência.</p>
    <?php foreach ($itens as $row): ?>
      <div class="bar-row">
        <div class="bar-label"><?= h($row['label']) ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= (float)$row['pct'] ?>%"></div></div>
        <div class="bar-value"><?= h($row['display']) ?></div>
      </div>
    <?php endforeach; else:
      foreach ($results as $row): ?>
      <div class="bar-row">
        <div class="bar-label"><?= h($row['label']) ?></div>
        <div class="bar-track"><div class="bar-fill" style="width:<?= (float)$row['pct'] ?>%"></div></div>
        <div class="bar-value"><?= h($row['display']) ?></div>
      </div>
    <?php endforeach; endif; ?>
    <?php if ($room['test_type'] === 'triangular'): ?>
      <p class="hint">Número de vezes que cada amostra foi apontada como "a diferente".</p>
    <?php elseif ($room['test_type'] === 'pareada'): ?>
      <p class="hint">Número de vezes que cada amostra foi a preferida.</p>
    <?php elseif ($room['test_type'] === 'ordenacao'): ?>
      <p class="hint">Posição média atribuída por amostra (1 = menos preferida/intensa).</p>
    <?php endif; ?>
    <div class="row">
      <a class="btn btn-ghost btn-sm" href="export_csv.php?code=<?= urlencode($room['code']) ?>">Exportar CSV</a>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Análise com IA</h2>
  <p class="desc">Gere uma leitura interpretativa dos resultados — tendências, destaques e possíveis conclusões — a partir dos dados coletados.</p>

  <?php if (!$responses): ?>
    <p class="empty">Ainda não há respostas suficientes para gerar uma análise.</p>
  <?php else:
    $ai = $room['ai_analysis'] ? json_decode($room['ai_analysis'], true) : null;
    if ($ai): ?>
      <?php if (!empty($ai['resumo'])): ?><div class="ai-result"><?= h($ai['resumo']) ?></div><?php endif; ?>
      <?php $itens = $ai['grafico']['itens'] ?? [];
        if ($itens):
          $maxVal = max(1, ...array_map(fn($i) => (float)($i['valor'] ?? 0), $itens));
      ?>
        <div class="ai-chart">
          <div class="ai-chart-title"><?= h($ai['grafico']['titulo'] ?? 'Análise gerada por IA') ?></div>
          <?php foreach ($itens as $i): $valor = (float)($i['valor'] ?? 0); ?>
            <div class="bar-row">
              <div class="bar-label"><?= h($i['amostra'] ?? '') ?></div>
              <div class="bar-track"><div class="bar-fill ai-bar-fill" style="width:<?= ($valor / $maxVal) * 100 ?>%"></div></div>
              <div class="bar-value"><?= h($i['valor'] ?? '') ?></div>
            </div>
            <?php if (!empty($i['observacao'])): ?><p class="hint" style="margin:-4px 0 10px 0;"><?= h($i['observacao']) ?></p><?php endif; ?>
          <?php endforeach; ?>
          <?php if (!empty($ai['grafico']['eixo'])): ?><p class="hint">Escala: <?= h($ai['grafico']['eixo']) ?></p><?php endif; ?>
        </div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="gerar_analise">
        <div class="row"><button class="btn btn-ghost btn-sm" type="submit">Gerar novamente</button></div>
      </form>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="action" value="gerar_analise">
        <div class="row"><button class="btn btn-primary btn-sm" type="submit">Analisar com IA</button></div>
      </form>
    <?php endif;
  endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
