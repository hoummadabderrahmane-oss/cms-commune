<?php
/**
 * ============================================
 * CMS Baladiya - Statistiques
 * ============================================
 */
define('SGC_ACCESS', true);

$basePath = dirname(__DIR__);
define('BASE_PATH', $basePath);

require_once BASE_PATH . '/auth/auth_check.php';
require_once BASE_PATH . '/config/database.php';

global $currentUser;

$pageTitle = 'Statistiques';
$pageIcon = 'fa-chart-bar';

try {
    $db = getDB();

    // ===== CITOYENS =====
    $totalCitoyens = $db->query("SELECT COUNT(*) FROM citoyens WHERE statut = 'actif'")->fetchColumn();
    $totalHommes = $db->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'M' AND statut = 'actif'")->fetchColumn();
    $totalFemmes = $db->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'F' AND statut = 'actif'")->fetchColumn();
    $totalInactifs = $db->query("SELECT COUNT(*) FROM citoyens WHERE statut = 'inactif'")->fetchColumn();

    // Répartition par sexe
    $sexeData = [
        'labels' => ['Hommes', 'Femmes'],
        'data' => [$totalHommes, $totalFemmes],
        'colors' => ['#3498db', '#e91e63']
    ];

    // Répartition par âge
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) < 18 THEN 'Moins de 18'
                WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 18 AND 30 THEN '18-30 ans'
                WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 31 AND 45 THEN '31-45 ans'
                WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 46 AND 60 THEN '46-60 ans'
                ELSE 'Plus de 60'
            END as tranche,
            COUNT(*) as total
        FROM citoyens
        WHERE date_naissance IS NOT NULL AND statut = 'actif'
        GROUP BY tranche
        ORDER BY 
            FIELD(tranche, 'Moins de 18', '18-30 ans', '31-45 ans', '46-60 ans', 'Plus de 60')
    ");
    $ageGroups = $stmt->fetchAll();

    // Répartition par quartier
    $stmt = $db->query("
        SELECT quartier, COUNT(*) as total 
        FROM citoyens 
        WHERE statut = 'actif' AND quartier IS NOT NULL AND quartier != '' 
        GROUP BY quartier 
        ORDER BY total DESC
    ");
    $quartiers = $stmt->fetchAll();

    // Évolution mensuelle (12 derniers mois)
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mois,
            DATE_FORMAT(created_at, '%m/%Y') as mois_label,
            COUNT(*) as total
        FROM citoyens
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY mois
        ORDER BY mois
    ");
    $evolution = $stmt->fetchAll();

    // ===== DOCUMENTS =====
    $totalDocuments = $db->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    $docsValides = $db->query("SELECT COUNT(*) FROM documents WHERE statut = 'valide'")->fetchColumn();
    $docsEnAttente = $db->query("SELECT COUNT(*) FROM documents WHERE statut = 'en_attente'")->fetchColumn();
    $docsRejetes = $db->query("SELECT COUNT(*) FROM documents WHERE statut = 'rejete'")->fetchColumn();

    // Documents par type
    $stmt = $db->query("
        SELECT type, COUNT(*) as total 
        FROM documents 
        GROUP BY type 
        ORDER BY total DESC
    ");
    $docTypes = $stmt->fetchAll();

    // Documents par mois
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as mois,
            DATE_FORMAT(created_at, '%m/%Y') as mois_label,
            COUNT(*) as total
        FROM documents
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY mois
        ORDER BY mois
    ");
    $docsParMois = $stmt->fetchAll();

    // ===== UTILISATEURS =====
    $totalAgents = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'")->fetchColumn();
    $totalAdmins = $db->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin' AND statut = 'actif'")->fetchColumn();

    // Activité récente
    $stmt = $db->query("
        SELECT 
            u.prenom, u.nom, u.role,
            COUNT(c.id) as citoyens_crees
        FROM utilisateurs u
        LEFT JOIN citoyens c ON c.created_by = u.id
        WHERE u.statut = 'actif'
        GROUP BY u.id
        ORDER BY citoyens_crees DESC
        LIMIT 5
    ");
    $topAgents = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erreur base de données: " . $e->getMessage());
}

$includesPath = BASE_PATH . '/includes/';
require_once $includesPath . 'header.php';
require_once $includesPath . 'sidebar.php';
require_once $includesPath . 'navbar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 font-weight-bold">Statistiques</h4>
            <p class="text-muted mb-0">Vue d'ensemble du système</p>
        </div>
        <div class="text-muted small">
            <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card text-center">
                <div class="stat-number text-success"><?= number_format($totalCitoyens) ?></div>
                <div class="stat-label">Citoyens</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card text-center">
                <div class="stat-number text-primary"><?= number_format($totalHommes) ?></div>
                <div class="stat-label">Hommes</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card text-center">
                <div class="stat-number" style="color:#e91e63"><?= number_format($totalFemmes) ?></div>
                <div class="stat-label">Femmes</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card text-center">
                <div class="stat-number text-warning"><?= number_format($totalDocuments) ?></div>
                <div class="stat-label">Documents</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card text-center">
                <div class="stat-number text-info"><?= number_format($totalAgents) ?></div>
                <div class="stat-label">Agents</div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="stat-card text-center">
                <div class="stat-number text-danger"><?= number_format($totalInactifs) ?></div>
                <div class="stat-label">Inactifs</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Répartition par sexe -->
        <div class="col-lg-4 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-venus-mars mr-2 text-primary"></i>Répartition par sexe</h5>
                </div>
                <div class="p-4">
                    <canvas id="sexeChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- Répartition par âge -->
        <div class="col-lg-4 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-birthday-cake mr-2 text-warning"></i>Répartition par âge</h5>
                </div>
                <div class="p-4">
                    <?php if (empty($ageGroups)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-bar fa-3x mb-3"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="ageChart" height="220"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statut des documents -->
        <div class="col-lg-4 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt mr-2 text-success"></i>Statut des documents</h5>
                </div>
                <div class="p-4">
                    <canvas id="docStatusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Évolution citoyens -->
        <div class="col-lg-8 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line mr-2 text-info"></i>Évolution des citoyens (12 derniers mois)</h5>
                </div>
                <div class="p-4">
                    <?php if (empty($evolution)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="evolutionChart" height="180"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top agents -->
        <div class="col-lg-4 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-trophy mr-2 text-warning"></i>Top agents</h5>
                </div>
                <div class="p-0">
                    <?php if (empty($topAgents)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                            <p>Aucune donnée</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topAgents as $i => $agent): ?>
                                <div class="list-group-item d-flex align-items-center py-3">
                                    <div class="mr-3" style="width:30px;text-align:center;font-weight:bold;color:<?= $i < 3 ? '#1a5f2a' : '#999' ?>">
                                        <?= $i + 1 ?>
                                    </div>
                                    <div class="user-avatar mr-3 flex-shrink-0" style="width:38px;height:38px;font-size:0.8rem;">
                                        <?= strtoupper(substr($agent['prenom'], 0, 1) . substr($agent['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="font-weight-bold text-truncate"><?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?></div>
                                        <small class="text-muted"><?= ucfirst($agent['role']) ?></small>
                                    </div>
                                    <span class="badge badge-success"><?= $agent['citoyens_crees'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Répartition par quartier -->
        <div class="col-lg-6 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt mr-2 text-danger"></i>Répartition par quartier</h5>
                </div>
                <div class="p-4">
                    <?php if (empty($quartiers)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-map fa-3x mb-3"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="quartierChart" height="250"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents par type -->
        <div class="col-lg-6 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-folder-open mr-2 text-primary"></i>Documents par type</h5>
                </div>
                <div class="p-4">
                    <?php if (empty($docTypes)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-folder fa-3x mb-3"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="docTypeChart" height="250"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau récapitulatif documents -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="data-table-card">
                <div class="data-table-header">
                    <h5 class="mb-0"><i class="fas fa-table mr-2 text-secondary"></i>Récapitulatif des documents</h5>
                </div>
                <div class="p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center">
                            <thead class="thead-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-success">Validés</th>
                                    <th class="text-warning">En attente</th>
                                    <th class="text-danger">Rejetés</th>
                                    <th>Taux de validation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold"><?= number_format($totalDocuments) ?></td>
                                    <td class="text-success font-weight-bold"><?= number_format($docsValides) ?></td>
                                    <td class="text-warning font-weight-bold"><?= number_format($docsEnAttente) ?></td>
                                    <td class="text-danger font-weight-bold"><?= number_format($docsRejetes) ?></td>
                                    <td>
                                        <?php 
                                        $taux = $totalDocuments > 0 ? round(($docsValides / $totalDocuments) * 100, 1) : 0;
                                        ?>
                                        <div class="progress" style="height:20px">
                                            <div class="progress-bar bg-success" role="progressbar" style="width:<?= $taux ?>%">
                                                <?= $taux ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Répartition par sexe (Doughnut)
    new Chart(document.getElementById('sexeChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($sexeData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($sexeData['data']) ?>,
                backgroundColor: <?= json_encode($sexeData['colors']) ?>,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });

    // Répartition par âge (Bar)
    <?php if (!empty($ageGroups)): ?>
    new Chart(document.getElementById('ageChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($ageGroups, 'tranche')) ?>,
            datasets: [{
                label: 'Citoyens',
                data: <?= json_encode(array_column($ageGroups, 'total')) ?>,
                backgroundColor: ['#ff9800', '#ff5722', '#e91e63', '#9c27b0', '#673ab7'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }]
            }
        }
    });
    <?php endif; ?>

    // Statut documents (Pie)
    new Chart(document.getElementById('docStatusChart'), {
        type: 'pie',
        data: {
            labels: ['Validés', 'En attente', 'Rejetés'],
            datasets: [{
                data: [<?= $docsValides ?>, <?= $docsEnAttente ?>, <?= $docsRejetes ?>],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { position: 'bottom' }
        }
    });

    // Évolution mensuelle (Line)
    <?php if (!empty($evolution)): ?>
    new Chart(document.getElementById('evolutionChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($evolution, 'mois_label')) ?>,
            datasets: [{
                label: 'Citoyens ajoutés',
                data: <?= json_encode(array_column($evolution, 'total')) ?>,
                borderColor: '#1a5f2a',
                backgroundColor: 'rgba(26, 95, 42, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1a5f2a',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }]
            }
        }
    });
    <?php endif; ?>

    // Quartiers (Horizontal Bar)
    <?php if (!empty($quartiers)): ?>
    new Chart(document.getElementById('quartierChart'), {
        type: 'horizontalBar',
        data: {
            labels: <?= json_encode(array_column($quartiers, 'quartier')) ?>,
            datasets: [{
                label: 'Citoyens',
                data: <?= json_encode(array_column($quartiers, 'total')) ?>,
                backgroundColor: 'rgba(26, 95, 42, 0.8)',
                borderColor: '#1a5f2a',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                xAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }]
            }
        }
    });
    <?php endif; ?>

    // Documents par type (Bar)
    <?php if (!empty($docTypes)): ?>
    new Chart(document.getElementById('docTypeChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($docTypes, 'type')) ?>,
            datasets: [{
                label: 'Documents',
                data: <?= json_encode(array_column($docTypes, 'total')) ?>,
                backgroundColor: ['#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true, stepSize: 1 } }]
            }
        }
    });
    <?php endif; ?>
</script>

<?php require_once $includesPath . 'footer.php'; ?>
