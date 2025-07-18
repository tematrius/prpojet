<?php 
session_start(); 
require 'includes/db.php'; 
require 'includes/log.php';
date_default_timezone_set('Africa/Kinshasa');


$email = $_POST['email'] ?? ''; 
$password = $_POST['mot_de_passe'] ?? ''; 

// Récupère l'utilisateur 
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?'); 
$stmt->execute([$email]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC); 


$now = time(); 


if ($user) { 
    if ($user['tentatives_login'] >= 5 && strtotime($user['bloque_jusqu']) > $now) {
        $_SESSION['bloque'] = true;
        $_SESSION['bloque_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i> <strong>Compte bloqué !</strong> Réessayez dans <span id="timer" class="badge bg-warning text-dark"></span>.</div>';
        $_SESSION['bloque_expire'] = strtotime($user['bloque_jusqu']);
        // Journalise le dernier échec
        $pdo->prepare('UPDATE utilisateurs SET dernier_echec = NOW() WHERE id = ?')->execute([$user['id']]);
        header('Location: index.php');
        exit;
    } 
    if (password_verify($password, $user['mot_de_passe'])) { 
        add_log('login_succes', $user['id'], '', 'user', $user['id'], 'succes', 'Connexion réussie', $_SERVER['REMOTE_ADDR']);
        $pdo->prepare('UPDATE utilisateurs SET tentatives_login = 0, bloque_jusqu = NULL WHERE id = ?') ->execute([$user['id']]); 
        session_regenerate_id(true); 
        $_SESSION['user'] = [ 
        'id' => $user['id'], 
        'nom' => $user['nom'], 
        'email' => $user['email'], 
        'role' => $user['role'], 
        'ip' => $_SERVER['REMOTE_ADDR'], 
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] 
        ]; 
        $_SESSION['last_activity'] = $now; 
        // Redirection selon le rôle 
        switch ($user['role']) { 
            case 'ag': 
                header("Location: admin/dashboard.php"); 
                break; 
            case 'secretaire': 
                header("Location: secretaire/dashboard.php"); 
                break; 
            case 'employe': 
                header("Location: employe/dashboard.php"); 
                break;
            case 'superadmin': 
                header("Location: superadmin/dashboard.php"); 
                break;                 
            default: 
                header("Location: index.php"); 
        } 
        exit; 
    } else { 
    // Blocage après 5 tentatives pour 5 minutes
    if ($user['tentatives_login'] >= 5 && strtotime($user['bloque_jusqu']) > $now) {
        add_log('login_bloque', $user['id'], '', 'user', $user['id'], 'bloque', 'Compte bloqué', $_SERVER['REMOTE_ADDR']);
        $_SESSION['bloque'] = true;
        $_SESSION['bloque_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i> <strong>Compte bloqué !</strong> Réessayez dans <span id="timer" class="badge bg-warning text-dark"></span>.</div>';
        $_SESSION['bloque_expire'] = strtotime($user['bloque_jusqu']);
        $pdo->prepare('UPDATE utilisateurs SET dernier_echec = NOW() WHERE id = ?')->execute([$user['id']]);
        header('Location: index.php');
        exit;
    }
    if (password_verify($password, $user['mot_de_passe'])) {
        add_log('login_succes', $user['id'], '', 'user', $user['id'], 'succes', 'Connexion réussie', $_SERVER['REMOTE_ADDR']);
        $pdo->prepare('UPDATE utilisateurs SET tentatives_login = 0, bloque_jusqu = NULL WHERE id = ?')->execute([$user['id']]);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nom' => $user['nom'],
            'email' => $user['email'],
            'role' => $user['role'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        $_SESSION['last_activity'] = $now;
        // Redirection selon le rôle
        switch ($user['role']) {
            case 'ag':
                header("Location: admin/dashboard.php");
                break;
            case 'secretaire':
                header("Location: secretaire/dashboard.php");
                break;
            case 'employe':
                header("Location: employe/dashboard.php");
                break;
            case 'superadmin':
                header("Location: superadmin/dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit;
    } else {
        add_log('login_echec', $user['id'], '', 'user', $user['id'], 'echec', 'Mot de passe incorrect', $_SERVER['REMOTE_ADDR']);
        $tentatives = $user['tentatives_login'] + 1; 
        // Journalise le dernier échec
        $pdo->prepare('UPDATE utilisateurs SET tentatives_login = ?, dernier_echec = NOW() WHERE id = ?') ->execute([$tentatives, $user['id']]); 
        if ($tentatives >= 5) { 
            $bloque_jusqu = date('Y-m-d H:i:s', time() + 300); // 5 minutes (format DATETIME)
            $pdo->prepare('UPDATE utilisateurs SET bloque_jusqu = ? WHERE id = ?')->execute([$bloque_jusqu, $user['id']]);
            $_SESSION['bloque'] = true;
            $_SESSION['bloque_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i> <strong>Compte bloqué !</strong> Réessayez dans <span id="timer" class="badge bg-warning text-dark"></span>.</div>';
            $_SESSION['bloque_expire'] = strtotime($bloque_jusqu);
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Email ou mot de passe incorrect.</strong></div>';
            header('Location: index.php');
            exit;
        }
} 
    }
} else { 
    $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Email ou mot de passe incorrect.</strong></div>'; 
    header('Location: index.php'); 
    exit; 
} 
?>