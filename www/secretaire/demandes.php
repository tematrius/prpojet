<style>
@media (max-width: 600px) {
  .table-responsive table thead { display: none; }
  .table-responsive table, .table-responsive tbody, .table-responsive tr, .table-responsive td {
    display: block;
    width: 100%;
  }
  .table-responsive tr {
    margin-bottom: 1.2rem;
    border: 1px solid #dee2e6;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    background: #fff;
    padding: 10px 8px;
  }
  .table-responsive td {
    padding: 6px 8px;
    border: none !important;
    font-size: 0.97em;
    position: relative;
  }
  .table-responsive td:before {
    content: attr(data-label);
    font-weight: 600;
    color: #0d6efd;
    display: block;
    margin-bottom: 2px;
    font-size: 0.93em;
  }
  .table-responsive .btn, .table-responsive .badge {
    width: 100%;
    margin-bottom: 4px;
    font-size: 1em;
  }
}
</style>
<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$secretaire_id = $_SESSION['user']['id'];

// Récupérer les filtres
$statut = $_GET['statut'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$whereClauses = ["u.role = 'secretaire'", "d.id_demandeur = ?"];
$params = [$secretaire_id];

if ($statut) {
    $whereClauses[] = "d.statut = ?";
    $params[] = $statut;
}
if ($date_debut) {
    $whereClauses[] = "DATE(d.date_post) >= ?";
    $params[] = $date_debut;
}
if ($date_fin) {
    $whereClauses[] = "DATE(d.date_post) <= ?";
    $params[] = $date_fin;
}

$sql1 = "
    SELECT d.*, a.nom_fichier, a.chemin, a.provenance, u.nom AS demandeur_nom
    FROM demandes d
    JOIN archives a ON d.id_document = a.id
    JOIN utilisateurs u ON d.id_demandeur = u.id
    WHERE " . implode(' AND ', $whereClauses) . "
    ORDER BY d.date_post DESC
";
$stmt1 = $pdo->prepare($sql1);
$stmt1->execute($params);
$demandes_secretaire = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// 🔽 Ajouter des filtres pour demandes_employes (dernière section)
$filters = ["u.role = 'employe'", "d.soumis_ag = 1"];
$filterParams = [];
if ($statut) {
  $filters[] = "d.statut = ?";
  $filterParams[] = $statut;
}
if ($date_debut) {
  $filters[] = "DATE(d.date_post) >= ?";
  $filterParams[] = $date_debut;
}
if ($date_fin) {
  $filters[] = "DATE(d.date_post) <= ?";
  $filterParams[] = $date_fin;
}

$sql2 = "
    SELECT d.*, a.nom_fichier, a.chemin, a.provenance, u.nom AS demandeur_nom
    FROM demandes d
    JOIN archives a ON d.id_document = a.id
    JOIN utilisateurs u ON d.id_demandeur = u.id
    WHERE " . implode(' AND ', $filters) . "
    ORDER BY d.date_post DESC
";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute($filterParams);
$demandes_employes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Demandes employes non soumises
$stmt3 = $pdo->query("SELECT d.*, a.nom_fichier, a.chemin, a.provenance, u.nom AS demandeur_nom FROM demandes d JOIN archives a ON d.id_document = a.id JOIN utilisateurs u ON d.id_demandeur = u.id WHERE u.role = 'employe' AND d.soumis_ag = 0 ORDER BY d.date_post DESC");
$demandes_a_soumettre = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h3><i class="bi bi-inbox"></i> Demandes en cours</h3>

  <form method="GET" class="row g-3 mb-4 align-items-end">
    <div class="col-md-3">
      <label for="statut" class="form-label">Statut</label>
      <select id="statut" name="statut" class="form-select" aria-label="Filtrer par statut">
        <option value="">-- Statut --</option>
        <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
        <option value="accepte" <?= $statut === 'accepte' ? 'selected' : '' ?>>Acceptée</option>
        <option value="refuse" <?= $statut === 'refuse' ? 'selected' : '' ?>>Refusée</option>
      </select>
    </div>
    <div class="col-md-3">
      <label for="date_debut" class="form-label">Date début</label>
      <input id="date_debut" type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>" placeholder="Date début">
    </div>
    <div class="col-md-3">
      <label for="date_fin" class="form-label">Date fin</label>
      <input id="date_fin" type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>" placeholder="Date fin">
    </div>
    <div class="col-md-3 d-flex gap-2">
      <button class="btn btn-primary flex-fill" type="submit">Filtrer</button>
      <a href="demandes.php" class="btn btn-outline-secondary flex-fill" role="button">Réinitialiser</a>
    </div>
  </form>

  <!-- Vos propres demandes -->
  <h5 class="text-primary d-flex align-items-center"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Vos propres demandes <span class="badge bg-light text-dark ms-2"><?= count($demandes_secretaire) ?> résultat<?= count($demandes_secretaire) > 1 ? 's' : '' ?></span></h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered align-middle" id="tableDemandesSecretaire">
      <thead class="table-light">
        <tr>
          <th>Fichier</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Motif</th>
        </tr>
      </thead>
      <tbody id="bodyDemandesSecretaire">
        <!-- JS pagination -->
      </tbody>
    </table>
    <div id="paginationDemandesSecretaire" class="d-flex flex-wrap gap-1 justify-content-center"></div>
  </div>
<script>
const demandesSecretaire = <?php echo json_encode($demandes_secretaire); ?>;
const rowsPerPageSec = 10;
let currentPageSec = 1;
function renderDemandesSecretaire(page=1) {
  const tbody = document.getElementById('bodyDemandesSecretaire');
  tbody.innerHTML = '';
  if (!demandesSecretaire.length) {
    tbody.innerHTML = `<tr><td colspan='4' class='text-center text-danger fw-bold'>Aucune demande.</td></tr>`;
    document.getElementById('paginationDemandesSecretaire').innerHTML = '';
    return;
  }
  const start = (page-1)*rowsPerPageSec;
  const end = start+rowsPerPageSec;
  const pageData = demandesSecretaire.slice(start, end);
  pageData.forEach(dem => {
    let statutHtml = '';
    if(dem.statut === 'en_attente') statutHtml = `<span class='badge bg-secondary'>En attente</span>`;
    else if(dem.statut === 'accepte') statutHtml = `<span class='badge bg-success'>Acceptée</span>`;
    else statutHtml = `<span class='badge bg-danger'>Refusée</span>`;
    tbody.innerHTML += `
      <tr>
        <td data-label='Fichier'>${dem.nom_fichier}</td>
        <td data-label='Date'>${new Date(dem.date_post).toLocaleString('fr-FR')}</td>
        <td data-label='Statut'>${statutHtml}</td>
        <td data-label='Motif'>${dem.motif_refus ? dem.motif_refus : '-'}</td>
      </tr>`;
  });
  // Pagination
  const totalPages = Math.ceil(demandesSecretaire.length/rowsPerPageSec);
  let pagHtml = '';
  if(totalPages > 1) {
    for(let p=1;p<=totalPages;p++) {
      pagHtml += `<button class='btn btn-sm ${p===page?'btn-primary':'btn-outline-primary'}' onclick='gotoPageSec(${p})'>${p}</button>`;
    }
  }
  document.getElementById('paginationDemandesSecretaire').innerHTML = pagHtml;
}
function gotoPageSec(p) {
  currentPageSec = p;
  renderDemandesSecretaire(currentPageSec);
}
window.addEventListener('DOMContentLoaded', ()=>{
  renderDemandesSecretaire(currentPageSec);
});
</script>

  <!-- Demandes à soumettre -->
  <h5 class="text-primary d-flex align-items-center"><i class="bi bi-hourglass-split me-2 text-warning"></i> Demandes des employés à soumettre <span class="badge bg-light text-dark ms-2"><?= count($demandes_a_soumettre) ?> résultat<?= count($demandes_a_soumettre) > 1 ? 's' : '' ?></span></h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Employé</th>
          <th>Fichier</th>
          <th>Commentaire</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($demandes_a_soumettre) === 0): ?>
          <tr><td colspan="5" class="text-center text-danger fw-bold">Aucune demande à soumettre.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes_a_soumettre as $dem): ?>
            <tr>
              <td data-label="Employé"><?= htmlspecialchars($dem['demandeur_nom']) ?></td>
              <td data-label="Fichier"><?= htmlspecialchars($dem['nom_fichier']) ?></td>
              <td data-label="Commentaire"><?= nl2br(htmlspecialchars($dem['commentaire'])) ?></td>
              <td data-label="Date"><?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></td>
              <td data-label="Action">
                <form method="POST" action="soumettre_ag.php">
                  <input type="hidden" name="id_demande" value="<?= $dem['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-success" aria-label="Soumettre à l'AG">Soumettre à l'AG</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Demandes déjà soumises -->
  <h5 class="text-primary d-flex align-items-center"><i class="bi bi-send-check-fill me-2 text-success"></i> Demandes des employés déjà transmises à l'AG <span class="badge bg-light text-dark ms-2"><?= count($demandes_employes) ?> résultat<?= count($demandes_employes) > 1 ? 's' : '' ?></span></h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Employé</th>
          <th>Fichier</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Motif</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($demandes_employes) === 0): ?>
          <tr><td colspan="5" class="text-center text-danger fw-bold">Aucune demande soumise.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes_employes as $dem): ?>
            <tr>
              <td data-label="Employé"><?= htmlspecialchars($dem['demandeur_nom']) ?></td>
              <td data-label="Fichier"><?= htmlspecialchars($dem['nom_fichier']) ?></td>
              <td data-label="Date"><?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></td>
              <td data-label="Statut">
                <?php if ($dem['statut'] === 'en_attente'): ?>
                  <span class="badge bg-secondary">En attente</span>
                <?php elseif ($dem['statut'] === 'accepte'): ?>
                  <span class="badge bg-success">Acceptée</span>
                <?php else: ?>
                  <span class="badge bg-danger">Refusée</span>
                <?php endif; ?>
              </td>
              <td data-label="Motif"><?= $dem['motif_refus'] ?? '-' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
