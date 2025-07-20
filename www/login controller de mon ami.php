<?php

require_once '../config/database.php';
require_once '../core/middleware.php';

initializeSecureSession();

function redirectWithMessage($msg, $type = 'danger', $extra = []) {
    $_SESSION['message'] = $msg;
    $_SESSION['message_type'] = $type;
    foreach ($extra as $key => $value) {
        $_SESSION[$key] = $value;
    }
    header("Location: ../views/auth/logins.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    logSecurityEvent("Tentative CSRF sur login.");
    redirectWithMessage("⛔ Erreur de sécurité : Tentative CSRF sur login.");
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['mot_de_passe'] ?? '';

if (empty($email) || empty($password)) {
    redirectWithMessage("❌ Tous les champs sont obligatoires.");
}



$stmt = $db->prepare("SELECT u.*, r.nom_role, r.id_role FROM utilisateurs u JOIN roles r ON u.id_role = r.id_role WHERE u.email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$ip = getIpAddress();
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$now = date('Y-m-d H:i:s');

// Si utilisateur inexistant
if (!$user) {
    enregistrerTentativeLogin($db, $email, $ip, 0, null, $user_agent);
    logSecurityEvent("Tentative de connexion echoué avec un email inexistant ($email) depuis $ip", $email, null);
  
    redirectWithMessage("❌ Email ou mot de passe incorrect.");
}

// Compte déjà désactivé
if ($user['status'] !== 'actif') {
    
    logSecurityEvent("Connexion bloquée : utilisateur $email est inactif.", $email, $user['id_utilisateur']);
    redirectWithMessage("⛔ Compte bloqué. Contactez l’administrateur.");
}

// Tentatives échouées dans les 10 dernières minutes
$stmt = $db->prepare("SELECT attempt_time FROM login_attempts 
    WHERE email = ? AND success = 0 AND handled = 0 AND attempt_time > (NOW() - INTERVAL 30 MINUTE)
    ORDER BY attempt_time DESC");
$stmt->execute([$email]);
$failures = $stmt->fetchAll(PDO::FETCH_COLUMN);
$failureCount = count($failures);

$nowTime = time();

if ($failureCount >= 15) {
    // 🔴 Blocage dur après 15 tentatives
    $db->prepare("UPDATE utilisateurs SET status = 'inactif' WHERE email = ?")->execute([$email]);
    // Marquer les tentatives comme traitées
    $db->prepare("UPDATE login_attempts SET handled = 1 WHERE email = ? AND success = 0 AND handled = 0")->execute([$email]);
    enregistrerTentativeLogin($db, $email, $ip, 0, $user['id_utilisateur'], $user_agent);
    logSecurityEvent("🚫 Compte désactivé automatiquement pour $email après 15 tentatives.", $email, $user['id_utilisateur']);
    redirectWithMessage("⛔ Votre compte a été désactivé après plusieurs échecs. Contactez l’administrateur.");


} elseif ($failureCount >= 10) {
    $lastAttemptTime = strtotime($failures[0]);
    $blockDuration = 300; // 4 minutes
    $remaining = $blockDuration - ($nowTime - $lastAttemptTime);
    if ($remaining > 0) {
        $_SESSION['remaining_time'] = $remaining;

        logSecurityEvent("⏳ Blocage 4 min pour $email après 10 tentatives.", $email, $user['id_utilisateur']);
        redirectWithMessage("⏳ Trop de tentatives. Réessayez dans $remaining secondes...");
    } else {
         // Marquer les tentatives comme traitées
    $db->prepare("UPDATE login_attempts SET handled = 1 WHERE email = ? AND success = 0 AND handled = 0")->execute([$email]);
    
    unset($_SESSION['remaining_time']);
    }
} elseif ($failureCount >= 5) {
    $lastAttemptTime = strtotime($failures[0]);
    $blockDuration = 120; // 2 minutes
    $remaining = $blockDuration - ($nowTime - $lastAttemptTime);
    if ($remaining > 0) {
        $_SESSION['remaining_time'] = $remaining;

          logSecurityEvent("⏳ Blocage 2 min pour $email après 5 tentatives.", $email, $user['id_utilisateur']);
        redirectWithMessage("⏳ Trop de tentatives. Réessayez dans $remaining secondes...");
    } else {
        // Marquer les tentatives comme traitées
    $db->prepare("UPDATE login_attempts SET handled = 1 WHERE email = ? AND success = 0 AND handled = 0")->execute([$email]);
    unset($_SESSION['remaining_time']);
    }
} else {
    unset($_SESSION['remaining_time']);
}

// Vérifie les échecs dans les 30 dernières minutes pour désactivation par cycle
$stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts 
    WHERE email = ? AND success = 0 AND attempt_time > (NOW() - INTERVAL 30 MINUTE)");
$stmt->execute([$email]);
$totalFailures = $stmt->fetchColumn();

if ($totalFailures >= 15) {
    $db->prepare("UPDATE utilisateurs SET status = 'inactif' WHERE email = ?")->execute([$email]);
     // Marquer les tentatives comme traitées
     $db->prepare("UPDATE login_attempts SET handled = 1 WHERE email = ? AND success = 0 AND handled = 0")->execute([$email]);
    
    logSecurityEvent("🚫 Compte désactivé automatiquement (15 tentatives sur 30 min) pour $email.", $email, $user['id_utilisateur']);
    redirectWithMessage("⛔ Votre compte a été désactivé après plusieurs tentatives échouées.");
}

// Vérification mot de passe
if (!password_verify($password, $user['mot_de_passe'])) {
    enregistrerTentativeLogin($db, $email, $ip, 0, $user['id_utilisateur'], $user_agent);
    logSecurityEvent("Mot de passe incorrect pour $email ($ip)", $email, $user['id_utilisateur']);
    redirectWithMessage("❌ Email ou mot de passe incorrect.");
}

$_SESSION['user'] = [
    'id_utilisateur' => $user['id_utilisateur'],
    'email' => $user['email'],
    'prenom' => $user['prenom'],
    'id_role' => $user['id_role'],
    'nom' => $user['nom'],
    'nom_role' => $user['nom_role']
    
];


// Met à jour le statut de l'utilisateur
$db->prepare("UPDATE utilisateurs SET is_active = 1 WHERE id_utilisateur = ?")
    ->execute([$user['id_utilisateur']]);



// Supprime les tentatives précédentes
enregistrerTentativeLogin($db, $email, $ip, 1, $user['id_utilisateur'], $user_agent);
logSecurityEvent("✅ Connexion réussie pour $email depuis $ip", $email, $user['id_utilisateur']);


$db->prepare("DELETE FROM login_attempts WHERE email = ?")->execute([$email]);

// Redirection selon rôle
switch (strtolower($user['nom_role'])) {
    case 'admin':
        header("Location: ../views/admin/index-2.php");
        break;
    case 'enseignant':
        header("Location: ../views/enseignants/index-2.php");
        break;
    case 'élève':
        header("Location: ../views/eleves/dash.php");
        break;
    default:
        header("Location: ../index.php");
        break;
}
exit;

// Enregistrement des tentatives
function enregistrerTentativeLogin($db, $email, $ip, $success, $user_id, $user_agent) {
    $time = date('Y-m-d H:i:s');
    $stmt = $db->prepare("INSERT INTO login_attempts 
        (email, ip_address, attempt_time, success, id_utilisateur, user_agent, handled)
        VALUES (?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$email, $ip, $time, $success, $user_id, $user_agent]);
}


?>