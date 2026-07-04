<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/pesquisador/dashboard.php');
}

$error = null;
$info = $_SESSION['flash_info'] ?? null;
unset($_SESSION['flash_info']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        $error = 'Informe e-mail e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($senha, $user['senha_hash'])) {
            $error = 'Não foi possível realizar o login. Verifique suas credenciais.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_email'] = $user['email'];
            redirect(BASE_URL . '/pesquisador/dashboard.php');
        }
    }
}

$pageTitle = 'Entrar — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card center-card">
  <h2>Entrar</h2>
  <p class="desc">Acesse sua conta de pesquisador.</p>
  <form method="post">
    <label>E-mail</label>
    <input type="email" name="email" placeholder="voce@ifpr.edu.br" required>
    <label>Senha</label>
    <input type="password" name="senha" placeholder="Sua senha" required>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <?php if ($info): ?><div class="hint" style="color:var(--sage-deep);font-weight:600;"><?= h($info) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Entrar</button>
    </div>
  </form>
  <p class="hint">
    <a class="back-link" style="font-size:12.5px;" href="esqueci.php">Esqueceu a senha?</a>
  </p>
  <p class="hint">Ainda não tem conta?
    <a class="back-link" style="font-size:12.5px;" href="cadastro.php">Cadastre-se</a>
  </p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
