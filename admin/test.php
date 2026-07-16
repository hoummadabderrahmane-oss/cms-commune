<?php
echo "<h1>Test chemins</h1>";
echo "<p>__DIR__: " . __DIR__ . "</p>";
echo "<p>__FILE__: " . __FILE__ . "</p>";

$headerPath = __DIR__ . '/../includes/header.php';
echo "<p>Chemin header: " . $headerPath . "</p>";
echo "<p>Existe: " . (file_exists($headerPath) ? 'OUI' : 'NON') . "</p>";

if (file_exists($headerPath)) {
    echo "<p style='color:green;'>✅ header.php existe!</p>";
} else {
    echo "<p style='color:red;'>❌ header.php n'existe PAS!</p>";
    echo "<p>Vérifiez que le dossier includes/ existe à: " . dirname(__DIR__) . "/includes/</p>";
}