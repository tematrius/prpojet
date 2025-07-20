<?php
session_start();
require 'includes/db.php';
require 'includes/log.php';
date_default_timezone_set('Africa/Kinshasa');


// Vérification CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-shield-lock me-2"></i> <strong>Erreur de sécurité : CSRF token invalide.</strong></div>';
    header('Location: index.php');
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['mot_de_passe'] ?? '';

// Récupère l'utilisateur
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$now = time();

// Vérifie si le compte existe
if ($user) {
    // Vérifie si le compte est actif
    if (isset($user['is_active']) && !$user['is_active']) {
        add_log('login_bloque', $user['id'], '', 'user', $user['id'], 'bloque', 'Compte désactivé', $_SERVER['REMOTE_ADDR']);
        $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i> <strong>Compte désactivé !</strong> Contactez l’administrateur.</div>';
        header('Location: index.php');
        exit;
    }

    // Enregistre la tentative dans login_attempts
    $stmtAttempt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, attempt_time, success, id_utilisateur, user_agent) VALUES (?, ?, NOW(), ?, ?, ?)');

    // Vérifie le blocage progressif
    $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND handled = 0 AND attempt_time > (NOW() - INTERVAL 30 MINUTE)');
    $stmtCount->execute([$email]);
    $failures = $stmtCount->fetchColumn();

    // Blocage temporaire après 5 échecs
    if ($failures == 5 && $failures < 8) {
        $_SESSION['bloque'] = true;
        $_SESSION['bloque_expire'] = time() + 300; // 5 minutes
        add_log('login_bloque', $user['id'], '', 'user', $user['id'], 'bloque', 'Blocage temporaire après 5 échecs', $_SERVER['REMOTE_ADDR']);
        $stmtAttempt->execute([$email, $_SERVER['REMOTE_ADDR'], 0, $user['id'], $_SERVER['HTTP_USER_AGENT']]);
        header('Location: index.php');
        exit;
    }
    // Blocage long après 6-7 échecs
    if ($failures >= 8) {
        $pdo->prepare('UPDATE utilisateurs SET is_active = 0 WHERE id = ?')->execute([$user['id']]);
        add_log('login_bloque', $user['id'], '', 'user', $user['id'], 'bloque', 'Blocage définitif après 8 échecs', $_SERVER['REMOTE_ADDR']);
        $stmtAttempt->execute([$email, $_SERVER['REMOTE_ADDR'], 0, $user['id'], $_SERVER['HTTP_USER_AGENT']]);
        $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-lock-fill me-2"></i> <strong>Compte désactivé !</strong> Contactez l’administrateur.</div>';
        header('Location: index.php');
        exit;
    }

    // Vérification du mot de passe
    if (password_verify($password, $user['mot_de_passe'])) {
        $stmtAttempt->execute([$email, $_SERVER['REMOTE_ADDR'], 1, $user['id'], $_SERVER['HTTP_USER_AGENT']]);
        add_log('login_succes', $user['id'], '', 'user', $user['id'], 'succes', 'Connexion réussie', $_SERVER['REMOTE_ADDR']);
        // Marque toutes les tentatives comme handled
        $pdo->prepare('UPDATE login_attempts SET handled = 1 WHERE email = ? AND success = 0 AND handled = 0')->execute([$email]);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nom' => $user['nom'],
            'email' => $user['email'],
            'role' => $user['role'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'a_change_mdp' => $user['a_change_mdp']
        ];
        $_SESSION['last_activity'] = $now;
        // Redirection vers changement de mot de passe si première connexion
        if ($user['a_change_mdp'] == 0) {
            header('Location: changer-mdp.php');
            exit;
        }
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
        $stmtAttempt->execute([$email, $_SERVER['REMOTE_ADDR'], 0, $user['id'], $_SERVER['HTTP_USER_AGENT']]);
        add_log('login_echec', $user['id'], '', 'user', $user['id'], 'echec', 'Mot de passe incorrect', $_SERVER['REMOTE_ADDR']);
        $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Email ou mot de passe incorrect.</strong></div>';
        header('Location: index.php');
        exit;
    }
} else {
    // Log tentative avec email inexistant
    $logStmt = $pdo->prepare('INSERT INTO logs (action, statut, message, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?, ?, NOW())');
    $logStmt->execute([
        'login_echec',
        'echec',
        'Tentative de connexion avec email inexistant: ' . htmlspecialchars($email),
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    $_SESSION['login_message'] = '<div class="alert alert-danger d-flex align-items-center"><i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Email ou mot de passe incorrect.</strong></div>';
    header('Location: index.php');
    exit;
}