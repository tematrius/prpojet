<?php 
require '../includes/db.php';
ob_start(); // Empêche les headers d’être envoyés trop tôt
include '../includes/dashboard-template.php';



// Pré-remplissage depuis GET (ex: lien depuis notification)
$doc_id = $_GET['doc_id'] ?? null;
$fichierPrecharge = $_GET['fichier'] ?? null;
$provenancePrecharge = $_GET['provenance'] ?? null;

// Traitement uniquement en POST avec fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['file']) || isset($_POST['fichier']))) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $nom = '';
    if (isset($_FILES['file'])) {
        $fichier = $_FILES['file'];
        $tmp = $fichier['tmp_name'];
        $nom = basename($fichier['name']);
        $chemin = $uploadDir . $nom;

        if (!move_uploaded_file($tmp, $chemin)) {
            http_response_code(500);
            echo "Erreur lors de l'enregistrement du fichier.";
            exit;
        }
    } else {
        $nom = $_POST['fichier'];
        $chemin = $uploadDir . $nom;

        if (!file_exists($chemin)) {
            // 🟡 Vérifie dans le dossier uploads/documents/
            $altPath = $uploadDir . 'documents/' . $nom;

            if (file_exists($altPath)) {
                $chemin = $altPath;
            } else {
                http_response_code(500);
                echo "Fichier préchargé introuvable dans uploads/ ni uploads/documents/";
                exit;
            }
        }
    }

    $provenance = $_POST['provenance'] ?? 'Inconnue';
    if ($provenance === 'autre') {
        $provenance = $_POST['provenance_autre'] ?? 'Inconnue';
    }
    $provenance = strtoupper(trim($provenance));

    // Envoyer au serveur OCR
    $curl = curl_init();
    $data = [
        'file' => new CURLFile($chemin),
        'provenance' => $provenance
    ];

    curl_setopt_array($curl, [
        CURLOPT_URL => 'http://127.0.0.1:5000/ocr',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data
    ]);

      $response = curl_exec($curl);
      $error = curl_error($curl);
      curl_close($curl);

      if ($error) {
          ob_end_clean();
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

    // Insertion en base de données
    try {
        $est_restreint = ($provenance === 'AG') ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO archives (nom_fichier, chemin, provenance, contenu_textuel, est_restreint) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, 'uploads/' . $nom, $provenance, $result['contenu'], $est_restreint]);

       

    } catch (Exception $e) {
        http_response_code(500);
        echo "Erreur BDD : " . $e->getMessage();
    }
     if (isset($_POST['doc_id'])) {
            $stmt2 = $pdo->prepare("UPDATE documents SET etat = 'traite' WHERE id = ?");
            $stmt2->execute([$_POST['doc_id']]);
                    // ✅ Message + redirection
       ob_end_clean();
 echo "<script>
        alert('✅ Fichier archivé avec succès.');
        window.location.href = 'notifications.php';
        </script>";
        }
ob_end_flush();

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

