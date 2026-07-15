<?php
/**
 * ============================================
  * SGC - Installation automatique
   * ============================================
    */
    define('SGC_ACCESS', true);

    // Paramètres de connexion
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbName = 'sgc_db';

    try {
        // Connexion sans base de données
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                        ]);
                            
                                // Créer la base de données
                                    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                        $pdo->exec("USE $dbName");
                                            
                                                // Lire et exécuter database.sql
                                                    $sql = file_get_contents('database.sql');
                                                        
                                                            // Supprimer "CREATE DATABASE" et "USE" car déjà fait
                                                                $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
                                                                    $sql = preg_replace('/USE .*?;/i', '', $sql);
                                                                        
                                                                            $pdo->exec($sql);
                                                                                
                                                                                    echo "<h1>✅ Installation réussie!</h1>";
                                                                                        echo "<p>La base de données <strong>$dbName</strong> a été créée avec succès.</p>";
                                                                                            echo "<p><a href='index.php' style='color:#1a5f2a;font-size:18px;'>Accéder au système →</a></p>";
                                                                                                echo "<hr>";
                                                                                                    echo "<p><strong>Login par défaut:</strong><br>";
                                                                                                        echo "Email: admin@commune.ma<br>";
                                                                                                            echo "Password: password</p>";
                                                                                                                
                                                                                                                } catch (PDOException $e) {
                                                                                                                    die("<h1>❌ Erreur d'installation</h1><p>" . $e->getMessage() . "</p>");
                                                                                                                    }
                                                                                                                    