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
        <h2 class="fw-bold"><i class="bi bi-house-door-fill text-primary"></i> Tableau de bord Employé</h2>
        <p class="lead text-secondary">Bienvenue Employé ! Accédez rapidement à vos principales actions.</p>
      </div>
      <div class="row g-4">
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
              <i class="bi bi-send display-4 text-primary mb-3"></i>
              <h5 class="card-title">Envoyer un document</h5>
              <p class="card-text text-muted">Transmettez facilement vos fichiers à l’administration.</p>
              <a href="envoyer.php" class="btn btn-outline-primary w-100">Envoyer</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-body text-center">
              <i class="bi bi-shield-check display-4 text-success mb-3"></i>
              <h5 class="card-title">Mes demandes d'autorisation</h5>
              <p class="card-text text-muted">Consultez et suivez vos demandes d’accès aux documents.</p>
              <a href="autorisation.php" class="btn btn-outline-success w-100">Voir mes demandes</a>
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
