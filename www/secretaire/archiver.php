<?php 
session_start();
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require '../includes/db.php';
require '../includes/encryption.php'; 
ob_start(); // Empêche les headers d’être envoyés trop tôt
include '../includes/dashboard-template.php';



$doc_id = $_GET['doc_id'] ?? null;
$fichierPrecharge = $_GET['fichier'] ?? null;
$provenancePrecharge = $_GET['provenance'] ?? null;
function generate_uuid() {
return bin2hex(random_bytes(16));
}

$uploadRoot = __DIR__ . '/../uploads/archives/' . date('Y-m-d') . '/';
if (!is_dir($uploadRoot)) mkdir($uploadRoot, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['file']) || isset($_POST['fichier']))) {

$nomOriginal = '';
$uuid = generate_uuid();
$cheminFinal = $uploadRoot . $uuid . '.enc';

if (isset($_FILES['file'])) {
    $tmp = $_FILES['file']['tmp_name'];
    $nomOriginal = basename($_FILES['file']['name']);
} else {
    $nomOriginal = $_POST['fichier'];
    $tmp = __DIR__ . '/../uploads/' . $nomOriginal;
    if (!file_exists($tmp)) {
        $tmp = __DIR__ . '/../uploads/documents/' . $nomOriginal;
        if (!file_exists($tmp)) {
            http_response_code(500);
            echo "Fichier introuvable.";
            exit;
        }
    }
}


// Récupère la clé active
$stmt = $pdo->query("SELECT id, valeur FROM cles WHERE active = 1 LIMIT 1");
$cle = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$cle) {
    http_response_code(500);
    echo "Aucune clé active disponible pour le chiffrement.";
    exit;
}
$data = file_get_contents($tmp);
$encryptedData = encrypt_file($data, $cle['valeur']);
file_put_contents($cheminFinal, $encryptedData);

$cheminBDD = 'uploads/archives/' . date('Y-m-d') . '/' . $uuid . '.enc';

$provenance = $_POST['provenance'] ?? 'Inconnue';
if ($provenance === 'autre') {
    $provenance = $_POST['provenance_autre'] ?? 'Inconnue';
}
$provenance = strtoupper(trim($provenance));

// Envoi au serveur OCR inchangé
$tempDecryptPath = $uploadRoot . $uuid . '-temp.pdf';
file_put_contents($tempDecryptPath, decrypt_file($encryptedData, $cle['valeur'])); // Si OCR exige PDF brut

$curl = curl_init();
$dataCurl = [
    'file' => new CURLFile($tempDecryptPath),
    'provenance' => $provenance
];

curl_setopt_array($curl, [
    CURLOPT_URL => 'http://127.0.0.1:5000/ocr',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $dataCurl
]);

$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);
unlink($tempDecryptPath);

if ($error) {
    http_response_code(500);
    echo "Erreur OCR : $error";
    exit;
}

$result = json_decode($response, true);
if (!isset($result['contenu'])) {
    http_response_code(500);
    echo "Réponse invalide du serveur OCR.";
    exit;
}

// Enregistrement BDD
$est_restreint = ($provenance === 'AG') ? 1 : 0;

$stmt = $pdo->prepare("INSERT INTO archives (nom_fichier, chemin, provenance, contenu_textuel, est_restreint, id_cle) VALUES (?, ?, ?, ?, ?, ?)");
$ok = $stmt->execute([$nomOriginal, $cheminBDD, $provenance, $result['contenu'], $est_restreint, $cle['id']]);
if (!$ok) {
    $errorInfo = $stmt->errorInfo();
    die("Erreur SQL : " . $errorInfo[2]);
}

if (isset($_POST['doc_id'])) {
    $stmt2 = $pdo->prepare("UPDATE documents SET etat = 'traite' WHERE id = ?");
    $stmt2->execute([$_POST['doc_id']]);
}

ob_end_clean();
echo "<script>
    alert('✅ Fichier archivé avec succès.');
    window.location.href = 'notifications.php';
</script>";
exit;
 
}

   

?>

<style>
.container { max-width: 960px; }
.dropzone {
  border: 2px dashed #0d6efd;
  border-radius: 8px;
  padding: 40px;
  background-color: #f8f9fa;
  text-align: center;
  color: #6c757d;
  cursor: pointer;
}
</style>

<div class="container mt-4">
  <h3 class="mb-4"><i class="bi bi-file-earmark-arrow-up"></i> Archiver un fichier</h3>

  <?php if ($fichierPrecharge): ?>
    <div class="alert alert-info">
      <strong>Fichier :</strong> <?= htmlspecialchars($fichierPrecharge) ?><br>
      <strong>Provenance :</strong> <?= htmlspecialchars($provenancePrecharge) ?>
    </div>
    <form method="POST" id="archiverForm">
      <input type="hidden" name="fichier" value="<?= htmlspecialchars($fichierPrecharge) ?>">
      <input type="hidden" name="provenance" value="<?= htmlspecialchars($provenancePrecharge) ?>">
      <?php if ($doc_id): ?>
        <input type="hidden" name="doc_id" value="<?= htmlspecialchars($doc_id) ?>">
      <?php endif; ?>
      <button type="submit" class="btn btn-success" id="archiverBtn">
        <i class="bi bi-check-circle"></i> Archiver maintenant
      </button>
      <div id="spinnerArea" class="mt-3" style="display: none;">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Archivage en cours...</span>
        </div>
        <span class="ms-2">Archivage en cours...</span>
      </div>
    </form>

  <?php else: ?>
    <form id="provenanceForm" method="POST" class="mb-4">
      <label>Provenance du fichier</label>
      <select name="provenance" class="form-select" onchange="toggleProvenance(this)">
        <option value="secretaire">secretaire</option>
        <option value="AG">AG</option>
        <option value="autre">Autre...</option>
      </select>
      <div class="mt-2" id="autreProvenance" style="display:none">
        <input type="text" name="provenance_autre" class="form-control" placeholder="Préciser la provenance">
      </div>
    </form>

    <form action="archiver.php" class="dropzone" id="dropzoneForm" enctype="multipart/form-data">
      Glissez et déposez ici vos fichiers PDF ou images
    </form>

    <div id="uploadStatus"></div>
  <?php endif; ?>
</div>

<script>
function toggleProvenance(select) {
  document.getElementById('autreProvenance').style.display = select.value === 'autre' ? 'block' : 'none';
}

Dropzone.autoDiscover = false;
const provenanceForm = document.getElementById('provenanceForm');
const dz = new Dropzone("#dropzoneForm", {
  paramName: "file",
  maxFilesize: 200,
  acceptedFiles: ".pdf,.jpg,.jpeg,.png,.bmp,.tif,.tiff",
  init: function () {
    this.on("sending", function(file, xhr, formData) {
      const provenanceData = new FormData(provenanceForm);
      for (const [key, value] of provenanceData.entries()) {
        formData.append(key, value);
      }
    });
    this.on("success", function(file, response) {
      document.getElementById("uploadStatus").innerHTML = '<div class="alert alert-success">Fichier archivé avec succès.</div>';
    });
    this.on("error", function(file, errorMessage) {
      document.getElementById("uploadStatus").innerHTML = '<div class="alert alert-danger">Erreur : ' + errorMessage + '</div>';
    });
  }
});
</script>
<script>
document.getElementById('archiverForm')?.addEventListener('submit', function(e) {
  const btn = document.getElementById('archiverBtn');
  const spinner = document.getElementById('spinnerArea');

  btn.disabled = true;
  spinner.style.display = 'block';
});
</script>

