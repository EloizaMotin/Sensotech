<?php
require_once __DIR__ . '/../includes/functions.php';

$error = null;
$prefill = $_GET['code'] ?? ($_SESSION['judge_room_code'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));

    if (!$nome || !$codigo) {
        $error = 'Preencha seu nome e o código.';
        $prefill = $codigo;
    } else {
        $stmt = $pdo->prepare('SELECT * FROM rooms WHERE code = ?');
        $stmt->execute([$codigo]);
        $room = $stmt->fetch();

        if (!$room) {
            $error = 'Não foi possível acessar a sala. Verifique o código informado e tente novamente.';
            $prefill = $codigo;
        } elseif ($room['status'] !== 'ativa') {
            $error = 'Esta sala já foi encerrada.';
            $prefill = $codigo;
        } else {
            $_SESSION['judge_name'] = $nome;
            $_SESSION['judge_room_code'] = $codigo;
            redirect(BASE_URL . '/julgador/teste.php');
        }
    }
}

$pageTitle = 'Entrar em uma sala — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card center-card">
  <h2>Entrar em uma sala</h2>
  <p class="desc">Informe seu nome e o código de acesso fornecido pelo pesquisador.</p>
  <?php if (!empty($_GET['code'])): ?>
    <p class="hint" style="color:var(--sage-deep);font-weight:600;">Código preenchido automaticamente a partir do link recebido.</p>
  <?php endif; ?>
  <form method="post">
    <label>Seu nome</label>
    <input type="text" name="nome" placeholder="Nome do julgador" required>
    <label>Código da sala</label>
    <input type="text" name="codigo" value="<?= h($prefill) ?>" placeholder="Ex: A1B2C3" style="text-transform:uppercase;font-family:'IBM Plex Mono',monospace;letter-spacing:.08em;" required>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Entrar</button>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
