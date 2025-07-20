<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
include '../includes/dashboard-template.php';
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'superadmin') {
    header('Location: ../index.php');
    exit;
}
// Statistiques globales
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) FROM archives");
$stats['total_fichiers'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT provenance, COUNT(*) as total FROM archives GROUP BY provenance");
$stats['par_provenance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT COUNT(*) FROM cles");
$stats['total_cles'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM cles WHERE active = 1");
$stats['cles_actives'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT a.nom_fichier, COUNT(*) as nb FROM logs l JOIN archives a ON l.target_id = a.id WHERE l.action = 'telechargement' GROUP BY l.target_id ORDER BY nb DESC LIMIT 5");
$stats['top_telechargements'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT u.nom, COUNT(*) as nb FROM logs l JOIN utilisateurs u ON l.user_id = u.id WHERE l.action IN ('connexion','telechargement') GROUP BY l.user_id ORDER BY nb DESC LIMIT 5");
$stats['top_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'telechargement'");
$stats['total_telechargements'] = $stmt->fetchColumn();
$stmt = $pdo->query("SELECT COUNT(*) FROM logs WHERE action = 'consultation'");
$stats['total_consultations'] = $stmt->fetchColumn();
$actions = $pdo->query("SELECT action, DATE_FORMAT(timestamp, '%Y-%m') as mois, COUNT(*) as count FROM logs WHERE action IN ('ajout','suppression','modification') GROUP BY action, mois ORDER BY mois DESC")->fetchAll(PDO::FETCH_ASSOC);
$labels = [];
$ajouts = [];
$suppressions = [];
$modifications = [];
foreach ($actions as $row) {
    if (!in_array($row['mois'], $labels)) $labels[] = $row['mois'];
}
foreach ($labels as $mois) {
    $ajout = $supp = $modif = 0;
    foreach ($actions as $row) {
        if ($row['mois'] == $mois) {
            if ($row['action'] == 'ajout') $ajout = $row['count'];
            if ($row['action'] == 'suppression') $supp = $row['count'];
            if ($row['action'] == 'modification') $modif = $row['count'];
        }
    }
    $ajouts[] = $ajout;
    $suppressions[] = $supp;
    $modifications[] = $modif;
}
$roles = $pdo->query("SELECT role, COUNT(*) as count FROM utilisateurs GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
$admin_actions = $pdo->query("SELECT l.*, u.nom FROM logs l LEFT JOIN utilisateurs u ON l.user_id = u.id WHERE l.action IN ('ajout','suppression','modification') ORDER BY l.timestamp DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Super Admin - Statistiques</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    body {
      background: linear-gradient(120deg, #f7faff 0%, #e4ebf7 100%);
      font-family: 'Inter', 'Nunito', Arial, sans-serif;
      min-height: 100vh;
      margin: 0;
      padding-bottom: 40px;
      animation: fadeInBg 1.2s;
    }
    @keyframes fadeInBg {
      from { opacity: 0;}
      to { opacity: 1;}
    }
    .container {
      max-width: 950px;
      margin: 0 auto;
      padding: 1.2rem 0.5rem;
    }
    .dashboard-header {
      background: rgba(255,255,255,0.98);
      border-radius: 1.2rem;
      box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
      padding: 1.2rem 1.2rem 1rem 1.2rem;
      margin-bottom: 1.2rem;
      border: 1px solid #e3e6f3;
      position: relative;
      overflow: hidden;
      transition: box-shadow 0.3s;
      animation: slideFadeIn 1s;
    }
    @keyframes slideFadeIn {
      from { transform: translateY(-30px); opacity: 0;}
      to { transform: translateY(0); opacity: 1;}
    }
    .dashboard-header:before {
      content: "";
      position: absolute;
      top: -50px; right: -60px;
      width: 180px; height: 180px;
      background: radial-gradient(circle, #0d6efd33 35%, transparent 70%);
      z-index: 0;
    }
    .dashboard-header h1 {
      font-size: 1.5rem;
      font-weight: 800;
      color: #0d6efd;
      letter-spacing: 1px;
      margin-bottom: 0.3rem;
      position: relative;
      z-index: 2;
      text-shadow: 0 2px 8px #0d6efd11;
    }
    .dashboard-header p {
      font-size: 0.95rem;
      color: #444;
      position: relative;
      z-index: 2;
      margin-bottom: 0.7rem;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.2rem;
      position: relative;
      z-index: 2;
    }
    /* Ajout des backgrounds colorés et icônes blanches pour les cartes principales */
    .stat-card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 2px 8px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
      min-height: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      padding: 0.7rem 1rem;
      position: relative;
      overflow: hidden;
      transition: transform 0.22s, box-shadow 0.22s;
      animation: cardPopIn 0.5s;
    }
    .stat-card .stat-icon {
      color: #fff !important;
      background: rgba(255,255,255,0.18) !important;
    }
    .stat-card .stat-label {
      color: #fff !important;
    }
    .stat-card .stat-value {
      color: #fff !important;
      background: rgba(255,255,255,0.18) !important;
    }
    .stat-card .stat-badge {
      background: #fff !important;
      color: #198754 !important;
      border: none !important;
    }
    .btn {
      border-radius: 0.7rem !important;
      font-weight: 700 !important;
      padding: 0.45em 1.2em !important;
      font-size: 1rem !important;
      box-shadow: 0 2px 8px #0d6efd33;
      transition: background 0.18s, color 0.18s;
    }
    .btn-primary {
      background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%) !important;
      color: #fff !important;
      border: none !important;
    }
    .btn-success {
      background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%) !important;
      color: #fff !important;
      border: none !important;
    }
    .btn-danger {
      background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%) !important;
      color: #fff !important;
      border: none !important;
    }
    .btn-info {
      background: linear-gradient(135deg, #0dcaf0 80%, #6f42c1 100%) !important;
      color: #fff !important;
      border: none !important;
    }
    .dashboard-header {
      background: rgba(255,255,255,0.98);
      border-radius: 1.2rem;
      box-shadow: 0 4px 18px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
      padding: 1.2rem 1.2rem 1rem 1.2rem;
      margin-bottom: 1.2rem;
      border: 1px solid #e3e6f3;
      position: relative;
      overflow: hidden;
      transition: box-shadow 0.3s;
      animation: slideFadeIn 1s;
    }
    @keyframes slideFadeIn {
      from { transform: translateY(-30px); opacity: 0;}
      to { transform: translateY(0); opacity: 1;}
    }
    .dashboard-header:before {
      content: "";
      position: absolute;
      top: -50px; right: -60px;
      width: 180px; height: 180px;
      background: radial-gradient(circle, #0d6efd33 35%, transparent 70%);
      z-index: 0;
    }
    .dashboard-header h1 {
      font-size: 1.5rem;
      font-weight: 800;
      color: #0d6efd;
      letter-spacing: 1px;
      margin-bottom: 0.3rem;
      position: relative;
      z-index: 2;
      text-shadow: 0 2px 8px #0d6efd11;
    }
    .dashboard-header p {
      font-size: 0.95rem;
      color: #444;
      position: relative;
      z-index: 2;
      margin-bottom: 0.7rem;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.2rem;
      position: relative;
      z-index: 2;
    }
    .stat-card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 2px 8px rgba(13,110,253,0.10), 0 1px 4px rgba(0,0,0,0.04);
      background: linear-gradient(120deg, #fff 60%, #f8fafc 100%);
      min-height: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      padding: 0.7rem 1rem;
      position: relative;
      overflow: hidden;
      transition: transform 0.22s, box-shadow 0.22s;
      animation: cardPopIn 0.5s;
    }
    @keyframes cardPopIn {
      from { transform: scale(0.98); opacity: 0;}
      to { transform: scale(1); opacity: 1;}
    }
    .stat-card:hover {
      transform: scale(1.045);
      box-shadow: 0 12px 32px rgba(13,110,253,0.18);
    }
    .stat-icon {
      font-size: 1.3rem;
      margin-bottom: 0.1rem;
      color: #fff;
      background: linear-gradient(135deg, #0d6efd 60%, #6f42c1 100%);
      border-radius: 0.5rem;
      padding: 0.3rem 0.5rem;
      box-shadow: 0 2px 8px #0d6efd33;
      display: inline-block;
      animation: iconBounce 1.4s infinite alternate;
    }
    @keyframes iconBounce {
      from { transform: translateY(0);}
      to { transform: translateY(-4px);}
    }
    .stat-value {
      font-size: 1.2rem;
      font-weight: 800;
      color: #222;
      background: #fff;
      border-radius: 0.5rem;
      box-shadow: 0 2px 8px #e3e6f3;
      padding: 0.15rem 0.7rem;
      margin-bottom: 0.1rem;
      display: inline-block;
      letter-spacing: 0.7px;
      transition: background 0.2s;
      animation: fadeInValue 1.2s;
    }
    @keyframes fadeInValue {
      from { opacity: 0;}
      to { opacity: 1;}
    }
    .stat-label {
      font-size: 0.95rem;
      color: #6f42c1;
      font-weight: 700;
      margin-bottom: 0.1rem;
      letter-spacing: 0.3px;
    }
    .stat-badge {
      font-size: 0.85rem;
      font-weight: 700;
      padding: 0.2em 0.5em;
      border-radius: 0.6em;
      margin-left: 0.4em;
      background: #f8fafc;
      color: #0d6efd;
      border: 1px solid #e3e6f3;
      box-shadow: 0 1px 4px #0d6efd13;
      animation: fadeInValue 1.2s;
    }
    .section-title {
      font-size: 1rem;
      font-weight: 800;
      color: #198754;
      margin-bottom: 0.7rem;
      letter-spacing: 0.4px;
      display: flex;
      align-items: center;
      gap: 0.3em;
    }
    .table thead th {
      background: #f8fafc;
      color: #198754;
      font-weight: 800;
      font-size: 0.95rem;
      letter-spacing: 0.2px;
    }
    .admin-avatar {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: #e3e6f3;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #0d6efd;
      margin-right: 6px;
      font-size: 0.95rem;
      border: 1px solid #d1d5db;
      box-shadow: 0 2px 8px #6f42c133;
      transition: background 0.2s;
    }
    .list-group-item {
      background: #f8fafc;
      border: none;
      font-size: 0.95rem;
      color: #222;
    }
    .badge {
      font-size: 0.85rem;
      font-weight: 700;
      padding: 0.3em 0.6em;
      border-radius: 0.6em;
      letter-spacing: 0.2px;
    }
    .badge.bg-success {
      background: #198754 !important;
    }
    .badge.bg-primary {
      background: #0d6efd !important;
    }
    .badge.bg-info {
      background: #0dcaf0 !important; color: #fff;
    }
    .badge.bg-danger {
      background: #dc3545 !important;
    }
    .badge.bg-warning {
      background: #ffc107 !important; color: #222;
    }
    tbody tr {
      animation: rowFadeIn 1s;
    }
    @keyframes rowFadeIn {
      from { opacity: 0; transform: translateY(12px);}
      to { opacity: 1; transform: translateY(0);}
    }
    /* Agrandissement des cards internes dans les deux colonnes */
    .big-list {
      display: flex;
      flex-direction: column;
      gap: 0.7rem;
    }
    .big-card {
      background: #fff;
      min-height: 40px;
      padding: 0.7rem 1rem !important;
      border-radius: 0.7rem;
      box-shadow: 0 2px 8px #e3e6f3;
      display: flex !important;
      align-items: center;
      gap: 0.7rem;
      margin-bottom: 0;
      font-size: 0.95rem;
      transition: box-shadow 0.18s, transform 0.18s;
    }
    .big-card .stat-icon {
      font-size: 1.3rem !important;
      margin-right: 0.7rem;
    }
    .big-card .stat-label {
      font-size: 0.95rem !important;
      margin-right: 0.7rem;
      font-weight: 700 !important;
    }
    .big-card .stat-value {
      font-size: 0.95rem !important;
      margin-bottom: 0 !important;
    }
    @media (max-width: 900px) {
      .dashboard-header { padding: 1.3rem; margin-bottom: 1.3rem; }
      .stats-grid { gap: 1rem; }
      .stat-card { padding: 1.2rem; min-height: 86px; }
      .big-card { padding: 1rem 0.7rem !important; font-size: 1rem; }
    }
    
  </style>
</head>
<body>
  <div class="container">
    <div class="dashboard-header">
      <h1><i class="bi bi-bar-chart"></i> Statistiques Super Admin</h1>
      <p>Bienvenue sur le dashboard analytics. Visualisez l’activité, les utilisateurs et les actions clés en temps réel.</p>
      <div class="stats-grid">
        <div class="stat-card" data-glossy="true" style="background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%); color: #fff;">
          <span class="stat-icon" style="background: rgba(255,255,255,0.18); color: #fff;"><i class="bi bi-file-earmark-bar-graph"></i></span>
          <span class="stat-label" style="color: #fff;">Fichiers</span>
          <span class="stat-value" style="background: rgba(255,255,255,0.18); color: #fff;"><?=$stats['total_fichiers']?></span>
        </div>
        <div class="stat-card" data-glossy="true" style="background: linear-gradient(135deg, #198754 80%, #0dcaf0 100%); color: #fff;">
          <span class="stat-icon" style="background: rgba(255,255,255,0.18); color: #fff;"><i class="bi bi-key"></i></span>
          <span class="stat-label" style="color: #fff;">Clés</span>
          <span class="stat-value" style="background: rgba(255,255,255,0.18); color: #fff;"><?=$stats['total_cles']?></span>
          <span class="stat-badge" style="background: #fff; color: #198754; border: none;">Actives: <?=$stats['cles_actives']?></span>
        </div>
        <div class="stat-card" data-glossy="true" style="background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%); color: #fff;">
          <span class="stat-icon" style="background: rgba(255,255,255,0.18); color: #fff;"><i class="bi bi-cloud-arrow-down"></i></span>
          <span class="stat-label" style="color: #fff;">Téléchargements</span>
          <span class="stat-value" style="background: rgba(255,255,255,0.18); color: #fff;"><?=$stats['total_telechargements']?></span>
        </div>
        <div class="stat-card" data-glossy="true" style="background: linear-gradient(135deg, #0dcaf0 80%, #6f42c1 100%); color: #fff;">
          <span class="stat-icon" style="background: rgba(255,255,255,0.18); color: #fff;"><i class="bi bi-eye"></i></span>
          <span class="stat-label" style="color: #fff;">Consultations</span>
          <span class="stat-value" style="background: rgba(255,255,255,0.18); color: #fff;"><?=$stats['total_consultations']?></span>
        </div>
      </div>
    </div>
    <!-- SECTION MODIFIÉE : Fichiers par provenance ET top téléchargements côte à côte et agrandis -->
<div class="row g-4 mb-4">
  <!-- Fichiers par provenance (agrandi et coloré) -->
  <div class="col-md-6">
    <div class="card stat-card" data-glossy="true" style="min-height: 350px; background: linear-gradient(135deg, #0d6efd 80%, #6f42c1 100%); color: #fff;">
      <div class="card-body">
        <div class="section-title" style="color: #fff;"><i class="bi bi-geo-alt"></i> Fichiers par provenance</div>
        <div class="d-flex flex-column gap-3">
          <?php foreach($stats['par_provenance'] as $prov): ?>
            <?php
              $icon = 'bi-geo-alt';
              $color = '#0d6efd';
              $bg = 'rgba(255,255,255,0.18)';
              if (stripos($prov['provenance'], 'gérant') !== false) { $icon = 'bi-person-badge'; $color = '#198754'; $bg = 'rgba(25,135,84,0.18)'; }
              elseif (stripos($prov['provenance'], 'associé') !== false) { $icon = 'bi-people-fill'; $color = '#6f42c1'; $bg = 'rgba(111,66,193,0.18)'; }
              elseif (stripos($prov['provenance'], 'employé') !== false) { $icon = 'bi-person-workspace'; $color = '#0dcaf0'; $bg = 'rgba(13,202,240,0.18)'; }
              elseif (stripos($prov['provenance'], 'secrétaire') !== false) { $icon = 'bi-person-lines-fill'; $color = '#ffc107'; $bg = 'rgba(255,193,7,0.18)'; }
            ?>
            <div class="stat-card" data-glossy="true" style="background:<?=$bg?>; min-height:72px; padding:1.2rem 1.4rem; flex-direction:row; align-items:center; gap:1.4rem; border-radius:0.7rem;">
              <span class="stat-icon" style="background:<?=$color?>; color:#fff; font-size:2.3rem; margin-bottom:0; margin-right:1.2rem;">
                <i class="bi <?=$icon?>"></i>
              </span>
              <span class="stat-label" style="color:#fff; font-size:1.4rem; margin-bottom:0; margin-right:1.2rem; font-weight:700;">
                <?=htmlspecialchars($prov['provenance'])?>
              </span>
              <span class="stat-value" style="font-size:1.4rem; color:#fff; background:<?=$color?>; margin-bottom:0; border-radius:0.5rem; padding:0.2rem 1rem;">
                <?=$prov['total']?>
              </span>
              
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Top fichiers téléchargés (agrandi et coloré) -->
  <div class="col-md-6">
    <div class="card stat-card" data-glossy="true" style="min-height: 350px; background: linear-gradient(135deg, #dc3545 80%, #ffc107 100%); color: #fff;">
      <div class="card-body">
        <div class="section-title" style="color: #fff;"><i class="bi bi-star"></i> Top fichiers téléchargés</div>
        <div class="d-flex flex-column gap-2 mb-2">
          <?php foreach($stats['top_telechargements'] as $file): ?>
            <div class="stat-card" data-glossy="true" style="background:rgba(255,255,255,0.18); min-height:38px; padding:0.5rem 0.7rem; flex-direction:row; align-items:center; gap:0.7rem; border-radius:0.7rem;">
              <span class="stat-icon" style="background:#dc3545; color:#fff; font-size:1.1rem; margin-bottom:0; margin-right:0.5rem;">
                <i class="bi bi-file-earmark-arrow-down"></i>
              </span>
              <span class="stat-label" style="color:#fff; font-size:0.95rem; margin-bottom:0; margin-right:0.5rem; font-weight:700;">
                <?=htmlspecialchars($file['nom_fichier'])?>
              </span>
              <span class="stat-value" style="font-size:0.95rem; color:#fff; background:#dc3545; margin-bottom:0; border-radius:0.5rem; padding:0.2rem 1rem;">
                <?=$file['nb']?>
              </span>
              
            </div>
          <?php endforeach; ?>
        </div>
        <div class="section-title" style="color: #fff;"><i class="bi bi-person-badge"></i> Top utilisateurs actifs</div>
        <div class="d-flex flex-column gap-2">
          <?php foreach($stats['top_users'] as $user): ?>
            <div class="stat-card" data-glossy="true" style="background:rgba(255,255,255,0.18); min-height:38px; padding:0.5rem 0.7rem; flex-direction:row; align-items:center; gap:0.7rem; border-radius:0.7rem;">
              <span class="admin-avatar" style="background:#0dcaf0; color:#fff; font-size:0.95rem; margin-right:0.5rem;">
                <?php echo strtoupper(mb_substr($user['nom'],0,1)); ?>
              </span>
              <span class="stat-label" style="color:#fff; font-size:0.95rem; margin-bottom:0; margin-right:0.5rem; font-weight:700;">
                <?=htmlspecialchars($user['nom'])?>
              </span>
              <span class="stat-value" style="font-size:0.95rem; color:#fff; background:#0dcaf0; margin-bottom:0; border-radius:0.5rem; padding:0.2rem 1rem;">
                <?=$user['nb']?>
              </span>
              <button class="btn btn-info ms-auto" style="border-radius:0.7rem; font-weight:700;">Profil</button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
    <!-- FIN SECTION MODIFIÉE -->
    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="card stat-card" data-glossy="true" style="min-height: 350px; height: 100%;">
          <div class="card-body d-flex flex-column align-items-center justify-content-center" style="height: 100%; width: 100%; padding: 0.7rem 1rem;">
            <div class="section-title" style="margin-bottom: 0.7rem;"><i class="bi bi-graph-up"></i> Évolution des actions utilisateurs (par mois)</div>
            <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center;">
              <canvas id="actionsChart" height="260" style="width:100% !important; height:260px !important; max-width:100%;"></canvas>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card stat-card" data-glossy="true" style="min-height: 350px; height: 100%;">
          <div class="card-body d-flex flex-column align-items-center justify-content-center" style="height: 100%; width: 100%; padding: 0.7rem 1rem;">
            <div class="section-title" style="margin-bottom: 0.7rem;"><i class="bi bi-pie-chart"></i> Répartition des rôles</div>
            <div style="width:100%; height:260px; display:flex; align-items:center; justify-content:center;">
              <canvas id="rolesChart" height="260" style="width:100% !important; height:260px !important; max-width:100%;"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="row g-4 mb-4">
      <div class="col-md-12">
        <div class="card stat-card" data-glossy="true">
          <div class="card-body">
            <div class="section-title"><i class="bi bi-person-lines-fill"></i> Dernières actions administratives</div>
            <div class="table-responsive">
              <table class="table table-striped align-middle">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Action</th>
                    <th>Utilisateur</th>
                    <th>Détails</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($admin_actions as $action): ?>
                  <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($action['date'])); ?></td>
                    <td><span class="badge bg-<?php echo ($action['action']=='ajout'?'success':($action['action']=='suppression'?'danger':'primary')); ?>"><?php echo ucfirst($action['action']); ?></span></td>
                    <td>
                      <span class="admin-avatar"><?php echo strtoupper(mb_substr($action['nom'],0,1).mb_substr($action['prenom'],0,1)); ?></span>
                      <?php echo htmlspecialchars($action['nom'] . ' ' . $action['prenom']); ?>
                    </td>
                    <td><?php echo htmlspecialchars($action['details']); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <footer>
      <span> </span>
    </footer>
  </div>
  <script>
    // Graphique évolution des actions
    const ctx = document.getElementById('actionsChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [
          {
            label: 'Ajouts',
            data: <?php echo json_encode($ajouts); ?>,
            borderColor: '#198754',
            backgroundColor: 'rgba(25,135,84,0.15)',
            tension: 0.3,
            pointRadius: 4,
            fill: true
          },
          {
            label: 'Suppressions',
            data: <?php echo json_encode($suppressions); ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220,53,69,0.15)',
            tension: 0.3,
            pointRadius: 4,
            fill: true
          },
          {
            label: 'Modifications',
            data: <?php echo json_encode($modifications); ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.15)',
            tension: 0.3,
            pointRadius: 4,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: false }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
    // Graphique répartition des rôles
    const ctxRoles = document.getElementById('rolesChart').getContext('2d');
    new Chart(ctxRoles, {
      type: 'pie',
      data: {
        labels: <?php echo json_encode(array_map(function($r){return ucfirst($r['role']);}, $roles)); ?>,
        datasets: [{
          data: <?php echo json_encode(array_map(function($r){return $r['count'];}, $roles)); ?>,
          backgroundColor: ['#0d6efd','#198754','#dc3545','#ffc107']
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: false }
        }
      }
    });
  </script>
</body>
</html>