<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';


// Vérification de l'autorisation (seul AG peut accéder)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ag') {
  echo "<div class='container mt-4'><div class='alert alert-danger'>Accès refusé.</div></div>";
  exit;
}

$success = null;
$generated_password = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $noms = trim($_POST['noms']);
  $email = trim($_POST['email']);
  $role = ($_POST['role'] === 'a') ? 'a' : 'a'; // par défaut "a" (associé)

  $generated_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
  $password_hash = password_hash($generated_password, PASSWORD_BCRYPT);

  $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
  if ($stmt->execute([$noms, $email, $password_hash, $role])) {
    $success = "Associé ajouté avec succès. Mot de passe : <strong>$generated_password</strong>";
  } else {
    $success = "Erreur lors de l'ajout.";
  }
}
?>

<style>
.container {
  max-width: 600px;
}
</style>

<div class="container mt-4">
  <h3 class="mb-4"><i class="bi bi-person-plus"></i> Ajouter un associé</h3>

  <?php if ($success): ?>
    <div class="alert alert-info">
      <?= $success ?>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Noms complets</label>
      <input type="text" name="noms" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Adresse e-mail</label>
      <input type="email" name="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Rôle</label>
      <select name="role" class="form-select">
        <option value="a">Associé</option>
      </select>
    </div>

    <button type="submit" class="btn btn-primary">Ajouter l'associé</button>
  </form>
</div>
