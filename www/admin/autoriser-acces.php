<?php
ob_start();
require '../includes/db.php';
include '../includes/dashboard-template.php';

// Traitement des décisions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_demande'], $_POST['statut'])) {
    $id = intval($_POST['id_demande']);
    $statut = $_POST['statut'];
    $motif_refus = $_POST['motif_refus'] ?? null;

    if (!in_array($statut, ['accepte', 'refuse'])) die("❌ Statut invalide.");

    if ($statut === 'refuse' && $motif_refus) {
        $stmt = $pdo->prepare("UPDATE demandes SET statut = ?, motif_refus = ? WHERE id = ?");
        $stmt->execute([$statut, $motif_refus, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE demandes SET statut = ? WHERE id = ?");
        $stmt->execute([$statut, $id]);
    }

    header("Location: autoriser-acces.php?info=success");
    exit;
}

// Récupération des demandes
$stmt = $pdo->prepare("
    SELECT d.id, d.date_post, d.commentaire, u.nom AS demandeur, a.nom_fichier, a.provenance
    FROM demandes d
    JOIN utilisateurs u ON d.id_demandeur = u.id
    JOIN archives a ON d.id_document = a.id
    WHERE d.statut = 'en_attente' AND d.soumis_ag = 1
    ORDER BY d.date_post DESC
");
$stmt->execute();
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.card-demande {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-bottom: 20px;
  padding: 16px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.card-header h5 {
  font-size: 1.1rem;
  margin: 0;
}
.detail {
  display: none;
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid #eee;
}
  .icon-pdf {
  width: 28px;
  height: 28px;
  background-color: #0d6efd;
  color: white;
  font-weight: bold;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 10px;
}
textarea {
  resize: vertical;
}
</style>

<div class="container mt-4">
  <h3><i class="bi bi-check2-square me-2"></i> Autoriser l'accès aux fichiers</h3>

  <?php if (isset($_GET['info']) && $_GET['info'] === 'success'): ?>
    <div class="alert alert-success mt-3">✅ Décision enregistrée avec succès.</div>
  <?php endif; ?>

  <?php if (empty($demandes)): ?>
    <div class="alert alert-info mt-4">Aucune demande en attente.</div>
  <?php endif; ?>

  <?php foreach ($demandes as $d): ?>
    <div class="card-demande">
      <div class="card-header">
        <h5><div class="icon-pdf">PDF</div> <?= htmlspecialchars($d['nom_fichier']) ?></h5>
        <button class="btn btn-sm btn-outline-primary toggle-btn">Voir plus</button>
      </div>
      <div><small><i class="bi bi-person"></i> <?= htmlspecialchars($d['demandeur']) ?> | <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($d['date_post'])) ?></small></div>
      
      <div class="detail">
        <p><strong>Provenance :</strong> <?= htmlspecialchars($d['provenance']) ?></p>
        <p><strong>Commentaire :</strong><br><?= nl2br(htmlspecialchars($d['commentaire'])) ?></p>

        <!-- Action -->
        <div class="d-flex flex-column gap-2">
          <!-- Accepter -->
          <form method="POST" class="d-inline">
            <input type="hidden" name="id_demande" value="<?= $d['id'] ?>">
            <input type="hidden" name="statut" value="accepte">
            <button type="submit" class="btn btn-success"><i class="bi bi-check2-square me-2"></i>  Accepter</button>
          </form>

          <!-- Refuser -->
          <form method="POST">
            <input type="hidden" name="id_demande" value="<?= $d['id'] ?>">
            <input type="hidden" name="statut" value="refuse">
            <div class="input-group">
              <input type="text" name="motif_refus" class="form-control" placeholder="Motif du refus" required>
              <button type="submit" class="btn btn-outline-danger">Refuser</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
document.querySelectorAll(".toggle-btn").forEach(btn => {
  btn.addEventListener("click", () => {
    const detail = btn.closest(".card-demande").querySelector(".detail");
    const isOpen = detail.style.display === 'block';
    detail.style.display = isOpen ? 'none' : 'block';
    btn.textContent = isOpen ? 'Voir plus' : 'Voir moins';
  });
});
</script>
