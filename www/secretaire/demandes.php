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

  <form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
      <select name="statut" class="form-select">
        <option value="">-- Statut --</option>
        <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
        <option value="accepte" <?= $statut === 'accepte' ? 'selected' : '' ?>>Acceptée</option>
        <option value="refuse" <?= $statut === 'refuse' ? 'selected' : '' ?>>Refusée</option>
      </select>
    </div>
    <div class="col-md-3">
      <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut) ?>" placeholder="Date début">
    </div>
    <div class="col-md-3">
      <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin) ?>" placeholder="Date fin">
    </div>
    <div class="col-md-3">
      <button class="btn btn-primary w-100">Filtrer</button>
    </div>
  </form>

  <!-- Vos propres demandes -->
  <h5 class="text-primary"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Vos propres demandes</h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Fichier</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Motif</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($demandes_secretaire) === 0): ?>
          <tr><td colspan="4" class="text-center">Aucune demande.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes_secretaire as $dem): ?>
            <tr>
              <td><?= htmlspecialchars($dem['nom_fichier']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></td>
              <td>
                <?php if ($dem['statut'] === 'en_attente'): ?>
                  <span class="badge bg-secondary">En attente</span>
                <?php elseif ($dem['statut'] === 'accepte'): ?>
                  <span class="badge bg-success">Acceptée</span>
                <?php else: ?>
                  <span class="badge bg-danger">Refusée</span>
                <?php endif; ?>
              </td>
              <td><?= $dem['motif_refus'] ?? '-' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Demandes à soumettre -->
  <h5 class="text-primary"><i class="bi bi-hourglass-split me-2 text-warning"></i> Demandes des employés à soumettre</h5>
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
          <tr><td colspan="5" class="text-center">Aucune demande à soumettre.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes_a_soumettre as $dem): ?>
            <tr>
              <td><?= htmlspecialchars($dem['demandeur_nom']) ?></td>
              <td><?= htmlspecialchars($dem['nom_fichier']) ?></td>
              <td><?= nl2br(htmlspecialchars($dem['commentaire'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></td>
              <td>
                <form method="POST" action="soumettre_ag.php">
                  <input type="hidden" name="id_demande" value="<?= $dem['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-success">Soumettre à l'AG</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Demandes déjà soumises -->
  <h5 class="text-primary"><i class="bi bi-send-check-fill me-2 text-success"></i> Demandes des employés déjà transmises à l'AG</h5>
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
          <tr><td colspan="5" class="text-center">Aucune demande soumise.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes_employes as $dem): ?>
            <tr>
              <td><?= htmlspecialchars($dem['demandeur_nom']) ?></td>
              <td><?= htmlspecialchars($dem['nom_fichier']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></td>
              <td>
                <?php if ($dem['statut'] === 'en_attente'): ?>
                  <span class="badge bg-secondary">En attente</span>
                <?php elseif ($dem['statut'] === 'accepte'): ?>
                  <span class="badge bg-success">Acceptée</span>
                <?php else: ?>
                  <span class="badge bg-danger">Refusée</span>
                <?php endif; ?>
              </td>
              <td><?= $dem['motif_refus'] ?? '-' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
