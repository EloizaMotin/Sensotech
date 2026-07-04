<?php
require_once __DIR__ . '/../includes/functions.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!$email) {
        $error = 'Informe o e-mail de login cadastrado.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Não encontramos uma conta com este e-mail.';
        } else {
            $token = genResetToken();
            $expiry = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutos

            $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?');
            $stmt->execute([$token, $expiry, $user['id']]);

            // Envio de e-mail real. Requer um servidor de e-mail (SMTP) configurado
            // no php.ini ou o uso de uma biblioteca como PHPMailer.
            $assunto = 'SensoTech — Código de recuperação de senha';
            $mensagem = "Olá, {$user['nome']}!\n\nSeu código de verificação é: {$token}\n\nEle expira em 15 minutos.";
            $enviado = @mail($user['email'], $assunto, $mensagem);

            $_SESSION['pending_reset_email'] = $user['email'];

            if (!$enviado) {
                // Ambiente sem SMTP configurado: registra no log do servidor
                // para não bloquear o teste da aplicação.
                error_log("[SensoTech] Código de recuperação para {$user['email']}: {$token}");
            }

            redirect(BASE_URL . '/auth/redefinir.php');
        }
    }
}

$pageTitle = 'Recuperar senha — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card center-card">
  <h2>Recuperar senha</h2>
  <p class="desc">Informe o e-mail cadastrado. Vamos enviar um código de verificação para esse mesmo e-mail.</p>
  <form method="post">
    <label>E-mail de login</label>
    <input type="email" name="email" placeholder="voce@ifpr.edu.br" required>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <div class="row">
      <button class="btn btn-primary" type="submit">Enviar código de recuperação</button>
    </div>
  </form>
  <p class="hint"><a class="back-link" style="font-size:12.5px;" href="login.php">← Voltar ao login</a></p>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
