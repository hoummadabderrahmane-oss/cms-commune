<?php
/**
 * ============================================
 * CMS Baladiya - Tableau de bord
 * ============================================
 */
define('SGC_ACCESS', true);

// Chemin absolu vers la racine du projet
$basePath = realpath(dirname(__DIR__));

// Vérifier si config/database.php existe
if (!file_exists($basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php')) {
    die("<h1>❌ Erreur</h1>
    <p>Fichier config/database.php non trouvé à: " . $basePath . "\\config\\database.php</p>
    <p>Vérifiez que le fichier existe.</p>");
}

define('BASE_PATH', $basePath);

require_once BASE_PATH . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'auth_check.php';
require_once BASE_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

global $currentUser;

$pageTitle = 'Tableau de bord';
$pageIcon = 'fa-tachometer-alt';

try {
    $db = getDB();
    
    $stats = [
        'total_citoyens' => $db->query("SELECT COUNT(*) FROM citoyens WHERE statut = 'actif'")->fetchColumn(),
        'total_hommes' => $db->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'M' AND statut = 'actif'")->fetchColumn(),
        'total_femmes' => $db->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'F' AND statut = 'actif'")->fetchColumn(),
        'total_documents' => $db->query("SELECT COUNT(*) FROM documents WHERE statut = 'valide'")->fetchColumn()
    ];
    
    $stmt = $db->query("
        SELECT c.*, u.prenom as agent_prenom, u.nom as agent_nom 
        FROM citoyens c 
        LEFT JOIN utilisateurs u ON c.created_by = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $derniersCitoyens = $stmt->fetchAll();
    
    $stmt = $db->query("
        SELECT quartier, COUNT(*) as total 
        FROM citoyens 
        WHERE statut = 'actif' AND quartier IS NOT NULL AND quartier != ''
        GROUP BY quartier 
        ORDER BY total DESC 
        LIMIT 6
    ");
    $quartiers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $stats = ['total_citoyens' => 0, 'total_hommes' => 0, 'total_femmes' => 0, 'total_documents' => 0];
    $derniersCitoyens = [];
    $quartiers = [];
}

// Chemins absolus pour includes
$includesPath = BASE_PATH . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;

require_once $includesPath . 'header.php';
require_once $includesPath . 'sidebar.php';
require_once $includesPath . 'navbar.php';
?>

<div class="main-content">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 font-weight-bold">Tableau de bord</h4>
            <p class="text-muted mb-0">Bienvenue, <?= htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom']) ?></p>
        </div>
        <div class="text-muted small">
            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y') ?>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon green mr-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($stats['total_citoyens']) ?></div>
                        <div class="stat-label">Citoyens actifs</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon blue mr-3">
                        <i class="fas fa-male"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($stats['total_hommes']) ?></div>
                        <div class="stat-label">Hommes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon orange mr-3">
                        <i class="fas fa-female"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($stats['total_femmes']) ?></div>
                        <div class="stat-label">Femmes</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon red mr-3">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="stat-number"><?= number_format($stats['total_documents']) ?></div>
                        <div class="stat-label">Documents valides</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Graphique -->
        <div class="col-lg-8 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar mr-2 text-success"></i>Répartition par quartier</h5>
                </div>
                <div class="p-4">
                    <?php if (empty($quartiers)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="quartierChart" height="250"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Derniers citoyens -->
        <div class="col-lg-4 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-clock mr-2 text-success"></i>Derniers ajouts</h5>
                    <a href="../citizens/index.php" class="text-success small">Voir tout</a>
                </div>
                <div class="p-0">
                    <?php if (empty($derniersCitoyens)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            <p>Aucun citoyen enregistré</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($derniersCitoyens as $c): ?>
                                <a href="../citizens/show.php?id=<?= $c['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center py-3">
                                    <div class="user-avatar mr-3 flex-shrink-0" style="width:42px;height:42px;font-size:0.85rem;">
                                        <?= strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="font-weight-bold text-truncate"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($c['quartier'] ?? 'N/A') ?>
                                        </small>
                                    </div>
                                    <small class="text-muted flex-shrink-0 ml-2"><?= date('d/m', strtotime($c['created_at'])) ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="row">
        <div class="col-12">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-bolt mr-2 text-success"></i>Actions rapides</h5>
                </div>
                <div class="p-4">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="../citizens/ajouter.php" class="btn btn-outline-success btn-block py-3">
                                <i class="fas fa-user-plus fa-lg mb-2 d-block"></i>
                                Ajouter un citoyen
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-outline-primary btn-block py-3">
                                <i class="fas fa-file-alt fa-lg mb-2 d-block"></i>
                                Nouveau document
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-outline-info btn-block py-3">
                                <i class="fas fa-chart-pie fa-lg mb-2 d-block"></i>
                                Voir les statistiques
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-outline-warning btn-block py-3">
                                <i class="fas fa-cog fa-lg mb-2 d-block"></i>
                                Paramètres
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    <?php if (!empty($quartiers)): ?>
    const ctx = document.getElementById('quartierChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($quartiers, 'quartier')) ?>,
            datasets: [{
                label: 'Nombre de citoyens',
                data: <?= json_encode(array_column($quartiers, 'total')) ?>,
                backgroundColor: [
                    'rgba(26, 95, 42, 0.8)',
                    'rgba(45, 138, 62, 0.8)',
                    'rgba(76, 175, 80, 0.8)',
                    'rgba(129, 199, 132, 0.8)',
                    'rgba(165, 214, 167, 0.8)',
                    'rgba(200, 230, 201, 0.8)'
                ],
                borderColor: '#1a5f2a',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    },
                    gridLines: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                }],
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }]
            }
        }
    });
    <?php endif; ?>
</script>

<?php
require_once $includesPath . 'footer.php';
?>