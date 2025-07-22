<?php
require_once 'auth.php';
secure_session();
$user = $_SESSION['user'];
require 'db.php';
include 'dashboard-template.php';
?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="card mt-3">
        <div class="card-body">
          <h3 class="mb-4"><i class="bi bi-house"></i> Tableau de bord</h3>
          <?php
          switch ($user['role']) {
            case 'superadmin':
              // Exemple de dashboard superadmin
              echo '<div class="alert alert-primary">Bienvenue Superadmin !<br>Vous pouvez gérer tous les utilisateurs, voir les statistiques globales et accéder à toutes les fonctionnalités.</div>';
              // Ajoute ici des widgets ou liens spécifiques superadmin
              break;
            case 'secretaire':
              echo '<div class="alert alert-info">Bienvenue Secrétaire !<br>Vous avez accès à la gestion des documents, des demandes et des notifications.</div>';
              // Widgets rapides
              echo '<ul class="list-group list-group-flush mb-3">';
              echo '<li class="list-group-item"><a href="archiver.php"><i class="bi bi-file-earmark-arrow-up"></i> Archiver des documents</a></li>';
              echo '<li class="list-group-item"><a href="demandes.php"><i class="bi bi-inbox"></i> Gérer les demandes</a></li>';
              echo '<li class="list-group-item"><a href="notifications.php"><i class="bi bi-bell"></i> Voir les notifications</a></li>';
              echo '</ul>';
              break;
            case 'employe':
              echo '<div class="alert alert-success">Bienvenue Employé !<br>Vous pouvez envoyer des documents, faire des demandes d\'autorisation et suivre vos accès.</div>';
              echo '<ul class="list-group list-group-flush mb-3">';
              echo '<li class="list-group-item"><a href="envoyer.php"><i class="bi bi-send"></i> Envoyer un document</a></li>';
              echo '<li class="list-group-item"><a href="autorisation.php"><i class="bi bi-shield-check"></i> Mes demandes d\'autorisation</a></li>';
              echo '</ul>';
              break;
            case 'ag':
              echo '<div class="alert alert-warning">Bienvenue AG !<br>Vous pouvez gérer les accès, voir les archives et autoriser des documents.</div>';
              echo '<ul class="list-group list-group-flush mb-3">';
              echo '<li class="list-group-item"><a href="liste-archives.php"><i class="bi bi-archive"></i> Voir les archives</a></li>';
              echo '<li class="list-group-item"><a href="autoriser-acces.php"><i class="bi bi-check2-square"></i> Autoriser des accès</a></li>';
              echo '</ul>';
              break;
            case 'associe':
              echo '<div class="alert alert-secondary">Bienvenue Associé !<br>Vous pouvez consulter les documents partagés et voir votre historique.</div>';
              echo '<ul class="list-group list-group-flush mb-3">';
              echo '<li class="list-group-item"><a href="liste-archives.php"><i class="bi bi-archive"></i> Voir les archives partagées</a></li>';
              echo '</ul>';
              break;
            default:
              echo '<div class="alert alert-dark">Bienvenue !</div>';
          }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>
