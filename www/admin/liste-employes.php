<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

// Vérifier que l'utilisateur est bien un secrétaire
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ag') {
    header('Location: /login.html');
    exit;
}

// Récupérer tous les employés
$stmt = $pdo->prepare("SELECT id, nom, email FROM utilisateurs WHERE role = 'employe'");
$stmt->execute();
$employes = $stmt->fetchAll();
?>

<style>
.container {
  max-width: 960px;
}
.table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.table th {
  background-color: #f5f7fa;
  color: #333;
  font-weight: 600;
}
.action-btns .btn {
  margin-right: 6px;
}
</style>

<div class="container mt-4">
  <h3 class="mb-4"><i class="bi bi-people"> </i>Liste des employés</h3>

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Nom complet</th>
        <th>Email</th>
        
      </tr>
    </thead>
    <tbody>
      <?php foreach ($employes as $emp): ?>
        <tr>
          <td><?= htmlspecialchars($emp['nom']) ?></td>
          <td><?= htmlspecialchars($emp['email']) ?></td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

