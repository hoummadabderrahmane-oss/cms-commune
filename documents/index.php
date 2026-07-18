<?php
/**
 * ============================================
 * CMS Baladiya - Gestion des Documents (Modern)
 * ============================================
 */
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$typeLabels = [
    'extrait_naissance' => 'Extrait de naissance',
    'certificat_residence' => 'Certificat de residence',
    'attestation_mariage' => 'Attestation de mariage',
    'certificat_deces' => 'Certificat de deces',
    'carte_identite' => 'Carte d identite',
    'autre' => 'Autre'
];

$typeColors = [
    'extrait_naissance' => 'primary',
    'certificat_residence' => 'success',
    'attestation_mariage' => 'info',
    'certificat_deces' => 'dark',
    'carte_identite' => 'warning',
    'autre' => 'secondary'
];

$typeIcons = [
    'extrait_naissance' => 'bi-file-earmark-person',
    'certificat_residence' => 'bi-house-door',
    'attestation_mariage' => 'bi-heart',
    'certificat_deces' => 'bi-file-earmark-x',
    'carte_identite' => 'bi-person-vcard',
    'autre' => 'bi-file-earmark'
];

$statutLabels = ['valide' => 'Valide', 'expire' => 'Expire', 'annule' => 'Annule'];
$statutColors = ['valide' => 'success', 'expire' => 'warning', 'annule' => 'danger'];
$statutIcons = ['valide' => 'bi-check-circle-fill', 'expire' => 'bi-exclamation-circle-fill', 'annule' => 'bi-x-circle-fill'];

