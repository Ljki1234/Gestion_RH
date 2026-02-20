<?php
$pageTitle = 'Gestion des Départements';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $nom = $_POST['nom'] ?? '';
        $description = $_POST['description'] ?? '';

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO departements (nom, description) VALUES (?, ?)");
            $stmt->execute([$nom, $description]);
            header('Location: departements.php?success=1');
        } else {
            $stmt = $db->prepare("UPDATE departements SET nom=?, description=? WHERE id=?");
            $stmt->execute([$nom, $description, $id]);
            header('Location: departements.php?success=1');
        }
        exit();
    }
}

// Suppression
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM departements WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: departements.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $departement = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM departements WHERE id = ?");
        $stmt->execute([$id]);
        $departement = $stmt->fetch();
        if (!$departement) {
            header('Location: departements.php');
            exit();
        }
    }
    ?>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-<?= $action === 'add' ? 'plus-circle' : 'pencil' ?>"></i>
                <?= $action === 'add' ? 'Ajouter un département' : 'Modifier un département' ?>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Nom du département *</label>
                            <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($departement['nom'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($departement['description'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Enregistrer
                            </button>
                            <a href="departements.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    $stmt = $db->query("SELECT d.*, COUNT(e.id) as nb_employes FROM departements d LEFT JOIN employes e ON d.id = e.departement_id GROUP BY d.id ORDER BY d.nom");
    $departements = $stmt->fetchAll();
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="bi bi-building"></i> Gestion des Départements</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="departements.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Ajouter un département
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Opération réussie !
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Nombre d'employés</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departements)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucun département</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departements as $dept): ?>
                                <tr>
                                    <td><?= $dept['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($dept['nom']) ?></strong></td>
                                    <td><?= htmlspecialchars($dept['description'] ?? '') ?></td>
                                    <td><span class="badge bg-primary"><?= $dept['nb_employes'] ?></span></td>
                                    <td><?= date('d/m/Y', strtotime($dept['date_creation'])) ?></td>
                                    <td>
                                        <a href="departements.php?action=edit&id=<?= $dept['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="departements.php?action=delete&id=<?= $dept['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
require_once 'includes/footer.php';
?>
