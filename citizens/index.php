<?php
/**
 * ============================================
 * CMS Baladiya - Statistiques
 * ============================================
 */
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Statistiques';
require_once __DIR__ . '/../includes/header.php';

try {
    // ===== CITOYENS =====
    $totalCitoyens = $pdo->query("SELECT COUNT(*) FROM citoyens WHERE statut = 'actif'")->fetchColumn();
    $totalHommes = $pdo->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'M' AND statut = 'actif'")->fetchColumn();
    $totalFemmes = $pdo->query("SELECT COUNT(*) FROM citoyens WHERE sexe = 'F' AND statut = 'actif'")->fetchColumn();
    $totalInactifs = $pdo->query("SELECT COUNT(*) FROM citoyens WHERE statut = 'inactif'")->fetchColumn();

    // Répartition par sexe
    $sexeData = [
        'labels' => ['Hommes', 'Femmes'],
        'data' => [(int)$totalHommes, (int)$totalFemmes],
        'colors' => ['#3498db', '#e91e63']
    ];

    // Répartition par âge
    $stmt = $pdo->query("
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
    $stmt = $pdo->query("
        SELECT quartier, COUNT(*) as total 
        FROM citoyens 
        WHERE statut = 'actif' AND quartier IS NOT NULL AND quartier != '' 
        GROUP BY quartier 
        ORDER BY total DESC
    ");
    $quartiers = $stmt->fetchAll();

    // Évolution mensuelle (12 derniers mois)
    $stmt = $pdo->query("
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
    $totalDocuments = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
    $docsValides = $pdo->query("SELECT COUNT(*) FROM documents WHERE statut = 'valide'")->fetchColumn();
    $docsExpires = $pdo->query("SELECT COUNT(*) FROM documents WHERE statut = 'expire'")->fetchColumn();
    $docsAnnules = $pdo->query("SELECT COUNT(*) FROM documents WHERE statut = 'annule'")->fetchColumn();

    // Documents par type
    $stmt = $pdo->query("
        SELECT type_document, COUNT(*) as total 
        FROM documents 
        GROUP BY type_document 
        ORDER BY total DESC
    ");
    $docTypes = $stmt->fetchAll();

    // ===== UTILISATEURS =====
    $totalAgents = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'")->fetchColumn();

    // Top agents
    $stmt = $pdo->query("
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
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Statistiques</h4>
            <p class="text-muted mb-0">Vue d'ensemble du système</p>
        </div>
        <div class="text-muted small">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold text-success"><?= number_format($totalCitoyens) ?></div>
                    <div class="text-muted small">Citoyens</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold text-primary"><?= number_format($totalHommes) ?></div>
                    <div class="text-muted small">Hommes</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold" style="color:#e91e63"><?= number_format($totalFemmes) ?></div>
                    <div class="text-muted small">Femmes</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold text-warning"><?= number_format($totalDocuments) ?></div>
                    <div class="text-muted small">Documents</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold text-info"><?= number_format($totalAgents) ?></div>
                    <div class="text-muted small">Agents</div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center">
                    <div class="display-6 fw-bold text-danger"><?= number_format($totalInactifs) ?></div>
                    <div class="text-muted small">Inactifs</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Répartition par sexe -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-gender-ambiguous me-2 text-primary"></i>Répartition par sexe</h5>
                </div>
                <div class="card-body">
                    <canvas id="sexeChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <!-- Répartition par âge -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-cake2 me-2 text-warning"></i>Répartition par âge</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ageGroups)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-bar-chart-line fs-1 mb-3 d-block"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="ageChart" height="220"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Statut des documents -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2 text-success"></i>Statut des documents</h5>
                </div>
                <div class="card-body">
                    <canvas id="docStatusChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Évolution citoyens -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2 text-info"></i>Évolution des citoyens (12 derniers mois)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($evolution)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-graph-up fs-1 mb-3 d-block"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="evolutionChart" height="180"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top agents -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top agents</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($topAgents)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox fs-2 mb-2 d-block"></i>
                            <p>Aucune donnée</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topAgents as $i => $agent): ?>
                                <div class="list-group-item d-flex align-items-center py-3">
                                    <div class="me-3" style="width:30px;text-align:center;font-weight:bold;color:<?= $i < 3 ? '#198754' : '#6c757d' ?>">
                                        <?= $i + 1 ?>
                                    </div>
                                    <div class="flex-shrink-0 me-3">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:0.85rem;font-weight:bold;">
                                            <?= strtoupper(substr($agent['prenom'], 0, 1) . substr($agent['nom'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="fw-bold text-truncate"><?= htmlspecialchars($agent['prenom'] . ' ' . $agent['nom']) ?></div>
                                        <small class="text-muted"><?= ucfirst($agent['role']) ?></small>
                                    </div>
                                    <span class="badge bg-success"><?= $agent['citoyens_crees'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <!-- Répartition par quartier -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2 text-danger"></i>Répartition par quartier</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($quartiers)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-map fs-1 mb-3 d-block"></i>
                            <p>Aucune donnée disponible</p>
                        </div>
                    <?php else: ?>
                        <canvas id="quartierChart" height="250"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents par type -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-folder2-open me-2 text-primary"></i>Documents par type</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($docTypes)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-folder fs-1 mb-3 d-block"></i>
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
    <div class="row g-4 mt-1 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-table me-2 text-secondary"></i>Récapitulatif des documents</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-success">Validés</th>
                                    <th class="text-warning">Expirés</th>
                                    <th class="text-danger">Annulés</th>
                                    <th>Taux de validation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold"><?= number_format($totalDocuments) ?></td>
                                    <td class="text-success fw-bold"><?= number_format($docsValides) ?></td>
                                    <td class="text-warning fw-bold"><?= number_format($docsExpires) ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($docsAnnules) ?></td>
                                    <td style="min-width:200px">
                                        <?php 
                                        $taux = $totalDocuments > 0 ? round(($docsValides / $totalDocuments) * 100, 1) : 0;
                                        ?>
                                        <div class="progress" style="height:22px">
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
            plugins: { legend: { position: 'bottom' } }
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
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>

    // Statut documents (Pie)
    new Chart(document.getElementById('docStatusChart'), {
        type: 'pie',
        data: {
            labels: ['Validés', 'Expirés', 'Annulés'],
            datasets: [{
                data: [<?= $docsValides ?>, <?= $docsExpires ?>, <?= $docsAnnules ?>],
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
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
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#198754',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>

    // Quartiers (Horizontal Bar)
    <?php if (!empty($quartiers)): ?>
    new Chart(document.getElementById('quartierChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($quartiers, 'quartier')) ?>,
            datasets: [{
                label: 'Citoyens',
                data: <?= json_encode(array_column($quartiers, 'total')) ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.8)',
                borderColor: '#198754',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>

    // Documents par type (Bar)
    <?php if (!empty($docTypes)): ?>
    new Chart(document.getElementById('docTypeChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($docTypes, 'type_document')) ?>,
            datasets: [{
                label: 'Documents',
                data: <?= json_encode(array_column($docTypes, 'total')) ?>,
                backgroundColor: ['#0d6efd', '#0dcaf0', '#20c997', '#198754', '#ffc107', '#fd7e14', '#dc3545', '#6f42c1'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>