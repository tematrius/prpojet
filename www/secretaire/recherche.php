<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$stmt = $pdo->query("SELECT id, nom_fichier, provenance, date_upload, chemin, est_restreint FROM archives ORDER BY date_upload DESC LIMIT 12");
$derniers_fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$role = $_SESSION['user']['role'];
?>

<style>
.card-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
}
.card-item {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 10px;
  padding: 15px;
  width: calc(33.333% - 20px);
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.card-title {
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 10px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.card-text {
  font-size: 0.875rem;
  margin-bottom: 6px;
  color: #555;
}
.card-footer {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 10px;
}
.card-icon {
  font-size: 24px;
  color: #0d6efd;
  margin-bottom: 8px;
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

textarea.form-control {
  resize: vertical;
}
</style>

<div class="container mt-4">
  <h3><i class="bi bi-search"></i> Rechercher des fichiers archivés</h3>
  <input id="searchInput" class="form-control my-4" placeholder="Rechercher un mot-clé, titre ou contenu...">

  <div id="results" class="card-grid">
    <?php foreach ($derniers_fichiers as $file): ?>
      <div class="card-item">
        <div class="icon-pdf">PDF</div>
        <h6 class="card-title"><?= htmlspecialchars($file['nom_fichier']) ?></h6>
        <p class="card-text">Provenance : <?= htmlspecialchars($file['provenance']) ?></p>
        <p class="card-text">Ajouté le : <?= date('d/m/Y H:i', strtotime($file['date_upload'])) ?></p>

        <div class="card-footer">
          <?php if ($file['provenance'] === 'AG' && $file['est_restreint'] == 1 && $role === 'secretaire'): ?>
            <span class="badge bg-warning text-dark">Restreint</span>

            <!-- Formulaire de demande d'accès -->
            <form action="demande-acces.php" method="POST">
              <input type="hidden" name="id_document" value="<?= $file['id'] ?>">
              <textarea name="commentaire" class="form-control" rows="2" placeholder="Pourquoi souhaitez-vous accéder ?..." required></textarea>
              <button type="submit" class="btn btn-sm btn-outline-danger mt-2 w-100">Demander l'accès</button>
            </form>

          <?php else: ?>
                <a href="voir-document.php?id=<?= $file['id'] ?>" 
      class="btn btn-sm btn-outline-primary" target="_blank">
      Voir
    </a>

            <a href="telecharger.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-secondary">Télécharger</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
  const query = this.value.trim();
  const container = document.getElementById('results');
  if (!query) {
    window.location.reload(); // Recharge la page d'origine si champ vide
    return;
  }

  fetch('recherche-ajax.php?q=' + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
      container.innerHTML = '';
      if (data.length === 0) {
        container.innerHTML = '<p>Aucun résultat trouvé.</p>';
        return;
      }

      data.forEach(file => {
        const div = document.createElement('div');
        div.className = 'card-item';
        div.innerHTML = `
          <div class="icon-pdf">PDF</div>
          <h6 class="card-title">${file.nom_fichier}</h6>
          <p class="card-text">Provenance : ${file.provenance}</p>
          <p class="card-text">Ajouté le : <?= date('d/m/Y H:i', strtotime($file['date_upload'])) ?></p>
          <div class="card-footer">
            ${
              file.provenance === 'AG' && file.est_restreint == 1 && '<?= $role ?>' === 'secretaire'
              ? `
                <span class="badge bg-warning text-dark">Restreint</span>
                <form action="demande-acces.php" method="POST">
                  <input type="hidden" name="id_document" value="${file.id}">
                  <textarea name="commentaire" class="form-control" rows="2" placeholder="Pourquoi souhaitez-vous accéder ?..." required></textarea>
                  <button type="submit" class="btn btn-sm btn-outline-danger mt-2 w-100">Demander l'accès</button>
                </form>
              `
              : `
                <a href="voir-document.php?id=${file.id}" class="btn btn-sm btn-outline-primary">Voir</a>
                <a href="telecharger.php?id=${file.id}" class="btn btn-sm btn-outline-secondary">Télécharger</a>
              `
            }
          </div>`;
        container.appendChild(div);
      });
    });
});
</script>
