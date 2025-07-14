<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secretaire') {
    header('Location: /login.html');
    exit;
}

$stmt = $pdo->prepare("SELECT id, nom, email FROM utilisateurs WHERE role = 'employe'");
$stmt->execute();
$employes = $stmt->fetchAll();
?>

<style>
.card-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1rem;
}
.card-employe {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  padding: 20px;
  transition: transform 0.2s ease;
  border: 1px solid #e9ecef;
}
.card-employe:hover {
  transform: translateY(-3px);
}
.card-employe h5 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #0d6efd;
}
.card-employe p {
  margin: 4px 0;
  font-size: 0.95rem;
  color: #555;
}
.card-employe .btn {
  margin-top: 8px;
  font-size: 0.85rem;
}
</style>

<div class="container mt-4">
  <h3 class="mb-4"><i class="bi bi-people-fill text-primary me-2"></i> Liste des employés</h3>

  <div class="card-container">
    <?php foreach ($employes as $emp): ?>
      <div class="card-employe">
        <h5><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($emp['nom']) ?></h5>
        <p><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($emp['email']) ?></p>
        <div class="d-flex justify-content-between mt-3">
          <a href="modifier-employe.php?id=<?= $emp['id'] ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil"></i> Modifier
          </a>
          <a href="regenerer-mdp.php?id=<?= $emp['id'] ?>" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-shield-lock"></i> M. Passe
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
