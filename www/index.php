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
          <div class="mb-3">
            <label for="email" class="form-label">Adresse Email</label>
            <input type="email" name="email" class="form-control" required />
          </div>
          <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <input type="password" name="mot_de_passe" class="form-control" required />
          </div>
          <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
      </div>

    </div>
  </div>
</body>
</html>
