<?php
require_once '../includes/auth.php';
secure_session();
$user = $_SESSION['user'];
require '../includes/db.php';
include '../includes/dashboard-template.php';
?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card mt-3">
        <div class="card-body">
          <h3 class="mb-4"><i class="bi bi-house"></i> Tableau de bord Superadmin</h3>
          <div class="alert alert-primary">Bienvenue Superadmin !<br>Vous pouvez gérer tous les utilisateurs, voir les statistiques globales et accéder à toutes les fonctionnalités.</div>
          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><a href="utilisateurs.php"><i class="bi bi-people"></i> Gérer les utilisateurs</a></li>
            <li class="list-group-item"><a href="stats.php"><i class="bi bi-bar-chart"></i> Statistiques globales</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
