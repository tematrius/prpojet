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
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
      font-family: 'Inter', 'Nunito', Arial, sans-serif;
    }
    .card {
      border: none;
      border-radius: 1.2rem;
      box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
      transition: box-shadow 0.3s, transform 0.3s;
      animation: cardPopIn 0.7s;
    }
    @keyframes cardPopIn {
      from { transform: scale(0.97); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
    .card-body {
      padding: 2rem 1.2rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .dashboard-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      margin-bottom: 0.7rem;
      font-size: 2.2rem;
      color: #fff;
      box-shadow: 0 2px 8px #0d6efd33;
    }
    .card-users   { background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%); }
    .card-cles    { background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%); }
    .card-logs    { background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%); }
    .card-stats   { background: linear-gradient(135deg, #0dcaf0 80%, #6f42c1 100%); }
    .card h5 {
      color: #fff;
      font-weight: 800;
      font-size: 1.1rem;
      margin-bottom: 0.7rem;
      letter-spacing: 0.5px;
    }
    .btn {
      border-radius: 0.7rem !important;
      font-weight: 700 !important;
      padding: 0.45em 1.2em !important;
      font-size: 1rem !important;
      box-shadow: 0 2px 8px #0d6efd33;
      transition: background 0.18s, color 0.18s;
      border: none !important;
    }
    .btn-primary {
      background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
      color: #fff !important;
    }
    .btn-primary:hover {
      background: linear-gradient(135deg, #6f42c1 80%, #0d6efd 100%) !important;
      color: #fff !important;
      transform: scale(1.07);
    }
  </style>
</head>
<body>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="mb-0"><i class="bi bi-speedometer"></i> Tableau de bord Super Admin</h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card h-100 card-users">
          <div class="card-body text-center">
            <span class="dashboard-icon" style="background: #0d6efd;"><i class="bi bi-people-fill"></i></span>
            <h5 class="mt-2">Gestion des utilisateurs</h5>
            <a href="utilisateurs.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 card-cles">
          <div class="card-body text-center">
            <span class="dashboard-icon" style="background: #198754;"><i class="bi bi-key-fill"></i></span>
            <h5 class="mt-2">Gestion des clés de chiffrement</h5>
            <a href="cles.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 card-logs">
          <div class="card-body text-center">
            <span class="dashboard-icon" style="background: #dc3545;"><i class="bi bi-journal-text"></i></span>
            <h5 class="mt-2">Logs</h5>
            <a href="logs.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 card-stats">
          <div class="card-body text-center">
            <span class="dashboard-icon" style="background: #0dcaf0;"><i class="bi bi-bar-chart"></i></span>
            <h5 class="mt-2">Statistiques</h5>
            <a href="stats.php" class="btn btn-primary mt-2">Accéder</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
