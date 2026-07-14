<?php
/**
 * ============================================
 * SGC - Liste des Citoyens (CRUD)
 * ============================================
 */
define('SGC_ACCESS', true);
require_once '../auth/auth_check.php';
require_once '../config/database.php';

$pageTitle = 'Gestion des Citoyens';
$pageIcon = 'fa-users';

// Traitement suppression
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db = getDB();
        $id = (int)$_GET['delete'];
        
        // Vérifier si le citoyen existe
        $stmt = $db->prepare("SELECT nom, prenom FROM citoyens WHERE id = ?");
        $stmt->execute([$id]);
        $citoyen = $stmt->fetch();
        
        if ($citoyen) {
            // Suppression logique (changer statut au lieu de supprimer)
            $stmt = $db->prepare("UPDATE citoyens SET statut = 'inactif' WHERE id = ?");
            $stmt->execute([$id]);
            
            logActivity('suppression_citoyen', 'citoyens', $id, "Citoyen: {$citoyen['prenom']} {$citoyen['nom']}");
            $_SESSION['success'] = "Citoyen supprimé avec succès!";
        }
        
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression.";
        error_log("Erreur suppression citoyen: " . $e->getMessage());
        header('Location: index.php');
        exit;
    }
}

// Récupérer les citoyens
try {
    $db = getDB();
    
    // Filtre de recherche
    $where = "WHERE c.statut = 'actif'";
    $params = [];
    
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $where .= " AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.cin LIKE ? OR c.telephone LIKE ?)";
        $params = [$search, $search, $search, $search];
    }
    
    if (isset($_GET['quartier']) && !empty($_GET['quartier'])) {
        $where .= " AND c.quartier = ?";
        $params[] = $_GET['quartier'];
    }
    
    $stmt = $db->prepare("
        SELECT c.*, u.prenom as agent_prenom, u.nom as agent_nom
        FROM citoyens c
        LEFT JOIN utilisateurs u ON c.created_by = u.id
        $where
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $citoyens = $stmt->fetchAll();
    
    // Liste des quartiers pour le filtre
    $stmt = $db->query("SELECT DISTINCT quartier FROM citoyens WHERE quartier IS NOT NULL AND statut = 'actif' ORDER BY quartier");
    $quartiers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("Erreur liste citoyens: " . $e->getMessage());
    $citoyens = [];
    $quartiers = [];
}
define('SGC_ACCESS', true);
require_once '../auth/auth_check.php';
require_once '../config/database.php';

global $currentUser;  // ← AJOUTÉ

$pageTitle = 'Gestion des Citoyens';
$pageIcon = 'fa-users';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/navbar.php';
?>

<div class="main-content">
    <!-- Header avec actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Liste des Citoyens</h4>
            <p class="text-muted mb-0">Gérez les citoyens de votre commune</p>
        </div>
        <a href="ajouter.php" class="btn btn-add">
            <i class="fas fa-plus me-2"></i>Ajouter un citoyen
        </a>
    </div>
    
    <!-- Filtres -->
    <div class="data-table-card mb-4 p-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small">Rechercher</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" name="search" 
                           placeholder="Nom, CIN, téléphone..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small">Quartier</label>
                <select class="form-select" name="quartier">
                    <option value="">Tous les quartiers</option>
                    <?php foreach ($quartiers as $q): ?>
                        <option value="<?= htmlspecialchars($q) ?>" <?= ($_GET['quartier'] ?? '') == $q ? 'selected' : '' ?>>
                            <?= htmlspecialchars($q) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-success">
                    <i class="fas fa-filter me-1"></i>Filtrer
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-undo me-1"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tableau -->
    <div class="data-table-card">
        <div class="data-table-header">
            <h5><i class="fas fa-list me-2 text-success"></i>Citoyens enregistrés</h5>
            <span class="badge bg-success"><?= count($citoyens) ?> citoyen(s)</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-custom data-table" id="citoyensTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>CIN</th>
                        <th>Nom complet</th>
                        <th>Sexe</th>
                        <th>Quartier</th>
                        <th>Téléphone</th>
                        <th>Profession</th>
                        <th>Ajouté par</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($citoyens)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Aucun citoyen trouvé
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($citoyens as $index => $c): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><span class="fw-bold text-primary"><?= htmlspecialchars($c['cin']) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2" style="width:36px;height:36px;font-size:0.8rem;">
                                            <?= strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
                                            <?php if ($c['nom_ar']): ?>
                                                <small class="text-muted" dir="rtl"><?= htmlspecialchars($c['prenom_ar'] . ' ' . $c['nom_ar']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($c['sexe'] == 'M'): ?>
                                        <span class="badge bg-primary"><i class="fas fa-mars me-1"></i>Homme</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-venus me-1"></i>Femme</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="fas fa-map-marker-alt text-muted me-1"></i><?= htmlspecialchars($c['quartier'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($c['telephone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['profession'] ?? '-') ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(($c['agent_prenom'] ?? 'Système') . ' ' . ($c['agent_nom'] ?? '')) ?>
                                    </small>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($c['created_at'])) ?></small></td>
                                <td class="text-center">
                                    <a href="modifier.php?id=<?= $c['id'] ?>" class="btn-action btn-edit" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn-action btn-delete" title="Supprimer"
                                            onclick="confirmDelete('index.php?delete=<?= $c['id'] ?>', 'Supprimer <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?> ?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>