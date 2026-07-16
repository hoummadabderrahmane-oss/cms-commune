<?php
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
    'extrait_naissance'    => 'Extrait de naissance',
    'certificat_residence' => 'Certificat de résidence',
    'attestation_mariage'  => 'Attestation de mariage',
    'certificat_deces'     => 'Certificat de décès',
    'carte_identite'       => "Carte d'identité",
    'autre'                => 'Autre',
];
$statutLabels = ['valide' => 'Valide', 'expire' => 'Expiré', 'annule' => 'Annulé'];
$statutColors = ['valide' => 'success', 'expire' => 'warning', 'annule' => 'danger'];

/* ---------- Search + filters + pagination ---------- */
$q      = trim($_GET['q'] ?? '');
$type   = $_GET['type'] ?? '';
$statut = $_GET['statut'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($q !== '') {
    $where[] = '(c.nom LIKE :q OR c.prenom LIKE :q OR c.cin LIKE :q OR d.numero_document LIKE :q)';
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

$stmt = $pdo->prepare("SELECT COUNT(*) FROM documents d JOIN citoyens c ON c.id = d.citoyen_id $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));

$sql = "SELECT d.*, c.nom, c.prenom, c.cin
        FROM documents d
        JOIN citoyens c ON c.id = d.citoyen_id
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

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white data-table-header d-flex justify-content-between align-items-center">
            <h5><i class="bi bi-file-earmark-text-fill me-2"></i>Documents <span class="badge bg-secondary"><?= $total ?></span></h5>
            <a href="generer.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Générer un document
            </a>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control"
                           placeholder="Rechercher (citoyen, CIN, n° document)..."
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
                <div class="col-md-2">
                    <select name="statut" class="form-select">
                        <option value="">— Statuts —</option>
                        <?php foreach ($statutLabels as $k => $label): ?>
                            <option value="<?= $k ?>" <?= $statut === $k ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrer</button>
                    <a href="index.php" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>N° document</th>
                            <th>Type</th>
                            <th>Citoyen</th>
                            <th>Émission</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th>Fichier</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$documents): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucun document trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($documents as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['numero_document'] ?? ('#' . $d['id'])) ?></strong></td>
                            <td><?= $typeLabels[$d['type_document']] ?? $d['type_document'] ?></td>
                            <td>
                                <?= htmlspecialchars($d['nom'] . ' ' . $d['prenom']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($d['cin']) ?></small>
                            </td>
                            <td><?= $d['date_emission'] ? date('d/m/Y', strtotime($d['date_emission'])) : '—' ?></td>
                            <td><?= $d['date_expiration'] ? date('d/m/Y', strtotime($d['date_expiration'])) : '—' ?></td>
                            <td><span class="badge bg-<?= $statutColors[$d['statut']] ?? 'secondary' ?>"><?= $statutLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                            <td>
                                <?php if ($d['fichier'] && file_exists(__DIR__ . '/../asseets/uploads/documents/' . $d['fichier'])): ?>
                                    <a href="../asseets/uploads/documents/<?= htmlspecialchars($d['fichier']) ?>" target="_blank"
                                       class="btn btn-sm btn-outline-secondary" title="Télécharger">
                                        <i class="bi bi-file-earmark-arrow-down"></i>
                                    </a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="attestation.php?id=<?= (int)$d['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Imprimer">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <form action="statut.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                    <?php if ($d['statut'] !== 'valide'): ?>
                                        <input type="hidden" name="statut" value="valide">
                                        <button class="btn btn-sm btn-outline-success" title="Valider"><i class="bi bi-check-lg"></i></button>
                                    <?php else: ?>
                                        <input type="hidden" name="statut" value="annule">
                                        <button class="btn btn-sm btn-outline-warning" title="Annuler"
                                                onclick="return confirm('Annuler ce document ?');"><i class="bi bi-x-lg"></i></button>
                                    <?php endif; ?>
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