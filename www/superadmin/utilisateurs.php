<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Vérifie que le superadmin est connecté
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY role, nom");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roles = [
    'employe' => 'Employé',
    'ag' => 'Associé gérant',
    'secretaire' => 'Secrétaire',
    'associe' => 'Associé simple',
    'superadmin' => 'Super Admin'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Super Admin - Gestion des utilisateurs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-people-fill"></i> Gestion des utilisateurs</h2> 
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour au dashboard
        </a>        
        <a href="ajouter_utilisateur.php" class="btn btn-success">
            <i class="bi bi-person-plus"></i> Ajouter un utilisateur
        </a>

    </div>
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Date création</th>
          <th>Dernière connexion</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['nom']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
              <span class="badge bg-primary"><?= $roles[$user['role']] ?? ucfirst($user['role']) ?></span>
            </td>
            <td><?= $user['date_creation'] ?></td>
            <td><?= $user['derniere_connexion'] ?? '-' ?></td>
            <td>
              <?php if ($user['role'] !== 'superadmin'): ?>
                <a href="modifier_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                <?php if ($user['role'] !== 'ag'): ?>
                  <a href="supprimer_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
</div>
</body>
</html>