/* ---------- Search + filter + pagination ---------- */
$q      = trim($_GET['q'] ?? '');
$type   = $_GET['type'] ?? '';
$statut = $_GET['statut'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(d.numero_document LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR c.cin LIKE :q)';
    $params[':q'] = "%{$q}%";
}
if (array_key_exists($type, $typeLabels)) {
    $where[] = 'd.type_document = :type';
    $params[':type'] = $type;
}
if (array_key_exists($statut, $statutLabels)) {
    $where[] = 'd.statut = :statut';
    $params[':statut'] = $statut;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM documents d LEFT JOIN citoyens c ON d.citoyen_id = c.id $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$sql = "SELECT d.*, c.nom, c.prenom, c.cin, c.date_naissance, c.sexe, c.quartier, c.telephone 
        FROM documents d 
        LEFT JOIN citoyens c ON d.citoyen_id = c.id 
        $whereSql 
        ORDER BY d.created_at DESC 
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$documents = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Quick stats
$totalDocs = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$docsValides = $pdo->query("SELECT COUNT(*) FROM documents WHERE statut = 'valide'")->fetchColumn();
$docsExpires = $pdo->query("SELECT COUNT(*) FROM documents WHERE statut = 'expire'")->fetchColumn();
$docsAnnules = $pdo->query("SELECT COUNT(*) FROM documents WHERE statut = 'annule'")->fetchColumn();

// Stats by type for mini chart
$stmt = $pdo->query("SELECT type_document, COUNT(*) as total FROM documents GROUP BY type_document");
$docsByType = $stmt->fetchAll();

$pageTitle = 'Gestion des documents';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .doc-stat-card {
        transition: all 0.3s ease;
        border-radius: 16px;
        overflow: hidden;
        position: relative;
    }
    .doc-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12) !important;
    }
    .doc-stat-card .stat-icon-box {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
    }
    .doc-stat-card .stat-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }
    .doc-stat-card .stat-label {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
    }
    .doc-stat-card .stat-trend {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 20px;
        font-weight: 600;
    }

    /* Modern table */
    .modern-table {
        border-collapse: separate;
        border-spacing: 0 8px;
    }
    .modern-table thead th {
        border: none;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        padding: 12px 16px;
    }
    .modern-table tbody tr {
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .modern-table tbody tr:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transform: scale(1.002);
    }
    .modern-table tbody td {
        border: none;
        padding: 16px;
        vertical-align: middle;
    }
    .modern-table tbody td:first-child {
        border-radius: 12px 0 0 12px;
    }
    .modern-table tbody td:last-child {
        border-radius: 0 12px 12px 0;
    }

    /* Document type badge */
    .doc-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    /* Status badge */
    .doc-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* Action buttons */
    .action-btn-group {
        display: flex;
        gap: 4px;
    }
    .action-btn-group .btn {
        width: 34px;
        height: 34px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: all 0.2s;
    }
    .action-btn-group .btn:hover {
        transform: scale(1.1);
    }

    /* Filter bar */
    .filter-bar {
        background: #f8f9fa;
        border-radius: 14px;
        padding: 16px;
    }
    .filter-bar .form-control,
    .filter-bar .form-select {
        border-radius: 10px;
        border: 1px solid #e9ecef;
        padding: 10px 14px;
    }
    .filter-bar .form-control:focus,
    .filter-bar .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state-icon {
        width: 100px;
        height: 100px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2.5rem;
        color: #adb5bd;
    }

    /* Dashboard return button */
    .btn-dashboard-return {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #1a5f2a 0%, #2d8a3e 100%);
        border: none;
        color: white;
    }
    .btn-dashboard-return:hover {
        transform: translateX(-4px);
        box-shadow: 0 4px 15px rgba(26, 95, 42, 0.3);
        color: white;
    }

    /* Page header */
    .page-header-modern {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }
    .page-header-modern h4 {
        font-weight: 700;
        color: #1a5f2a;
    }
    .page-header-modern .breadcrumb-text {
        color: #6c757d;
        font-size: 0.85rem;
    }

    /* Card header modern */
    .card-header-modern {
        background: white;
        border-bottom: 1px solid #f0f0f0;
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Stat icon box image styling */
    .stat-icon-box img {
        max-width: 100%;
        max-height: 100%;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* Pagination modern */
    .pagination-modern .page-link {
        border: none;
        border-radius: 10px;
        margin: 0 3px;
        padding: 8px 14px;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.2s;
    }
    .pagination-modern .page-link:hover {
        background: #e9ecef;
        color: #1a5f2a;
    }
    .pagination-modern .page-item.active .page-link {
        background: #1a5f2a;
        color: white;
    }
</style>

<div class="container-fluid py-4">

    <!-- Page Header with Dashboard Return -->
    <div class="page-header-modern">
        <div>
            <div class="breadcrumb-text mb-1">
                <i class="bi bi-house-door me-1"></i> Tableau de bord 
                <i class="bi bi-chevron-right mx-2" style="font-size:0.7rem"></i> 
                Documents
            </div>
            <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Gestion des documents</h4>
        </div>
        <a href="../admin/dashboard.php" class="btn btn-dashboard-return">
            <i class="bi bi-arrow-left-circle"></i>
            Retour au tableau de bord
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show rounded-3">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card doc-stat-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-box bg-primary bg-opacity-10 text-primary me-3" style="padding: 8px;">
                        <img src="../assets/images/logo-documents.png" alt="Documents" style="width: 40px; height: 40px; object-fit: contain;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-value text-primary"><?= number_format($totalDocs) ?></div>
                        <div class="stat-label">Total documents</div>
                    </div>
                    <span class="stat-trend bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-arrow-up-short"></i> 100%
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card doc-stat-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-box bg-success bg-opacity-10 text-success me-3" style="padding: 4px;">
                        <img src="../assets/images/logo-valide.png" alt="Valide" style="width: 48px; height: 48px; object-fit: contain;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-value text-success"><?= number_format($docsValides) ?></div>
                        <div class="stat-label">Valides</div>
                    </div>
                    <span class="stat-trend bg-success bg-opacity-10 text-success">
                        <i class="bi bi-shield-check"></i> OK
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card doc-stat-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-box bg-warning bg-opacity-10 text-warning me-3" style="padding: 4px;">
                        <img src="../assets/images/logo-expire.png" alt="Expire" style="width: 48px; height: 48px; object-fit: contain;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-value text-warning"><?= number_format($docsExpires) ?></div>
                        <div class="stat-label">Expires</div>
                    </div>
                    <span class="stat-trend bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-exclamation"></i>!
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card doc-stat-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon-box bg-danger bg-opacity-10 text-danger me-3" style="padding: 4px;">
                        <img src="../assets/images/logo-annule.png" alt="Annule" style="width: 48px; height: 48px; object-fit: contain;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="stat-value text-danger"><?= number_format($docsAnnules) ?></div>
                        <div class="stat-label">Annules</div>
                    </div>
                    <span class="stat-trend bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-ban"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card shadow-sm border-0" style="border-radius: 16px;">
        <div class="card-header-modern">
            <div class="d-flex align-items-center">
                <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center me-3" style="width:44px;height:44px;">
                    <i class="bi bi-file-earmark-text fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">Liste des documents</h5>
                    <small class="text-muted"><?= $total ?> document(s) trouve(s)</small>
                </div>
            </div>
            <a href="ajouter.php" class="btn btn-success" style="border-radius: 12px; padding: 10px 20px; font-weight: 600;">
                <i class="bi bi-plus-lg me-2"></i>Nouveau document
            </a>
        </div>

        <div class="card-body px-4 pb-4">
            <!-- Filter Bar -->
            <div class="filter-bar mb-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted fw-semibold mb-1">
                            <i class="bi bi-search me-1"></i>Recherche
                        </label>
                        <input type="text" name="q" class="form-control" 
                               placeholder="N doc, nom, CIN..."
                               value="<?= htmlspecialchars($q) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-semibold mb-1">
                            <i class="bi bi-funnel me-1"></i>Type
                        </label>
                        <select name="type" class="form-select">
                            <option value="">Tous les types</option>
                            <?php foreach ($typeLabels as $k => $label): ?>
                                <option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-semibold mb-1">
                            <i class="bi bi-flag me-1"></i>Statut
                        </label>
                        <select name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($statutLabels as $k => $label): ?>
                                <option value="<?= $k ?>" <?= $statut === $k ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" style="border-radius: 10px; padding: 10px;">
                            <i class="bi bi-funnel-fill me-1"></i>Filtrer
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius: 10px;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Documents Table -->
            <div class="table-responsive">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>N Document</th>
                            <th>Type</th>
                            <th>Citoyen</th>
                            <th>CIN</th>
                            <th>Emission</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th class="text-end" style="width:180px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$documents): ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-folder-open"></i>
                                    </div>
                                    <h5 class="text-muted">Aucun document trouve</h5>
                                    <p class="text-muted small">Commencez par ajouter un nouveau document</p>
                                    <a href="ajouter.php" class="btn btn-success mt-2" style="border-radius: 10px;">
                                        <i class="bi bi-plus-lg me-2"></i>Ajouter un document
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($documents as $i => $d): ?>
                        <tr>
                            <td class="text-muted fw-semibold"><?= $offset + $i + 1 ?></td>
                            <td>
                                <span class="font-monospace fw-semibold text-dark bg-light px-2 py-1 rounded-2" style="font-size:0.85rem;">
                                    <?= htmlspecialchars($d['numero_document'] ?? '—') ?>
                                </span>
                            </td>
                            <td>
                                <span class="doc-type-badge bg-<?= $typeColors[$d['type_document']] ?? 'secondary' ?> bg-opacity-10 text-<?= $typeColors[$d['type_document']] ?? 'secondary' ?>">
                                    <i class="bi <?= $typeIcons[$d['type_document']] ?? 'bi-file-earmark' ?>"></i>
                                    <?= $typeLabels[$d['type_document']] ?? $d['type_document'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;font-size:0.75rem;font-weight:bold;">
                                        <?= strtoupper(substr($d['prenom'] ?? '', 0, 1) . substr($d['nom'] ?? '', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?></div>
                                        <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($d['quartier'] ?? '') ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($d['cin'] ?? '—') ?></td>
                            <td class="text-muted small">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= $d['date_emission'] ? date('d/m/Y', strtotime($d['date_emission'])) : '—' ?>
                            </td>
                            <td class="text-muted small">
                                <?php if ($d['date_expiration']): ?>
                                    <i class="bi bi-calendar-x me-1"></i>
                                    <?= date('d/m/Y', strtotime($d['date_expiration'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="doc-status-badge bg-<?= $statutColors[$d['statut']] ?? 'secondary' ?> bg-opacity-10 text-<?= $statutColors[$d['statut']] ?? 'secondary' ?>">
                                    <i class="bi <?= $statutIcons[$d['statut']] ?? 'bi-circle' ?>"></i>
                                    <?= $statutLabels[$d['statut']] ?? $d['statut'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btn-group justify-content-end">
                                    <a href="generer.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline-success" title="Generer / Imprimer" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="edit.php?id=<?= (int)$d['id'] ?>" class="btn btn-outline-warning" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="statut.php" method="POST" class="d-inline" title="Changer statut">
                                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                        <button class="btn btn-outline-info">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                    <form action="delete.php" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Supprimer ce document ?');" title="Supprimer">
                                        <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                        <button class="btn btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination pagination-modern justify-content-center mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&statut=<?= urlencode($statut) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&statut=<?= urlencode($statut) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&statut=<?= urlencode($statut) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>