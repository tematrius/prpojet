<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
include '../includes/dashboard-template.php';

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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
      body {
        background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
        font-family: 'Inter', 'Nunito', Arial, sans-serif;
      }
      h2 {
        font-weight: 800;
        color: #0d6efd;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px #0d6efd11;
      }
      .table {
        border-radius: 1.2rem;
        box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
        overflow: hidden;
        background: #fff;
      }
      thead th {
        background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
        color: #fff !important;
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: 0.3px;
        border: none !important;
      }
      tbody tr {
        transition: box-shadow 0.18s, transform 0.18s, background 0.18s;
        animation: rowFadeIn 0.7s;
      }
      @keyframes rowFadeIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
      }
      tbody tr:hover {
        background: #eaf7fb !important;
        box-shadow: 0 2px 8px #0dcaf033;
        transform: scale(1.01);
      }
      .badge {
        font-size: 0.95rem;
        font-weight: 700;
        padding: 0.3em 0.8em;
        border-radius: 0.7em;
        letter-spacing: 0.2px;
        box-shadow: 0 1px 4px #0d6efd13;
      }
      .badge.bg-primary      { background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important; color: #fff !important; }
      .badge.bg-success      { background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%) !important; color: #fff !important; }
      .badge.bg-warning      { background: linear-gradient(135deg, #ffc107 80%, #dc3545 100%) !important; color: #222 !important; }
      .badge.bg-info         { background: linear-gradient(135deg, #0dcaf0 80%, #6f42c1 100%) !important; color: #fff !important; }
      .badge.bg-danger       { background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%) !important; color: #fff !important; }
      .badge.bg-dark         { background: linear-gradient(135deg, #343a40 80%, #6c757d 100%) !important; color: #fff !important; }
      .btn {
        border-radius: 0.7rem !important;
        font-weight: 700 !important;
        padding: 0.45em 1.2em !important;
        font-size: 1rem !important;
        box-shadow: 0 2px 8px #0d6efd33;
        transition: background 0.18s, color 0.18s, transform 0.18s;
        border: none !important;
      }
      .btn-success {
        background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%) !important;
        color: #fff !important;
      }
      .btn-outline-secondary {
        background: #fff !important;
        color: #0d6efd !important;
        border: 2px solid #0d6efd !important;
      }
      .btn-outline-secondary:hover {
        background: #0d6efd !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-warning {
        background: #fff !important;
        color: #ffc107 !important;
        border: 2px solid #ffc107 !important;
      }
      .btn-outline-warning:hover {
        background: #ffc107 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
      .btn-outline-danger {
        background: #fff !important;
        color: #dc3545 !important;
        border: 2px solid #dc3545 !important;
      }
      .btn-outline-danger:hover {
        background: #dc3545 !important;
        color: #fff !important;
        transform: scale(1.07);
      }
    </style>
</head>
<body>
<div class="container mt-4">
    <?php if (!empty($_SESSION['action_message'])): ?>
      <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-info-circle-fill me-2"></i>
        <div><?= $_SESSION['action_message'] ?></div>
      </div>
      <?php unset($_SESSION['action_message']); ?>
    <?php endif; ?>
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
              <?php
                $role = $user['role'];
                $badgeClass = 'bg-primary';
                if ($role === 'employe') $badgeClass = 'bg-info';
                elseif ($role === 'ag') $badgeClass = 'bg-success';
                elseif ($role === 'secretaire') $badgeClass = 'bg-warning';
                elseif ($role === 'associe') $badgeClass = 'bg-dark';
                elseif ($role === 'superadmin') $badgeClass = 'bg-danger';
              ?>
              <span class="badge <?= $badgeClass ?>"><?= $roles[$role] ?? ucfirst($role) ?></span>
            </td>
            <td><?= $user['date_creation'] ?></td>
            <td><?= $user['derniere_connexion'] ?? '-' ?></td>
            <td>
              <?php if ($user['role'] !== 'superadmin'): ?>
                <a href="modifier_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                <?php if ($user['role'] !== 'ag'): ?>
                  <a href="supprimer_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
                <?php if ($user['is_active']): ?>
                  <a href="bloquer_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Bloquer l'utilisateur">
                    <i class="bi bi-lock"></i> Bloquer
                  </a>
                <?php else: ?>
                  <a href="debloquer_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-success" title="Débloquer l'utilisateur">
                    <i class="bi bi-unlock"></i> Débloquer
                  </a>
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