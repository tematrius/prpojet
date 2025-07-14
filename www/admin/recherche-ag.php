<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$stmt = $pdo->query("SELECT id, nom_fichier, provenance, date_upload, chemin, est_restreint FROM archives ORDER BY date_upload DESC LIMIT 12");
$derniers_fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .section { margin-bottom: 30px; }
  .grid {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
  }
  .card-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    width: calc(33.333% - 20px);
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 220px;
  }
  .card-title {
    font-weight: bold;
    margin-bottom: 5px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .card-text {
    font-size: 0.875rem;
    color: #555;
    margin-bottom: 5px;
  }
  .badge { font-size: 0.75em; }
  .btn { margin-top: 10px; width: 100%; }
  .actions {
    display: flex;
    flex-direction: column;
    gap: 5px;
    margin-top: auto;
  }
  .actions form, .actions a { width: 100%; }
  .icon-pdf {
  width: 48px;
  height: 48px;
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
</style>

<div class="container">
  <h3 class="mb-4"><i class="bi bi-search"></i> Rechercher dans les fichiers archivés</h3>

  <div class="mb-3">
    <input type="text" class="form-control" id="searchInput" placeholder="Tapez pour rechercher...">
  </div>

  <div class="section">
    <h5>Derniers fichiers archivés</h5>
    <div class="grid" id="resultats">
      <?php foreach ($derniers_fichiers as $file): ?>
      <div class="card-item">
        <div class="icon-pdf">PDF</div>
        <div class="card-body">
          <h6 class="card-title"><?= htmlspecialchars($file['nom_fichier']) ?></h6>
          <p class="card-text">Provenance : <?= htmlspecialchars($file['provenance']) ?></p>
          <p class="card-text small">Ajouté le : <?= date('d/m/Y H:i', strtotime($file['date_upload'])) ?></p>
          <div class="actions">
            <a href="voir-document.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
            <a href="telecharger.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-secondary">Télécharger</a>
            <?php if ($file['provenance'] === 'AG'): ?>
              <?php if ($file['est_restreint']): ?>
                <form method="POST" action="changer-restriction.php">
                  <input type="hidden" name="id" value="<?= $file['id'] ?>">
                  <input type="hidden" name="action" value="de-restreindre">
                  <button type="submit" class="btn btn-sm btn-warning"><i class="bi bi-shield-lock me-2"></i> Rendre public</button>
                </form>
              <?php else: ?>
                <form method="POST" action="changer-restriction.php">
                  <input type="hidden" name="id" value="<?= $file['id'] ?>">
                  <input type="hidden" name="action" value="restreindre">
                  <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-shield-lock me-2"></i> Restreindre</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("input", function () {
  const q = this.value.trim();
  fetch("recherche-ajax-ag.php?q=" + encodeURIComponent(q))
    .then((res) => res.json())
    .then((data) => {
      const container = document.getElementById("resultats");
      container.innerHTML = "";
      if (data.length === 0) {
        container.innerHTML = "<p>Aucun résultat.</p>";
        return;
      }
      container.className = "grid";
      data.forEach((file) => {
        const div = document.createElement("div");
        div.className = "card-item";
        div.innerHTML = `
          <div class="icon-pdf">PDF</div>
          <div class="card-body">
            <h6 class="card-title" title="${file.nom_fichier}">${file.nom_fichier}</h6>
            <p class="card-text">Provenance : ${file.provenance}</p>
            <p class="card-text">Ajouté le : ${file.date}</p>
            <div class="actions">
              <a href="voir-document.php?id=${file.id}" class="btn btn-sm btn-outline-primary">Voir</a>
              <a href="telecharger.php?id=${file.id}" class="btn btn-sm btn-outline-secondary">Télécharger</a>
              ${file.provenance === "AG" ? (file.est_restreint == 1 ? `
              <form method="POST" action="changer-restriction.php">
                <input type="hidden" name="id" value="${file.id}">
                <input type="hidden" name="action" value="de-restreindre">
                <button type="submit" class="btn btn-sm btn-warning">
                  <i class="bi bi-shield-lock me-2"></i> Rendre public
                </button>
              </form>` : `
              <form method="POST" action="changer-restriction.php">
                <input type="hidden" name="id" value="${file.id}">
                <input type="hidden" name="action" value="restreindre">
                <button type="submit" class="btn btn-sm btn-danger">
                  <i class="bi bi-shield-lock me-2"></i> Restreindre
                </button>
              </form>`) : ""}
            </div>
          </div>`;
        container.appendChild(div);
      });
    });
});
</script>
