<?php
/**
 * ============================================
 * CMS Baladiya - Gestion des Documents
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

$statutLabels = ['valide' => 'Valide', 'expire' => 'Expire', 'annule' => 'Annule'];
$statutColors = ['valide' => 'success', 'expire' => 'warning', 'annule' => 'danger'];

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

$pageTitle = 'Gestion des documents';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;">
                            <i class="bi bi-file-earmark-text fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($totalDocs) ?></div>
                        <div class="text-muted small">Total documents</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;">
                            <i class="bi bi-check-circle fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($docsValides) ?></div>
                        <div class="text-muted small">Valides</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;">
                            <i class="bi bi-exclamation-triangle fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($docsExpires) ?></div>
                        <div class="text-muted small">Expires</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center" style="width:52px;height:52px;">
                            <i class="bi bi-x-circle fs-3"></i>
                        </div>
                    </div>
                    <div>
                        <div class="h4 mb-0 fw-bold"><?= number_format($docsAnnules) ?></div>
                        <div class="text-muted small">Annules</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Liste des documents <span class="badge bg-secondary"><?= $total ?></span></h5>
            <a href="ajouter.php" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouveau document
            </a>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control"
                           placeholder="Rechercher (N doc, nom, CIN)..."
                           value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">— Tous les types —</option>
                        <?php foreach ($typeLabels as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select">
                        <option value="">— Tous les statuts —</option>
                        <?php foreach ($statutLabels as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $statut === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Filtrer</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>N Document</th>
                            <th>Type</th>
                            <th>Citoyen</th>
                            <th>CIN</th>
                            <th>Date emission</th>
                            <th>Date expiration</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$documents): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">Aucun document trouve.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $i => $d): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td class="font-monospace"><?= htmlspecialchars($d['numero_document'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $typeColors[$d['type_document']] ?? 'secondary' ?>"><?= $typeLabels[$d['type_document']] ?? $d['type_document'] ?></span></td>
                            <td><?= htmlspecialchars(($d['prenom'] ?? '') . ' ' . ($d['nom'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($d['cin'] ?? '—') ?></td>
                            <td><?= $d['date_emission'] ? date('d/m/Y', strtotime($d['date_emission'])) : '—' ?></td>
                            <td><?= $d['date_expiration'] ? date('d/m/Y', strtotime($d['date_expiration'])) : '—' ?></td>
                            <td><span class="badge bg-<?= $statutColors[$d['statut']] ?? 'secondary' ?>"><?= $statutLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                            <td class="text-end text-nowrap">
                                <a href="generer.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-success" title="Generer / Imprimer" target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <a href="edit.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-warning" title="Modifier">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="statut.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                    <button class="btn btn-sm btn-outline-info" title="Changer statut">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                                <form action="delete.php" method="POST" class="d-inline"
                                      onsubmit="return confirm('Voulez-vous vraiment supprimer ce document ?');">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&q=<?= urlencode($q) ?>&type=<?= urlencode($type) ?>&statut=<?= urlencode($statut) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>