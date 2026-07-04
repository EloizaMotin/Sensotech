<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stmt = $pdo->prepare('SELECT * FROM rooms WHERE owner_id = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$rooms = $stmt->fetchAll();

// contagem de respostas por sala
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM responses WHERE room_id = ?');

$pageTitle = 'Minhas salas — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
    <div>
      <h2>Olá, <?= h(explode(' ', $_SESSION['user_nome'])[0]) ?></h2>
      <p class="desc">Suas salas de análise sensorial</p>
    </div>
    <div class="row" style="margin-top:0;">
      <a class="btn btn-primary" href="criar_sala.php">+ Nova sala</a>
      <a class="btn btn-ghost btn-sm" href="../auth/logout.php">Sair</a>
    </div>
  </div>

  <?php if (!$rooms): ?>
    <p class="empty">Você ainda não criou nenhuma sala.</p>
  <?php else: foreach ($rooms as $r):
      $countStmt->execute([$r['id']]);
      $total = $countStmt->fetchColumn();
  ?>
    <div class="room-item">
      <div>
        <div class="room-name"><?= h($r['name']) ?></div>
        <div class="room-meta"><?= h(TEST_TYPES[$r['test_type']]) ?> · código <?= h($r['code']) ?> · <?= (int)$total ?> resposta(s) · criada em <?= formatDateBR($r['created_at']) ?></div>
      </div>
      <div class="row" style="margin-top:0;align-items:center;">
        <span class="status-pill <?= $r['status'] === 'ativa' ? 'status-ativa' : 'status-encerrada' ?>"><?= h($r['status']) ?></span>
        <a class="btn btn-ghost btn-sm" href="sala.php?code=<?= urlencode($r['code']) ?>">Ver sala</a>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
