<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$stmt = $pdo->query("SELECT id, nom_fichier, provenance, date_upload, chemin, est_restreint FROM archives ORDER BY date_upload DESC LIMIT 12");
$derniers_fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.card-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}
@media (max-width: 900px) {
  .card-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .card-grid { grid-template-columns: 1fr; }
}
.card-item {
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 10px;
  padding: 15px;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-width: 0;
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
.badge-nouveau {
  background: #28a745;
  color: #fff;
  font-size: 0.7rem;
  margin-left: 8px;
}
.loader {
  display: none;
  margin: 30px auto;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #0d6efd;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.search-bar-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}
.search-history {
  margin-bottom: 10px;
}
.search-history span {
  background: #f1f1f1;
  color: #333;
  border-radius: 12px;
  padding: 3px 10px;
  margin-right: 6px;
  font-size: 0.9em;
  cursor: pointer;
}
.search-history .clear-history {
  color: #dc3545;
  cursor: pointer;
  font-size: 0.9em;
  margin-left: 8px;
}
textarea.form-control {
  resize: vertical;
}
</style>

<div class="container mt-4">
  <h3><i class="bi bi-search"></i> Rechercher des fichiers archivés</h3>
  <div class="search-bar-actions mb-3">
    <input id="searchInput" class="form-control" placeholder="Rechercher un mot-clé, titre ou contenu...">
    <button id="clearBtn" class="btn btn-outline-secondary" title="Effacer la recherche"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="search-history" id="searchHistory"></div>
  <div id="resultCount" class="mb-2 text-muted small"></div>
  <div class="loader" id="loader"></div>
  <div id="results" class="card-grid">
    <?php foreach ($derniers_fichiers as $file): ?>
      <?php $isNew = (strtotime($file['date_upload']) > strtotime('-7 days')); ?>
      <div class="card-item">
        <div class="icon-pdf">PDF</div>
        <h6 class="card-title">
          <?= htmlspecialchars($file['nom_fichier']) ?>
          <?php if ($isNew): ?><span class="badge badge-nouveau">Nouveau</span><?php endif; ?>
        </h6>
        <p class="card-text">Provenance : <?= htmlspecialchars($file['provenance']) ?></p>
        <p class="card-text">Ajouté le : <?= date('d/m/Y H:i', strtotime($file['date_upload'])) ?></p>
        <div class="card-footer">
          <?php if ($file['est_restreint']): ?>
            <span class="badge bg-warning text-dark">Restreint</span>
            <a href="autorisation.php?doc=<?= $file['id'] ?>" class="btn btn-sm btn-outline-warning">Demander l'accès</a>
          <?php else: ?>
            <a href="voir-document.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">Voir</a>
            <a href="telecharger.php?id=<?= $file['id'] ?>" class="btn btn-sm btn-outline-secondary">Télécharger</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="errorMsg" class="text-danger mt-3" style="display:none"></div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const clearBtn = document.getElementById('clearBtn');
const loader = document.getElementById('loader');
const container = document.getElementById('results');
const resultCount = document.getElementById('resultCount');
const errorMsg = document.getElementById('errorMsg');
const searchHistoryDiv = document.getElementById('searchHistory');

function showLoader(show) {
  loader.style.display = show ? 'block' : 'none';
}
function escapeHtml(text) {
  return text.replace(/[&<>"]/g, function(c) {
    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
  });
}
function updateHistoryUI() {
  let hist = JSON.parse(localStorage.getItem('searchHistory')||'[]');
  if(hist.length === 0) { searchHistoryDiv.innerHTML = ''; return; }
  searchHistoryDiv.innerHTML = 'Recherches récentes : ' + hist.map(q => `<span tabindex="0">${escapeHtml(q)}</span>`).join('') + `<span class="clear-history" title="Effacer l'historique">Effacer</span>`;
}
searchHistoryDiv.addEventListener('click', function(e){
  if(e.target.classList.contains('clear-history')) {
    localStorage.removeItem('searchHistory');
    updateHistoryUI();
  } else if(e.target.tagName==='SPAN') {
    searchInput.value = e.target.textContent;
    searchInput.dispatchEvent(new Event('input'));
  }
});

function addToHistory(q) {
  let hist = JSON.parse(localStorage.getItem('searchHistory')||'[]');
  hist = hist.filter(x=>x!==q);
  hist.unshift(q);
  if(hist.length>5) hist = hist.slice(0,5);
  localStorage.setItem('searchHistory', JSON.stringify(hist));
  updateHistoryUI();
}

function renderResults(data) {
  container.innerHTML = '';
  errorMsg.style.display = 'none';
  if (data.length === 0) {
    resultCount.textContent = 'Aucun résultat.';
    container.innerHTML = '<p>Aucun résultat trouvé.</p>';
    return;
  }
  resultCount.textContent = data.length + ' résultat' + (data.length>1?'s':'');
  data.forEach(file => {
    const isNew = (new Date(file.date_upload) > new Date(Date.now()-7*24*60*60*1000));
    const div = document.createElement('div');
    div.className = 'card-item';
    div.innerHTML = `
      <div class="icon-pdf">PDF</div>
      <h6 class="card-title">${escapeHtml(file.nom_fichier)}${isNew?'<span class=\'badge badge-nouveau\'>Nouveau</span>':''}</h6>
      <p class="card-text">Provenance : ${escapeHtml(file.provenance)}</p>
      <p class="card-text">Ajouté le : ${new Date(file.date_upload).toLocaleString('fr-FR')}</p>
      <div class="card-footer">
        ${
          file.est_restreint == 1
          ? `
            <span class="badge bg-warning text-dark">Restreint</span>
            <a href="autorisation.php?doc=${file.id}" class="btn btn-sm btn-outline-warning">Demander l'accès</a>
          `
          : `
            <a href="voir-document.php?id=${file.id}" class="btn btn-sm btn-outline-primary" target="_blank">Voir</a>
            <a href="telecharger.php?id=${file.id}" class="btn btn-sm btn-outline-secondary">Télécharger</a>
          `
        }
      </div>`;
    container.appendChild(div);
  });
}

// Debounce pour limiter les requêtes AJAX
let debounceTimer;
searchInput.addEventListener('input', function () {
  clearTimeout(debounceTimer);
  const query = this.value.trim();
  if (!query) {
    resultCount.textContent = '';
    errorMsg.style.display = 'none';
    container.innerHTML = document.querySelectorAll('.card-item').length ? container.innerHTML : '';
    return;
  }
  debounceTimer = setTimeout(() => {
    showLoader(true);
    fetch('recherche-ajax.php?q=' + encodeURIComponent(query))
      .then(res => {
        if(!res.ok) throw new Error('Erreur réseau');
        return res.json();
      })
      .then(data => {
        showLoader(false);
        addToHistory(query);
        renderResults(data);
      })
      .catch(e => {
        showLoader(false);
        errorMsg.textContent = 'Erreur lors de la recherche. Veuillez réessayer.';
        errorMsg.style.display = 'block';
      });
  }, 300);
});

clearBtn.addEventListener('click', function(){
  searchInput.value = '';
  resultCount.textContent = '';
  errorMsg.style.display = 'none';
  container.innerHTML = document.querySelectorAll('.card-item').length ? container.innerHTML : '';
});

// Historique au chargement
updateHistoryUI();
</script>
</script>
