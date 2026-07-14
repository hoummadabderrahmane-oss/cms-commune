<?php
/**
 * ============================================
 * SGC - Configuration de la base de données (XAMPP)
 * ============================================
 */

// Empêcher l'accès direct
if (!defined('SGC_ACCESS')) {
    define('SGC_ACCESS', true);
}

// Paramètres de connexion XAMPP (par défaut)
define('DB_HOST', 'localhost');
define('DB_NAME', 'sgc_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // ← XAMPP: root sans mot de passe par défaut
define('DB_CHARSET', 'utf8mb4');

// Options PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
];

/**
 * Obtenir la connexion PDO
 * @return PDO
 */
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $GLOBALS['options']);
        } catch (PDOException $e) {
            error_log("Erreur connexion DB: " . $e->getMessage());
            die("
                <div style='font-family:Segoe UI;padding:40px;text-align:center;'>
                    <h2 style='color:#d32f2f;'>❌ Erreur de connexion à la base de données</h2>
                    <p style='font-size:16px;color:#666;'>
                        Vérifiez que:<br><br>
                        1. XAMPP est démarré (Apache + MySQL)<br>
                        2. La base de données <b>sgc_db</b> existe<br>
                        3. Le fichier <b>database.sql</b> a été importé dans PHPMyAdmin<br><br>
                        <a href='http://localhost/phpmyadmin' target='_blank' style='color:#1a5f2a;'>Ouvrir PHPMyAdmin</a>
                    </p>
                </div>
            ");
        }
    }
    
    return $pdo;
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
/**
 * Fonction utilitaire: Logger une activité
 */
function logActivity(string $action, string $table = null, int $recordId = null, string $details = null): void {
    $db = getDB();
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        $stmt = $db->prepare("
            INSERT INTO journal_activites (utilisateur_id, action, table_concernee, enregistrement_id, details, adresse_ip)
            VALUES (:user_id, :action, :table, :record_id, :details, :ip)
        ");
        
        $stmt->execute([
            ':user_id'    => $userId,
            ':action'     => $action,
            ':table'      => $table,
            ':record_id'  => $recordId,
            ':details'    => $details,
            ':ip'         => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Erreur log: " . $e->getMessage());
    }
}