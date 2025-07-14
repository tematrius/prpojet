<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$stmt = $pdo->query("SELECT id, nom_fichier, provenance, date_upload, chemin, est_restreint FROM archives ORDER BY date_upload DESC LIMIT 12");
$derniers_fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
}
.card-item {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 15px;
  width: calc(33.333% - 16px);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.card-title {
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 8px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-text {
  font-size: 0.875rem;
  margin-bottom: 6px;
}
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
.actions {
  margin-top: auto;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
</style>

<div class="container mt-4">
  <h3><i class="bi bi-search"></i> Rechercher un document</h3>

  <input type="text" id="searchInput" class="form-control my-3" placeholder="Tapez pour rechercher...">

  <div class="section">
    <h5>Derniers fichiers archivés</h5>
    <div class="grid" id="resultats">
      <?php foreach ($derniers_fichiers as $file): ?>
        <div class="card-item">
          <div class="icon-pdf">PDF</div>
          <div>
            <h6 class="card-title"> <?= htmlspecialchars($file['nom_fichier']) ?></h6>
            <p class="card-text">Provenance : <?= htmlspecialchars($file['provenance']) ?></p>
            <p class="card-text">Ajouté le : <?= date('d/m/Y H:i', strtotime($file['date_upload'])) ?></p>
          </div>
          <div class="actions">
            <?php if ($file['est_restreint']): ?>
              <span class="badge bg-warning text-dark">Restreint</span>
              <a href="autorisation.php?doc=<?= $file['id'] ?>" class="btn btn-sm btn-outline-warning">Demander l'accès</a>
            <?php else: ?>
              <a href="<?= $file['chemin'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
              <a href="<?= $file['chemin'] ?>" download class="btn btn-sm btn-outline-secondary">Télécharger</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("input", function () {
  const query = this.value.trim();
  fetch("recherche-ajax.php?q=" + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById("resultats");
      container.innerHTML = "";

      if (data.length === 0) {
        container.innerHTML = "<p>Aucun résultat trouvé.</p>";
        return;
      }

      data.forEach(file => {
        const div = document.createElement("div");
        div.className = "card-item";
        div.innerHTML = `
          <div>
          <div class="icon-pdf">PDF</div>
            <h6 class="card-title"> ${file.nom_fichier}</h6>
            <p class="card-text">Provenance : ${file.provenance}</p>
            <p class="card-text">Ajouté le : ${file.date}</p>
          </div>
          <div class="actions">
            ${file.est_restreint == 1
              ? `<span class="badge bg-warning text-dark">Restreint</span>
                 <a href="autorisation.php?doc=<?= $file['id'] ?>" class="btn btn-sm btn-outline-danger">Demander accès</a>`
              : `<a href="${file.chemin}" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>
                 <a href="${file.chemin}" download class="btn btn-sm btn-outline-secondary">Télécharger</a>`
            }
          </div>`;
        container.appendChild(div);
      });
    });
});
</script>
