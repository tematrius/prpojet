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
    // Si le blocage est expiré, on supprime les variables et réactive le formulaire
    if ($bloque_expire && time() > $bloque_expire) {
        unset($_SESSION['bloque'], $_SESSION['bloque_expire']);
        $bloque = false;
        $bloque_expire = null;
    }
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
        <?php
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <form method="POST" action="login.php" class="login-form w-100" id="loginForm">
          <h2 class="mb-4">Connexion</h2>
          <?php if ($message): ?>
          <div class="alert alert-danger" id="message-block">
              <?= $message ?>
          </div>
          <?php endif; ?>
          <?php if ($bloque && $bloque_expire): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2" id="block-message">
              <i class="bi bi-lock-fill" style="font-size:1.5rem;"></i>
              <div>
                <strong>Compte temporairement bloqué&nbsp;!</strong><br>
                <span>Déblocage dans&nbsp;: <span id="timer" class="fw-bold"></span></span>
              </div>
            </div>
          <?php endif; ?>
          <div class="mb-3">
            <label for="email" class="form-label">Adresse Email</label>
            <input type="email" name="email" class="form-control" required <?php if ($bloque) echo 'disabled'; ?> />
          </div>
          <div class="mb-3 position-relative">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <div class="input-group">
              <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required <?php if ($bloque) echo 'disabled'; ?> />
              <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1" style="border-top-left-radius:0;border-bottom-left-radius:0;">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <button type="submit" class="btn btn-primary w-100" <?php if ($bloque) echo 'disabled'; ?>>Se connecter</button>
        </form>
      </div>

    </div>
  </div>
  <script>
    // Affichage/masquage du mot de passe
    document.getElementById('togglePassword').addEventListener('click', function() {
      const pwd = document.getElementById('mot_de_passe');
      const icon = document.getElementById('eyeIcon');
      if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        pwd.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    });
    // Validation côté client du formulaire de connexion
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const email = this.email.value.trim();
      const password = this.mot_de_passe.value;
      let valid = true;
      let errorMsg = '';
      // Vérification email
      if (!email.match(/^\S+@\S+\.\S+$/)) {
        valid = false;
        errorMsg += 'Adresse email invalide.<br>';
      }
      // Vérification mot de passe
      if (!password) {
        valid = false;
        errorMsg += 'Mot de passe requis.<br>';
      }
      if (!valid) {
        e.preventDefault();
        let block = document.getElementById('message-block');
        if (!block) {
          block = document.createElement('div');
          block.className = 'alert alert-danger';
          block.id = 'message-block';
          this.prepend(block);
        }
        block.innerHTML = errorMsg;
      }
    });

    <?php if ($bloque && $bloque_expire): ?>
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
        // Réactive les champs et le bouton
        document.querySelector('input[name="email"]').disabled = false;
        document.querySelector('input[name="mot_de_passe"]').disabled = false;
        document.querySelector('button[type="submit"]').disabled = false;
        document.getElementById('timer').textContent = 'Débloqué';
        // Recharge la page pour supprimer le blocage côté serveur
        setTimeout(() => window.location.reload(), 800);
      }
    }
    countdown();
    <?php endif; ?>
  </script>
</body>
</html>
