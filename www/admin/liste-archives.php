<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

// Récupération des stats globales
$total = $pdo->query("SELECT COUNT(*) FROM archives")->fetchColumn();
$restreints = $pdo->query("SELECT COUNT(*) FROM archives WHERE est_restreint = 1")->fetchColumn();
$publics = $pdo->query("SELECT COUNT(*) FROM archives WHERE est_restreint = 0")->fetchColumn();
$provenances = $pdo->query("SELECT provenance, COUNT(*) as total FROM archives GROUP BY provenance")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des fichiers archivés (avec filtre possible)
$filtre_provenance = $_GET['provenance'] ?? '';
$filtre_date = $_GET['date'] ?? '';

$where = [];
$params = [];

if ($filtre_provenance) {
    $where[] = "LOWER(provenance) = LOWER(?)";
    $params[] = $filtre_provenance;
}

if ($filtre_date) {
    $where[] = "DATE(date_upload) = ?";
    $params[] = $filtre_date;
}

$sql = "SELECT * FROM archives";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY date_upload DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.stats {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}
.stat-card {
  flex: 1 1 220px;
  padding: 20px;
  border-radius: 12px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 15px;
}
.stat-card i {
  font-size: 2rem;
}
.stat-content h5 {
  margin: 0;
  font-size: 1.4rem;
}
.stat-content small {
  font-size: 0.9rem;
}
.provenance-boxes {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}
.provenance-item {
  flex: 1 1 180px;
  padding: 15px;
  border-radius: 10px;
  background-color: #ffffff;
  text-align: center;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s;
}
.provenance-item:hover {
  transform: translateY(-4px);
}
.provenance-item .icon {
  font-size: 2rem;
  color: #0d6efd;
  margin-bottom: 8px;
}
.provenance-item h6 {
  font-size: 1.2rem;
  margin-bottom: 4px;
}
.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}
.table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
}
</style>

<div class="container mt-4">
  <h3><i class="bi bi-archive"></i> Tous les fichiers archivés</h3>

  <div class="stats">
    <div class="stat-card bg-primary">
      <i class="bi bi-folder2-open"></i>
      <div class="stat-content">
        <h5><?= $total ?></h5>
        <small>Total de fichiers</small>
      </div>
    </div>
    <div class="stat-card bg-success">
      <i class="bi bi-unlock"></i>
      <div class="stat-content">
        <h5><?= $publics ?></h5>
        <small>Fichiers publics</small>
      </div>
    </div>
    <div class="stat-card bg-warning text-dark">
      <i class="bi bi-lock"></i>
      <div class="stat-content">
        <h5><?= $restreints ?></h5>
        <small>Fichiers restreints</small>
      </div>
    </div>
  </div>

  <div class="card p-3 mb-4 bg-light">
    <h5 class="mb-3">Répartition par provenance</h5>
    <div class="provenance-boxes">
      <?php foreach ($provenances as $prov): ?>
        <div class="provenance-item">
          <div class="icon">
            <?php
              $icon = 'bi-building';
              if (stripos($prov['provenance'], 'secretaire') !== false || stripos($prov['provenance'], 'rh') !== false) {
                $icon = 'bi-person-vcard';
              } elseif (stripos($prov['provenance'], 'ag') !== false) {
                $icon = 'bi-person-badge';
              }
            ?>
            <i class="bi <?= $icon ?>"></i>
          </div>
          <h6><?= htmlspecialchars($prov['total']) ?></h6>
          <div><?= htmlspecialchars($prov['provenance']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <form method="GET" class="filters">
    <select name="provenance" class="form-select">
      <option value="">-- Provenance --</option>
      <option value="AG" <?= $filtre_provenance == 'AG' ? 'selected' : '' ?>>AG</option>
      <option value="secretaire" <?= $filtre_provenance == 'secretaire' ? 'selected' : '' ?>>Secrétaire</option>
    </select>
    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filtre_date) ?>">
    <button class="btn btn-primary">Filtrer</button>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Nom</th>
          <th>Provenance</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fichiers as $f): ?>
        <tr>
          <td><?= htmlspecialchars($f['nom_fichier']) ?></td>
          <td><?= htmlspecialchars($f['provenance']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($f['date_upload'])) ?></td>
          <td>
            <?php if ($f['est_restreint']): ?>
              <span class="badge bg-warning text-dark">Restreint</span>
            <?php else: ?>
              <span class="badge bg-success">Public</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="voir-document.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
            <a href="../<?= $f['chemin'] ?>" download class="btn btn-sm btn-outline-secondary">Télécharger</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
