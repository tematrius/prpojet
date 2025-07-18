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
        $commentaire = $_POST['commentaire'] ?? null;

        // Éviter les doublons
        $check = $pdo->prepare("SELECT COUNT(*) FROM demandes WHERE id_demandeur = ? AND id_document = ?");
        $check->execute([$user_id, $doc_id]);
        if ($check->fetchColumn() == 0) {
            $insert = $pdo->prepare("INSERT INTO demandes (id_demandeur, id_document, statut, date_post, commentaire) VALUES (?, ?, 'en_attente', NOW(), ?)");
            $insert->execute([$user_id, $doc_id, $commentaire]);
            echo "<script>alert('✅ Demande envoyée avec succès.'); window.location.href = 'autorisation.php';</script>";
            exit;
        } else {
            echo "<div class='alert alert-warning mt-3 container'>⚠️ Vous avez déjà fait une demande pour ce document.</div>";
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

  <div class="table-responsive mt-4">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Fichier</th>
          <th>Provenance</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($demandes)): ?>
          <tr><td colspan="5" class="text-center">Aucune demande pour l’instant.</td></tr>
        <?php else: ?>
          <?php foreach ($demandes as $dem): ?>
            <tr>
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
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
