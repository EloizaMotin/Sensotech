<?php
require_once __DIR__ . '/../includes/functions.php';

if (empty($_SESSION['judge_thanks'])) {
    redirect(BASE_URL . '/julgador/entrar.php');
}
unset($_SESSION['judge_thanks']);

$pageTitle = 'Obrigado! — SensoTech';
require __DIR__ . '/../includes/header.php';
?>
<div class="card center-card thankyou">
  <div style="font-size:38px;">✓</div>
  <div class="big">Obrigado!</div>
  <p class="desc">Suas respostas foram registradas com sucesso.</p>
  <a class="btn btn-ghost" href="entrar.php">Responder outra sala</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
