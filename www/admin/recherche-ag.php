<?php
require '../includes/db.php';
include '../includes/dashboard-template.php';

$stmt = $pdo->query("SELECT id, nom_fichier, provenance, date_upload, chemin, est_restreint FROM archives ORDER BY date_upload DESC LIMIT 12");
$derniers_fichiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .section { margin-bottom: 30px; }
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
  .clear-btn {
    border: none;
    background: #e9ecef;
    color: #333;
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: none;
    transition: background 0.2s;
    margin-left: 6px;
  }
  .clear-btn:hover, .clear-btn:focus {
    background: #ced4da;
    color: #000;
    outline: none;
  }
</style>

<div class="container">
  <h3 class="mb-4"><i class="bi bi-search"></i> Rechercher dans les fichiers archivés</h3>

  <div class="search-bar-actions mb-3">
    <input id="searchInput" class="form-control" placeholder="Rechercher un mot-clé, titre ou contenu...">
    <button id="clearBtn" class="clear-btn" title="Effacer la recherche"><i class="bi bi-x-lg"></i></button>
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
        <a href="voir-document.php?id=${file.id}" class="btn btn-sm btn-outline-primary">Voir</a>
        <a href="telecharger.php?id=${file.id}" class="btn btn-sm btn-outline-secondary">Télécharger</a>
        ${file.provenance === 'AG' ? (file.est_restreint == 1 ? `
          <form method=\"POST\" action=\"changer-restriction.php\">
            <input type=\"hidden\" name=\"id\" value=\"${file.id}\">
            <input type=\"hidden\" name=\"action\" value=\"de-restreindre\">
            <button type=\"submit\" class=\"btn btn-sm btn-warning\"><i class=\"bi bi-shield-lock me-2\"></i> Rendre public</button>
          </form>
        ` : `
          <form method=\"POST\" action=\"changer-restriction.php\">
            <input type=\"hidden\" name=\"id\" value=\"${file.id}\">
            <input type=\"hidden\" name=\"action\" value=\"restreindre\">
            <button type=\"submit\" class=\"btn btn-sm btn-danger\"><i class=\"bi bi-shield-lock me-2\"></i> Restreindre</button>
          </form>
        `) : ''}
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
    fetch('recherche-ajax-ag.php?q=' + encodeURIComponent(query))
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
