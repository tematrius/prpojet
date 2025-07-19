<?php
session_start();
require_once '../includes/db.php';
include '../includes/dashboard-template.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Super Admin - Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="mb-0"><i class="bi bi-speedometer"></i> Tableau de bord Super Admin</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="bi bi-people-fill" style="font-size:2rem;"></i>
            <h5 class="mt-2">Gestion des utilisateurs</h5>
            <a href="utilisateurs.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="bi bi-key-fill" style="font-size:2rem;"></i>
            <h5 class="mt-2">Gestion des clés de chiffrement</h5>
            <a href="cles.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="bi bi-journal-text" style="font-size:2rem;"></i>
            <h5 class="mt-2">Logs</h5>
            <a href="logs.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body text-center">
            <i class="bi bi-bar-chart" style="font-size:2rem;"></i>
            <h5 class="mt-2">Statistiques</h5>
            <a href="stats.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
