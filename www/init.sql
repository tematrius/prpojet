-- 1. Créer la base de données
CREATE DATABASE IF NOT EXISTS bnb_archives CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bnb_archives;

-- 2. Table des utilisateurs
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('employe', 'secretaire', 'associé', 'ag') NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_creation VARCHAR(45),
    derniere_connexion DATETIME,
    tentatives_login INT DEFAULT 0,
    dernier_echec DATETIME,
    a_change_mdp BOOLEAN DEFAULT 0
);

-- 3. Table des documents (envoyés par les utilisateurs au secrétariat)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    type_mime VARCHAR(100),
    commentaire TEXT,
    provenance VARCHAR(50),
    etat ENUM('en_attente', 'traite') DEFAULT 'en_attente',
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    auteur_id INT,
    FOREIGN KEY (auteur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- 4. Table des archives (documents officiellement archivés)
CREATE TABLE archives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin VARCHAR(255) NOT NULL,
    provenance VARCHAR(50) NOT NULL,
    contenu_textuel LONGTEXT,
    est_restreint BOOLEAN DEFAULT 0,
    expiration_acces DATETIME NULL,
    nombre_telechargements INT DEFAULT 1,
    nb_vues INT DEFAULT 0,
    date_upload DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 5. Table des demandes d'accès
CREATE TABLE demandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_document INT NOT NULL,
    id_demandeur INT NOT NULL,
    commentaire TEXT,
    statut ENUM('en_attente', 'accepte', 'refuse') DEFAULT 'en_attente',
    motif_refus TEXT,
    soumis_ag BOOLEAN DEFAULT 0,
    date_post DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_reponse DATETIME NULL,
    id_ag INT NULL,
    FOREIGN KEY (id_document) REFERENCES archives(id) ON DELETE CASCADE,
    FOREIGN KEY (id_demandeur) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (id_ag) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- 6. Table des logs
CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50),
    target_id INT NULL,
    type_cible ENUM('utilisateur', 'document', 'archive', 'demande', 'autre') DEFAULT 'autre',
    status VARCHAR(20),
    message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id)
);

