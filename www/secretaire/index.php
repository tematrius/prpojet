<?php
require_once '../includes/auth.php';
secure_session();
$user = $_SESSION['user'];
require '../includes/db.php';
include '../includes/dashboard-template.php';
?>
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">
      <div class="text-center my-4">
        <h2 class="fw-bold"><i class="bi bi-house-door-fill text-primary"></i> Tableau de bord Secrétaire</h2>
        <p class="lead text-secondary">Bienvenue Secrétaire ! Accédez rapidement à vos principales actions.</p>
      </div>
      <div class="row g-4">
        <div class="col-12 col-md-4">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
              <i class="bi bi-file-earmark-arrow-up display-4 text-primary mb-3"></i>
              <h5 class="card-title">Archiver des documents</h5>
              <p class="card-text text-muted">Ajoutez de nouveaux documents à la base d’archives.</p>
              <a href="archiver.php" class="btn btn-outline-primary w-100">Archiver</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
              <i class="bi bi-inbox display-4 text-success mb-3"></i>
              <h5 class="card-title">Gérer les demandes</h5>
              <p class="card-text text-muted">Consultez et traitez les demandes d’accès des utilisateurs.</p>
              <a href="demandes.php" class="btn btn-outline-success w-100">Voir les demandes</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
              <i class="bi bi-bell display-4 text-warning mb-3"></i>
              <h5 class="card-title">Notifications</h5>
              <p class="card-text text-muted">Soyez informé des nouvelles actions et alertes importantes.</p>
              <a href="notifications.php" class="btn btn-outline-warning w-100">Voir les notifications</a>
            </div>
          </div>
        </div>
      </div>
      <div class="text-center mt-5">
        <span class="text-muted small">BNB Archives &copy; 2025</span>
      </div>
    </div>
  </div>
</div>
