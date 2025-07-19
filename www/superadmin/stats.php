<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}
include '../includes/dashboard-template.php';

// Statistiques globales
$stats = [];
// Total fichiers
$stmt = $pdo->query("SELECT COUNT(*) FROM archives");
$stats['total_fichiers'] = $stmt->fetchColumn();
// Total par provenance
$stmt = $pdo->query("SELECT provenance, COUNT(*) as total FROM archives GROUP BY provenance");
$stats['par_provenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Total clés
$stmt = $pdo->query("SELECT COUNT(*) FROM cles");
$stats['total_cles'] = $stmt->fetchColumn();
// Clés actives
$stmt = $pdo->query("SELECT COUNT(*) FROM cles WHERE active = 1");
$stats['cles_actives'] = $stmt->fetchColumn();
// Top fichiers téléchargés
$stmt = $pdo->query("SELECT a.nom_fichier, COUNT(*) as nb FROM logs l JOIN archives a ON l.id_document = a.id WHERE l.action = 'telechargement' GROUP BY l.id_document ORDER BY nb DESC LIMIT 5");
$stats['top_telechargements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Top utilisateurs actifs
$stmt = $pdo->query("SELECT u.nom, COUNT(*) as nb FROM logs l JOIN utilisateurs u ON l.id_user = u.id WHERE l.action IN ('connexion','telechargement') GROUP BY l.id_user ORDER BY nb DESC LIMIT 5");
$stats['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Total téléchargements
$stmt = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'telechargement'");
$stats['total_telechargements'] = $stmt->fetchColumn();
// Total consultations
$stmt = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'consultation'");
$stats['total_consultations'] = $stmt->fetchColumn();

// Logs pertinents
$stmt = $pdo->query("SELECT l.*, u.nom as user_nom FROM logs l LEFT JOIN utilisateurs u ON l.id_user = u.id ORDER BY l.date DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h3 class="mb-4"><i class="bi bi-bar-chart"></i> Statistiques & Logs</h3>
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card text-bg-light mb-3">
        <div class="card-body">
          <h5 class="card-title">Total fichiers</h5>
          <p class="card-text fs-2"><?=$stats['total_fichiers']?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light mb-3">
        <div class="card-body">
          <h5 class="card-title">Total clés</h5>
          <p class="card-text fs-2"><?=$stats['total_cles']?> <span class="badge bg-success">Actives: <?=$stats['cles_actives']?></span></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light mb-3">
        <div class="card-body">
          <h5 class="card-title">Téléchargements</h5>
          <p class="card-text fs-2"><?=$stats['total_telechargements']?></p>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-light mb-3">
        <div class="card-body">
          <h5 class="card-title">Consultations</h5>
          <p class="card-text fs-2"><?=$stats['total_consultations']?></p>
        </div>
      </div>
    </div>
  </div>
  <div class="row mb-4">
    <div class="col-md-6">
      <h5>Fichiers par provenance</h5>
      <ul class="list-group">
        <?php foreach($stats['par_provenance'] as $prov): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?=htmlspecialchars($prov['provenance'])?>
            <span class="badge bg-primary rounded-pill"><?=$prov['total']?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="col-md-6">
      <h5>Top fichiers téléchargés</h5>
      <ul class="list-group">
        <?php foreach($stats['top_telechargements'] as $file): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?=htmlspecialchars($file['nom_fichier'])?>
            <span class="badge bg-success rounded-pill"><?=$file['nb']?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <h5 class="mt-4">Top utilisateurs actifs</h5>
      <ul class="list-group">
        <?php foreach($stats['top_users'] as $user): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <?=htmlspecialchars($user['nom'])?>
            <span class="badge bg-info rounded-pill"><?=$user['nb']?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <h4 class="mt-5"><i class="bi bi-journal-text"></i> Logs récents</h4>
  <!-- Les logs sont désormais affichés dans logs.php -->
</div>
