CREATE DATABASE IF NOT EXISTS sgc_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgc_db;

CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'agent') DEFAULT 'agent',
    commune VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    avatar VARCHAR(255) DEFAULT 'default.png',
    statut TINYINT(1) DEFAULT 1,
    derniere_connexion DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS citoyens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cin VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    nom_ar VARCHAR(100),
    prenom_ar VARCHAR(100),
    date_naissance DATE,
    lieu_naissance VARCHAR(100),
    sexe ENUM('M', 'F') NOT NULL,
    etat_civil ENUM('celibataire', 'marie', 'divorce', 'veuf') DEFAULT 'celibataire',
    adresse TEXT,
    quartier VARCHAR(100),
    telephone VARCHAR(20),
    email VARCHAR(150),
    profession VARCHAR(100),
    niveau_etude VARCHAR(100),
    situation_sociale ENUM('normal', 'handicap', 'veuf', 'orphelin', 'demuni') DEFAULT 'normal',
    nombre_enfants INT DEFAULT 0,
    photo VARCHAR(255),
    statut ENUM('actif', 'decede', 'demenage', 'inactif') DEFAULT 'actif',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citoyen_id INT NOT NULL,
    type_document ENUM('extrait_naissance', 'certificat_residence', 'attestation_mariage', 'certificat_deces', 'carte_identite', 'autre') NOT NULL,
    numero_document VARCHAR(50),
    fichier VARCHAR(255),
    date_emission DATE,
    date_expiration DATE,
    statut ENUM('valide', 'expire', 'annule') DEFAULT 'valide',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (citoyen_id) REFERENCES citoyens(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_activites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    action VARCHAR(50) NOT NULL,
    table_concernee VARCHAR(50),
    enregistrement_id INT,
    details TEXT,
    adresse_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, role, commune, statut) 
VALUES ('Admin', 'Principal', 'admin@commune.ma', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Commune Principale', 1);