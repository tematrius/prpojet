<?php
session_start();
require 'includes/db.php';

// Affichage des messages
$message = '';
$bloque = false;
$bloque_expire = null;
if (!empty($_SESSION['login_message'])) {
    $message = $_SESSION['login_message'];
    unset($_SESSION['login_message']);
}
if (!empty($_SESSION['bloque'])) {
    $bloque = true;
    $bloque_expire = $_SESSION['bloque_expire'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>BNB Archive - Connexion</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body, html {
      height: 100%;
    }
    .left-panel {
      background-color: #0d6efd;
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    .left-panel h1 {
      font-size: 2rem;
      margin-top: 20px;
    }
    .login-form {
      max-width: 400px;
      margin: auto;
      padding: 30px;
    }
  </style>
</head>
<body>
  <div class="container-fluid h-100">
    <div class="row h-100">
      
      <!-- Colonne gauche : Logo -->
      <div class="col-md-5 left-panel">
        <i class="bi bi-archive-fill" style="font-size: 80px;"></i>
        <h1 class="mt-3">BNB Archives</h1>
        <p>Centralisation et sécurité documentaire</p>
      </div>

      <!-- Colonne droite : Connexion -->
      <div class="col-md-7 d-flex align-items-center">
        <form method="POST" action="login.php" class="login-form w-100">
          <h2 class="mb-4">Connexion</h2>
          <?php if ($message): ?>
          <div class="alert alert-danger" id="message-block">
              <?= $message ?>
          </div>
          <?php endif; ?>
          <?php if ($bloque && $bloque_expire): ?>
            <div>Déblocage dans : <span id="timer"></span></div>
          <?php endif; ?>
          <div class="mb-3">
            <label for="email" class="form-label">Adresse Email</label>
            <input type="email" name="email" class="form-control" required <?php if ($bloque) echo 'disabled'; ?> />
          </div>
          <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <input type="password" name="mot_de_passe" class="form-control" required <?php if ($bloque) echo 'disabled'; ?> />
          </div>
          <button type="submit" class="btn btn-primary w-100" <?php if ($bloque) echo 'disabled'; ?>>Se connecter</button>
        </form>
      </div>

    </div>
  </div>
  <?php if ($bloque && $bloque_expire): ?>
  <script>
    let expire = <?= $bloque_expire ?> * 1000;
    let reloaded = false;
    function countdown() {
      let now = Date.now();
      let diff = Math.max(0, Math.floor((expire - now) / 1000));
      let min = Math.floor(diff / 60); let sec = diff % 60;
      document.getElementById('timer').textContent = `${min} min ${sec} sec`;
      if (diff > 0) {
        setTimeout(countdown, 1000);
      } else if (!reloaded) {
        reloaded = true;
        window.location.reload();
      }
    }
    countdown();
  </script>
  <?php endif; ?>
</body>
</html>
