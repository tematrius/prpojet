<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$user_id = $_SESSION['user']['id'];


// Vérification de rôle
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'secretaire') {
    header('Location: /index.php');
    exit;
}

// Documents à archiver
$stmt1 = $pdo->prepare("SELECT d.*, u.nom AS auteur_nom, u.role AS auteur_role FROM documents d JOIN utilisateurs u ON d.auteur_id = u.id WHERE d.etat = 'en_attente' ORDER BY d.date_upload DESC");
$stmt1->execute();
$documents = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// Demandes d'autorisation
$stmt2 = $pdo->prepare("SELECT d.id, d.date_post, u.nom, a.nom_fichier, d.id_document FROM demandes d 
    JOIN utilisateurs u ON d.id_demandeur = u.id
    JOIN archives a ON d.id_document = a.id
    WHERE d.statut = 'en_attente'&& d.soumis_ag = 0");
$stmt2->execute();
$demandes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Visualisations autorisées
$stmt3 = $pdo->prepare("
SELECT d.token, d.expiration_acces, d.telechargements_restants,
a.nom_fichier, u.nom AS demandeur,id_document
FROM demandes d
JOIN archives a ON d.id_document = a.id
JOIN utilisateurs u ON d.id_demandeur = u.id
WHERE d.statut = 'accepte'
AND id_demandeur = ?
AND d.expiration_acces > NOW()
ORDER BY d.date_post DESC
");
$stmt3->execute([$user_id]);
$visualisations = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
  <h3><i class="bi bi-bell me-2"></i>Centre de notifications</h3>

  <div class="mt-4">
    <h5 class="text-primary"><i class="bi bi-file-earmark-arrow-up me-2"></i> Documents à archiver</h5>
    <?php if (count($documents) > 0): ?>
      <ul class="list-group mb-4">
        <?php foreach ($documents as $doc): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong><?= htmlspecialchars($doc['titre']) ?></strong><br>
                <small class="text-muted">Envoyé le <?= date('d/m/Y H:i', strtotime($doc['date_upload'])) ?></small>
              </div>
              <div>
                <button class="btn btn-sm btn-outline-primary me-2" onclick="toggleDetails(<?= $doc['id'] ?>)">
                  <i class="bi bi-eye"></i> Voir plus
                </button>
                <a href="archiver.php?doc_id=<?= $doc['id'] ?>&fichier=<?= urlencode($doc['nom_fichier']) ?>&provenance=<?= urlencode($doc['provenance']) ?>" class="btn btn-sm btn-success">
                  <i class="bi bi-check-circle"></i> Archiver
                </a>
              </div>
            </div>
            <div id="details-<?= $doc['id'] ?>" class="mt-3 p-3 bg-light rounded shadow-sm border" style="display: none;">
              <p><i class="bi bi-chat-text me-2"></i><strong>Commentaire :</strong><br> <?= nl2br(htmlspecialchars($doc['commentaire'])) ?></p>
              <p><i class="bi bi-geo-alt me-2"></i><strong>Provenance :</strong> <?= htmlspecialchars($doc['provenance']) ?></p>
              <p><i class="bi bi-person-circle me-2"></i><strong>Expéditeur :</strong> <?= htmlspecialchars($doc['auteur_nom']) ?> (<?= $doc['auteur_role'] ?>)</p>
              <p><i class="bi bi-file-earmark-text me-2"></i><strong>Fichier :</strong> <a href="../uploads/<?= urlencode($doc['nom_fichier']) ?>" target="_blank">Ouvrir dans le navigateur</a></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted">Aucun document à archiver.</p>
    <?php endif; ?>
  </div>

  <hr>

  <div class="mt-4">
    <h5 class="text-primary"><i class="bi bi-shield-lock me-2"></i> Demandes d'autorisation d'accès</h5>
    <?php if (count($demandes) > 0): ?>
      <ul class="list-group">
        <?php foreach ($demandes as $dem): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong><?= htmlspecialchars($dem['nom']) ?></strong> a demandé l'accès à
              <em><?= htmlspecialchars($dem['nom_fichier']) ?></em><br>
              <small class="text-muted">Le <?= date('d/m/Y H:i', strtotime($dem['date_post'])) ?></small>
            </div>
            <form method="POST" action="soumettre_ag.php" style="display:inline">
              <input type="hidden" name="demande_id" value="<?= $dem['id'] ?>">
              <input type="hidden" name="document_id" value="<?= $dem['id_document'] ?>">
              <button type="submit" class="btn btn-sm btn-warning">
                <i class="bi bi-send"></i> Soumettre à l'AG
              </button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-muted">Aucune demande d'autorisation en attente.</p>
    <?php endif; ?>

  </div>
  <hr> <div class="mt-4"> 
    <h5 class="text-primary"><i class="bi bi-eye me-2"></i> Fichiers accessibles</h5> 
    <?php if (count($visualisations) > 0): ?> 
      <ul class="list-group"> 
        <?php foreach ($visualisations as $vis): ?> 
          <li class="list-group-item d-flex justify-content-between align-items-center"> 
            <div> 
              <strong><?= htmlspecialchars($vis['nom_fichier']) ?>
            </strong><br> 
            <small class="text-muted">Demandé par <?= htmlspecialchars($vis['demandeur']) ?></small><br> 
            <small class="text-muted">Expire : <?= date('d/m/Y H:i', strtotime($vis['expiration_acces'])) ?></small><br> 
            <small class="text-muted">Restant :  <?= $vis['telechargements_restants'] ?> téléchargement(s)</small> 
            </div> 
            <div class="d-flex gap-2"><a href="voir-document.php?id=<?=  urlencode($vis['id_document']) ?>" target="_blank" class="btn btn-sm btn-primary">Voir</a>
              <?php if ($vis['telechargements_restants'] > 0): ?> 
               <a href="telecharger.php?token=<?= urlencode($vis['token']) ?>" target="_blank" class="btn btn-sm btn-secondary">Télécharger</a> 
              <?php endif; ?> 
            </div> 
          </li>
               <?php endforeach; ?> 
              </ul> <?php else: ?> 
              <p class="text-muted">Aucun fichier actuellement accessible.</p> 
              <?php endif; ?> 
            </div>
</div>

<script>
function toggleDetails(id) {
  const el = document.getElementById('details-' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
