<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secretaire') {
    header('Location: /index.php');
    exit;
}

$success = null;
$error = null;
$generated_password = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noms = trim($_POST['noms']);
    $email = trim($_POST['email']);

    // Vérification si l'email existe déjà
    $check = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetchColumn() > 0) {
        $error = "Cette adresse e-mail est déjà utilisée par un autre utilisateur.";
    } else {
        $generated_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $password_hash = password_hash($generated_password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'employe')");
        if ($stmt->execute([$noms, $email, $password_hash])) {
            $success = "Employé ajouté avec succès.";
        } else {
            $error = "Une erreur est survenue lors de l’ajout.";
        }
    }
}

include '../includes/dashboard-template.php';
?>

<style>
.container {
  max-width: 600px;
}
</style>

<div class="container mt-4">
  <h3><i class="bi bi-person-plus"></i> Ajouter un nouvel employé</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success">
      <?= $success ?><br>
      Mot de passe généré :
      <div class="input-group mt-2" style="max-width: 300px;">
        <input type="text" class="form-control" value="<?= htmlspecialchars($generated_password) ?>" id="passwordField" readonly>
        <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()">Copier</button>
      </div>
    </div>
  <?php endif; ?>

  <form method="POST" class="mt-3">
    <div class="mb-3">
      <label for="noms" class="form-label">Noms complets</label>
      <input type="text" name="noms" id="noms" class="form-control" required>
    </div>
    <div class="mb-3">
      <label for="email" class="form-label">Adresse e-mail</label>
      <input type="email" name="email" id="email" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i> Ajouter l’employé
    </button>
  </form>
</div>

<script>
function copyPassword() {
  const input = document.getElementById("passwordField");
  input.select();
  input.setSelectionRange(0, 99999);
  document.execCommand("copy");
  alert("Mot de passe copié : " + input.value);
}
</script>
