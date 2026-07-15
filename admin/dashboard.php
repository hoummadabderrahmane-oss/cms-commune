<?php
/**
 * ============================================
 * SGC - Tableau de bord
 * ============================================
 */
define('SGC_ACCESS', true);
require_once '../auth/auth_check.php';
require_once '../config/database.php';

$pageTitle = 'Tableau de bord';
$pageIcon = 'fa-tachometer-alt';

try {
    $db = getDB();
    
    // Statistiques
    $stats = [
        'total_citoyens' => $db->query("SELECT COUNT(*) FROM citoyens WHERE statut = 'actif'")->fetchColumn(),
        'total_hommes' => $db->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'M' AND statut = 'actif'")->fetchColumn(),
        'total_femmes' => $db->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'F' AND statut = 'actif'")->fetchColumn(),
        'total_documents' => $db->query("SELECT COUNT(*) FROM documents WHERE statut = 'valide'")->fetchColumn(),
        'nouveaux_mois' => $db->query("SELECT COUNT(*) FROM citoyens WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn()
    ];
    
    // Derniers citoyens ajoutés
    $stmt = $db->query("
        SELECT c.*, u.prenom as agent_prenom, u.nom as agent_nom 
        FROM citoyens c 
        LEFT JOIN utilisateurs u ON c.created_by = u.id 
        ORDER BY c.created_at DESC 
        LIMIT 5
    ");
    $derniersCitoyens = $stmt->fetchAll();
    
    // Citoyens par quartier (pour le graphique)
    $stmt = $db->query("
        SELECT quartier, COUNT(*) as total 
        FROM citoyens 
        WHERE statut = 'actif' AND quartier IS NOT NULL 
        GROUP BY quartier 
        ORDER BY total DESC 
        LIMIT 6
    ");
    $quartiers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur dashboard: " . $e->getMessage());
    $stats = ['total_citoyens' => 0, 'total_hommes' => 0, 'total_femmes' => 0, 'total_documents' => 0, 'nouveaux_mois' => 0];
    $derniersCitoyens = [];
    $quartiers = [];
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/navbar.php';
?>

<div class="main-content">
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_citoyens']) ?></div>
                <div class="stat-label">Citoyens actifs</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-male"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_hommes']) ?></div>
                <div class="stat-label">Hommes</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-female"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_femmes']) ?></div>
                <div class="stat-label">Femmes</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_documents']) ?></div>
                <div class="stat-label">Documents valides</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Graphique -->
        <div class="col-lg-8">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5><i class="fas fa-chart-pie me-2 text-success"></i>Répartition par quartier</h5>
                </div>
                <div class="p-4">
                    <canvas id="quartierChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Derniers citoyens -->
        <div class="col-lg-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5><i class="fas fa-clock me-2 text-success"></i>Derniers ajouts</h5>
                </div>
                <div class="p-0">
                    <?php if (empty($derniersCitoyens)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>Aucun citoyen enregistré</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($derniersCitoyens as $c): ?>
                                <div class="list-group-item d-flex align-items-center py-3">
                                    <div class="user-avatar me-3 flex-shrink-0" style="width: 42px; height: 42px; font-size: 0.85rem;">
                                        <?= strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($c['quartier'] ?? 'N/A') ?>
                                            <span class="mx-1">•</span>
                                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($c['agent_prenom'] . ' ' . $c['agent_nom']) ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?= date('d/m/Y', strtotime($c['created_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Graphique des quartiers
    const ctx = document.getElementById('quartierChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($quartiers, 'quartier')) ?>,
            datasets: [{
                label: 'Nombre de citoyens',
                data: <?= json_encode(array_column($quartiers, 'total')) ?>,
                backgroundColor: 'rgba(26, 95, 42, 0.8)',
                borderColor: '#1a5f2a',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>