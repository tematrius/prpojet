<?php
session_start();
require 'includes/db.php';
var_dump($_POST); // Debugging line to check POST data

$email = $_POST['email'] ?? '';
$password = $_POST['mot_de_passe'] ?? '';


$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);



if ($user) {
        // Blocage après 5 tentatives en moins de 15 min
        if ($user['tentatives_login'] >= 5 && strtotime($user['dernier_echec']) > (time() - 900)) {
            exit('Compte bloqué 15 min après trop de tentatives.');
        }
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Réinitialise tentatives
            $pdo->prepare('UPDATE utilisateurs SET tentatives_login = 0, dernier_echec = NULL WHERE id = ?')->execute([$user['id']]);
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
            // Changement de mot de passe obligatoire
            if (!$user['a_change_mdp']) {
                header('Location: changer-mdp.php');
                exit();
            }
            switch ($user['role']) {
            case 'ag':
                header("Location: ../www/admin/dashboard.php");
                break;
            case 'secretaire':
                header("Location: ../www/secretaire/dashboard.php");
                break;
            case 'employe':
                header("Location: ../www/employe/dashboard.php");
                break;
            default:
                echo "Rôle inconnu.";
        }

            exit;
        } else {
            // Incrémente tentatives et met à jour dernier_echec
            $pdo->prepare('UPDATE utilisateurs SET tentatives_login = tentatives_login + 1, dernier_echec = NOW() WHERE id = ?')->execute([$user['id']]);
            exit('Email ou mot de passe incorrect.');
        }
    } else {
        exit('Email ou mot de passe incorrect.');
    }
?>

