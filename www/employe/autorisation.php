<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';
date_default_timezone_set('Africa/Kinshasa');

$user_id = $_SESSION['user']['id'];
$doc_id = $_GET['doc'] ?? null;
$document = null;

// Si un document est ciblé pour une demande
if ($doc_id) {
    $stmt = $pdo->prepare("SELECT id, nom_fichier FROM archives WHERE id = ?");
    $stmt->execute([$doc_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si formulaire soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require '../includes/log.php';
        $commentaire = $_POST['commentaire'] ?? null;

        // Vérifie la dernière demande pour ce document
        $check = $pdo->prepare("SELECT expiration_acces, statut FROM demandes WHERE id_demandeur = ? AND id_document = ? ORDER BY date_post DESC LIMIT 1");
        $check->execute([$user_id, $doc_id]);
        $last = $check->fetch(PDO::FETCH_ASSOC);
        $can_create = false;
        if (!$last) {
            $can_create = true;
        } else if ($last['statut'] !== 'accepte') {
            $can_create = true;
        } else if (!empty($last['expiration_acces']) && strtotime($last['expiration_acces']) < time()) {
            $can_create = true;
        }
        if ($can_create) {
            $insert = $pdo->prepare("INSERT INTO demandes (id_demandeur, id_document, statut, date_post, commentaire) VALUES (?, ?, 'en_attente', NOW(), ?)");
            $insert->execute([$user_id, $doc_id, $commentaire]);
            add_log('demande_acces', $user_id, $commentaire, 'demande', $pdo->lastInsertId(), 'soumis', 'Demande d\'accès créée', $_SERVER['REMOTE_ADDR']);
            // Toast Bootstrap pour feedback utilisateur
            echo "<div class='toast-container position-fixed top-0 end-0 p-3' style='z-index: 9999;'>"
                ."<div id='successToast' class='toast align-items-center text-bg-success border-0 show' role='alert' aria-live='assertive' aria-atomic='true'>"
                ."<div class='d-flex'>"
                ."<div class='toast-body'>✅ Demande envoyée avec succès.". "</div>"
                ."<button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast' aria-label='Close'></button>"
                ."</div>"
                ."</div>"
                ."</div>";
            echo "<script>setTimeout(()=>{window.location.href='autorisation.php';}, 1500);</script>";
            exit;
        } else {
            echo "<div class='alert alert-warning mt-3 container'>⚠️ Vous avez déjà fait une demande pour ce document et l'accès n'est pas encore expiré.</div>";
        }
    }
}

// Récupérer les demandes passées
// On récupère aussi expiration_acces, telechargements_restants et token
$stmt = $pdo->prepare("
    SELECT d.id, d.statut, d.date_post, d.commentaire, d.motif_refus, d.expiration_acces, d.telechargements_restants, d.token, id_document, a.nom_fichier, a.chemin, a.provenance, a.date_upload
    FROM demandes d
    JOIN archives a ON d.id_document = a.id
    WHERE d.id_demandeur = ?
    ORDER BY d.date_post DESC
");
$stmt->execute([$user_id]);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h3><i class="bi bi-shield-lock"></i> Mes demandes d'accès</h3>

  <?php if ($document): ?>
    <div class="card my-4">
      <div class="card-header bg-light">
        <strong>Faire une demande pour :</strong> <?= htmlspecialchars($document['nom_fichier']) ?>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="doc_id" value="<?= $document['id'] ?>">
          <div class="mb-3">
            <label for="commentaire" class="form-label">Commentaire (facultatif)</label>
            <textarea name="commentaire" class="form-control" rows="3" placeholder="Expliquez pourquoi vous souhaitez accéder à ce fichier..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-send-check me-2"></i>Soumettre la demande</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <input id="searchInput" type="text" class="form-control w-auto" placeholder="Rechercher par fichier ou statut..." style="min-width:220px;">
    <span class="ms-auto" id="pagination"></span>
  </div>
  <div class="table-responsive mt-2">
    <table id="demandesTable" class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Fichier</th>
          <th>Provenance</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Action</th>
          <th>Relancer</th>
        </tr>
      </thead>
      <tbody id="demandesBody">
        <?php if (empty($demandes)): ?>
          <tr><td colspan="6" class="text-center">Aucune demande pour l’instant.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes as $i => $dem): ?>
            <tr data-index="<?= $i ?>">
              <td><?= htmlspecialchars($dem['nom_fichier']) ?></td>
              <td><?= htmlspecialchars($dem['provenance']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></td>
              <td>
                <?php if ($dem['statut'] === 'en_attente'): ?>
                  <span class="badge bg-secondary">En attente</span>
                <?php elseif ($dem['statut'] === 'accepte'): ?>
                  <span class="badge bg-success">Accepté</span>
                <?php else: ?>
                  <span class="badge bg-danger">Refusé</span>
                  <?php if (!empty($dem['motif_refus'])): ?>
                    <br><small class="text-muted">Motif : <?= htmlspecialchars($dem['motif_refus']) ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($dem['statut'] === 'accepte'):
                  $expiration = isset($dem['expiration_acces']) ? strtotime($dem['expiration_acces']) : 0;
                  $now = time();
                  $can_see = $expiration > $now;
                  $can_download = $can_see && $dem['telechargements_restants'] > 0;
                ?>
                  <?php if ($can_see): ?>
                    <a href="voir-document.php?id=<?= $dem['id_document'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
                  <?php endif; ?>
                  <?php if ($can_download): ?>
                    <a href="telecharger.php?token=<?= urlencode($dem['token']) ?>" class="btn btn-sm btn-success ms-1">Télécharger (<?= $dem['telechargements_restants'] ?>)</a>
                  <?php endif; ?>
                  <?php if (!$can_see && !$can_download): ?>
                    <span class="text-muted">Accès expiré</span>
                  <?php endif; ?>
                <?php elseif ($dem['statut'] === 'refuse'): ?>
                  <i class="text-muted">Refusé</i>
                <?php else: ?>
                  <i class="text-muted">En attente</i>
                <?php endif; ?>
              </td>
              <td>
                <?php
                $can_relaunch = false;
                if ($dem['statut'] === 'refuse') {
                  $can_relaunch = true;
                } elseif ($dem['statut'] === 'accepte') {
                  $expiration = isset($dem['expiration_acces']) ? strtotime($dem['expiration_acces']) : 0;
                  $now = time();
                  if ($expiration > 0 && $expiration < $now) {
                    $can_relaunch = true;
                  }
                }
                ?>
                <?php if ($can_relaunch): ?>
                  <button type="button" class="btn btn-sm btn-warning relanceBtn" data-docid="<?= $dem['id_document'] ?>" data-filename="<?= htmlspecialchars($dem['nom_fichier'], ENT_QUOTES) ?>"><i class="bi bi-arrow-repeat"></i> Relancer</button>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pas de modal, relance directe -->
<script>
// --- Recherche et pagination côté client ---
const demandes = <?php echo json_encode($demandes); ?>;
const rowsPerPage = 10;
let currentPage = 1;
let filtered = demandes;

function renderTable(page = 1) {
  const tbody = document.getElementById('demandesBody');
  tbody.innerHTML = '';
  const start = (page-1)*rowsPerPage;
  const end = start+rowsPerPage;
  const pageData = filtered.slice(start, end);
  if(pageData.length === 0) {
    tbody.innerHTML = `<tr><td colspan='6' class='text-center'>Aucune demande pour l’instant.</td></tr>`;
    document.getElementById('pagination').innerHTML = '';
    return;
  }
  pageData.forEach((dem, i) => {
    let statutHtml = '';
    if(dem.statut === 'en_attente') statutHtml = `<span class='badge bg-secondary'>En attente</span>`;
    else if(dem.statut === 'accepte') statutHtml = `<span class='badge bg-success'>Accepté</span>`;
    else {
      statutHtml = `<span class='badge bg-danger'>Refusé</span>`;
      if(dem.motif_refus) statutHtml += `<br><small class='text-muted'>Motif : ${dem.motif_refus}</small>`;
    }
    // Actions
    let expiration = dem.expiration_acces ? Date.parse(dem.expiration_acces.replace(/-/g,'/'))/1000 : 0;
    let now = Math.floor(Date.now()/1000);
    let can_see = (dem.statut === 'accepte') && (expiration > now);
    let can_download = can_see && dem.telechargements_restants > 0;
    let actions = '';
    if(dem.statut === 'accepte') {
      if(can_see) actions += `<a href='voir-document.php?id=${dem.id_document}' target='_blank' class='btn btn-sm btn-outline-primary'>Voir</a>`;
      if(can_download) actions += ` <a href='telecharger.php?token=${encodeURIComponent(dem.token)}' class='btn btn-sm btn-success ms-1'>Télécharger (${dem.telechargements_restants})</a>`;
      if(!can_see && !can_download) actions += `<span class='text-muted'>Accès expiré</span>`;
    } else if(dem.statut === 'refuse') {
      actions = `<i class='text-muted'>Refusé</i>`;
    } else {
      actions = `<i class='text-muted'>En attente</i>`;
    }
    // Bouton Relancer (même logique que PHP)
    let can_relaunch = false;
    if (dem.statut === 'refuse') {
      can_relaunch = true;
    } else if (dem.statut === 'accepte') {
      if (expiration > 0 && expiration < now) {
        can_relaunch = true;
      }
    }
    let relanceBtn = can_relaunch
      ? `<button type='button' class='btn btn-sm btn-warning relanceBtn' data-docid='${dem.id_document}' data-filename='${dem.nom_fichier.replace(/'/g, "&#39;")}'><i class='bi bi-arrow-repeat'></i> Relancer</button>`
      : `<span class='text-muted'>-</span>`;
    tbody.innerHTML += `
      <tr data-index='${dem.index ?? (start+i)}'>
        <td>${dem.nom_fichier}</td>
        <td>${dem.provenance}</td>
        <td>${new Date(dem.date_post).toLocaleString('fr-FR')}</td>
        <td>${statutHtml}</td>
        <td>${actions}</td>
        <td>${relanceBtn}</td>
      </tr>`;
  });
  // Pagination
  const totalPages = Math.ceil(filtered.length/rowsPerPage);
  let pagHtml = '';
  for(let p=1;p<=totalPages;p++) {
    pagHtml += `<button class='btn btn-sm ${p===page?'btn-primary':'btn-outline-primary'} me-1' onclick='gotoPage(${p})'>${p}</button>`;
  }
  document.getElementById('pagination').innerHTML = pagHtml;
}

function gotoPage(p) {
  currentPage = p;
  renderTable(currentPage);
}

document.getElementById('searchInput').addEventListener('input', function(e){
  const val = e.target.value.toLowerCase();
  filtered = demandes.filter(dem =>
    dem.nom_fichier.toLowerCase().includes(val) ||
    dem.statut.toLowerCase().includes(val)
  );
  currentPage = 1;
  renderTable(currentPage);
});


// Relance demande : pré-remplit le formulaire pour le document concerné
document.addEventListener('click', function(e){
  const btn = e.target.closest && e.target.closest('.relanceBtn');
  if(btn){
    const docid = btn.getAttribute('data-docid');
    const filename = btn.getAttribute('data-filename');
    // Redirige vers la page avec le paramètre doc
    window.location.href = 'autorisation.php?doc=' + encodeURIComponent(docid);
  }
});

// Initialisation
window.addEventListener('DOMContentLoaded', ()=>{
  demandes.forEach((d,i)=>d.index=i); // Pour retrouver l'index même après filtrage
  renderTable(currentPage);
});
</script>
</div>
