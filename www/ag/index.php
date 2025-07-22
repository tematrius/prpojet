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
          <h3 class="mb-4"><i class="bi bi-house"></i> Tableau de bord AG</h3>
          <div class="alert alert-warning">Bienvenue AG !<br>Vous pouvez gérer les accès, voir les archives et autoriser des documents.</div>
          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item"><a href="liste-archives.php"><i class="bi bi-archive"></i> Voir les archives</a></li>
            <li class="list-group-item"><a href="autoriser-acces.php"><i class="bi bi-check2-square"></i> Autoriser des accès</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>
