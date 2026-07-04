<?php
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['pending_reset_email'])) {
    redirect(BASE_URL . '/auth/esqueci.php');
}
$email = $_SESSION['pending_reset_email'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user) {
    unset($_SESSION['pending_reset_email']);
    redirect(BASE_URL . '/auth/esqueci.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = strtoupper(trim($_POST['token'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (!$token || $token !== $user['reset_token']) {
        $error = 'Código inválido.';
    } elseif (!$user['reset_expiry'] || strtotime($user['reset_expiry']) < time()) {
        $error = 'Código expirado. Solicite um novo.';
    } elseif (!$senha || strlen($senha) < 4) {
        $error = 'A nova senha deve ter pelo menos 4 caracteres.';
    } elseif ($senha !== $senha2) {
        $error = 'As senhas não coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET senha_hash = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);

        unset($_SESSION['pending_reset_email']);
        $_SESSION['flash_info'] = 'Senha redefinida com sucesso! Você já pode entrar com a nova senha.';
        redirect(BASE_URL . '/auth/login.php');
    }
}

$pageTitle = 'Verificar código — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card center-card">
  <h2>Verificar código</h2>
  <p class="desc">Enviamos um código de verificação para <strong><?= h(maskEmail($user['email'])) ?></strong>.</p>
  <p class="hint">Não recebeu? Verifique a caixa de spam. Se o servidor não tiver envio de e-mail configurado, o código também fica registrado no log do servidor (error_log).</p>
  <form method="post">
    <label>Código recebido</label>
    <input type="text" name="token" placeholder="Digite o código" style="text-transform:uppercase;font-family:'IBM Plex Mono',monospace;letter-spacing:.08em;" required>
    <label>Nova senha</label>
    <input type="password" name="senha" placeholder="mínimo 4 caracteres" required>
    <label>Confirmar nova senha</label>
    <input type="password" name="senha2" placeholder="repita a nova senha" required>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Redefinir senha</button>
    </div>
  </form>
  <p class="hint"><a class="back-link" style="font-size:12.5px;" href="esqueci.php">← Reenviar / trocar e-mail</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
