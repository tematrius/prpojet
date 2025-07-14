<?php session_start(); 
require 'includes/db.php';

$email = $_POST['email'] ?? ''; 
$password = $_POST['mot_de_passe'] ?? '';


 // Si déjà bloqué par session 
if (!empty($_SESSION['bloque']) && time() < $_SESSION['bloque_expire']) { 
    header('Location: index.php?error=Compte temporairement bloqué. Réessayez plus tard.'); 
    exit; 
}

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?'); 
$stmt->execute([$email]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC);


if ($user) { 
    // Si déjà bloqué en BDD 
if ($user['tentatives_login'] >= 5 && strtotime($user['dernier_echec']) > (time() - 900)) { 
    $_SESSION['bloque'] = true; 
    $_SESSION['bloque_expire'] = time() + 300; 
// 5 min 
    header('Location: index.php?error=Compte bloqué 5 min.'); 
    exit; 
} if (password_verify($password, $user['mot_de_passe'])) { 
    $pdo->prepare('UPDATE utilisateurs SET tentatives_login = 0, dernier_echec = NULL WHERE id = ?') ->execute([$user['id']]); 
    session_regenerate_id(true); 
    $_SESSION['user'] = [ 
    'id' => $user['id'], 
    'nom' => $user['nom'], 
    'email' => $user['email'], 
    'role' => $user['role'], 
    'ip' => $_SERVER['REMOTE_ADDR'], 
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] 
    ]; 
    $_SESSION['last_activity'] = time(); 
    unset($_SESSION['bloque'], $_SESSION['bloque_expire']); 
    if (!$user['a_change_mdp']) { 
        header('Location: changer-mdp.php'); 
    exit; 
} switch ($user['role']) {
     case 'ag': header("Location: ../www/admin/dashboard.php");
    break; 
    case 'secretaire': header("Location: ../www/secretaire/dashboard.php"); 
    break; 
    case 'employe': header("Location: ../www/employe/dashboard.php"); 
    break;
    default: header('Location: index.php?error=Rôle inconnu.'); 
} 
exit; 
} else { $pdo->prepare('UPDATE utilisateurs SET tentatives_login = tentatives_login + 1, dernier_echec = NOW() WHERE id = ?') ->execute([$user['id']]); 
header('Location: index.php?error=Email ou mot de passe incorrect.'); 
exit;
 } 
} else { 
header('Location: index.php?error=Identifiants invalides.'); 
exit; 
} 
?>