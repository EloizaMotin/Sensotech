<?php
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['user_id'])) {
    redirect(BASE_URL . '/pesquisador/dashboard.php');
}

$error = null;
$nome = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (!$nome) {
        $error = 'Informe seu nome.';
    } elseif (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail válido.';
    } elseif (!$senha || strlen($senha) < 4) {
        $error = 'A senha deve ter pelo menos 4 caracteres.';
    } elseif ($senha !== $senha2) {
        $error = 'As senhas não coincidem.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Já existe uma conta com este e-mail. Tente entrar.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (nome, email, senha_hash) VALUES (?, ?, ?)');
            $stmt->execute([$nome, $email, $hash]);

            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_nome'] = $nome;
            $_SESSION['user_email'] = $email;
            redirect(BASE_URL . '/pesquisador/dashboard.php');
        }
    }
}

$pageTitle = 'Criar conta — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card center-card">
  <h2>Criar conta</h2>
  <p class="desc">Cadastre-se como pesquisador para criar e gerenciar salas de análise.</p>
  <form method="post">
    <label>Nome</label>
    <input type="text" name="nome" placeholder="Seu nome completo" value="<?= h($nome) ?>" required>
    <label>E-mail</label>
    <input type="email" name="email" placeholder="voce@ifpr.edu.br" value="<?= h($email) ?>" required>
    <p class="hint">Também será usado para recuperação de senha, caso você a esqueça.</p>
    <label>Senha</label>
    <input type="password" name="senha" placeholder="mínimo 4 caracteres" required>
    <label>Confirmar senha</label>
    <input type="password" name="senha2" placeholder="repita a senha" required>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Criar conta</button>
    </div>
  </form>
  <p class="hint">Já tem conta? <a class="back-link" style="font-size:12.5px;" href="login.php">Entrar</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
