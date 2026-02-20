<?php
$pageTitle = 'Gestion des Employés';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $matricule = $_POST['matricule'] ?? '';
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $email = $_POST['email'] ?? '';
        $telephone = $_POST['telephone'] ?? '';
        $adresse = $_POST['adresse'] ?? '';
        $date_naissance = $_POST['date_naissance'] ?? null;
        $date_embauche = $_POST['date_embauche'] ?? '';
        $poste = $_POST['poste'] ?? '';
        $departement_id = $_POST['departement_id'] ?? null;
        $salaire_base = $_POST['salaire_base'] ?? 0;
        $statut = $_POST['statut'] ?? 'actif';

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO employes (matricule, nom, prenom, email, telephone, adresse, date_naissance, date_embauche, poste, departement_id, salaire_base, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$matricule, $nom, $prenom, $email, $telephone, $adresse, $date_naissance ?: null, $date_embauche, $poste, $departement_id ?: null, $salaire_base, $statut]);
            header('Location: employes.php?success=1');
        } else {
            $stmt = $db->prepare("UPDATE employes SET matricule=?, nom=?, prenom=?, email=?, telephone=?, adresse=?, date_naissance=?, date_embauche=?, poste=?, departement_id=?, salaire_base=?, statut=? WHERE id=?");
            $stmt->execute([$matricule, $nom, $prenom, $email, $telephone, $adresse, $date_naissance ?: null, $date_embauche, $poste, $departement_id ?: null, $salaire_base, $statut, $id]);
            header('Location: employes.php?success=1');
        }
        exit();
    }
}

// Suppression
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM employes WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: employes.php?success=1');
    exit();
}

// Récupération des départements pour le formulaire
$stmt = $db->query("SELECT id, nom FROM departements ORDER BY nom");
$departements = $stmt->fetchAll();

if ($action === 'add' || $action === 'edit') {
    $employe = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM employes WHERE id = ?");
        $stmt->execute([$id]);
        $employe = $stmt->fetch();
        if (!$employe) {
            header('Location: employes.php');
            exit();
        }
    }
    ?>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-<?= $action === 'add' ? 'person-plus' : 'person-check' ?>"></i>
                <?= $action === 'add' ? 'Ajouter un employé' : 'Modifier un employé' ?>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Matricule *</label>
                                <input type="text" class="form-control" name="matricule" value="<?= htmlspecialchars($employe['matricule'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statut *</label>
                                <select class="form-select" name="statut" required>
                                    <option value="actif" <?= ($employe['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="inactif" <?= ($employe['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                    <option value="congé" <?= ($employe['statut'] ?? '') === 'congé' ? 'selected' : '' ?>>Congé</option>
                                    <option value="démission" <?= ($employe['statut'] ?? '') === 'démission' ? 'selected' : '' ?>>Démission</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($employe['nom'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prénom *</label>
                                <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($employe['prenom'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($employe['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="telephone" value="<?= htmlspecialchars($employe['telephone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <textarea class="form-control" name="adresse" rows="2"><?= htmlspecialchars($employe['adresse'] ?? '') ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="date_naissance" value="<?= $employe['date_naissance'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date d'embauche *</label>
                                <input type="date" class="form-control" name="date_embauche" value="<?= $employe['date_embauche'] ?? '' ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Poste *</label>
                                <input type="text" class="form-control" name="poste" value="<?= htmlspecialchars($employe['poste'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Département</label>
                                <select class="form-select" name="departement_id">
                                    <option value="">Sélectionner un département</option>
                                    <?php foreach ($departements as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= ($employe['departement_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salaire de base</label>
                            <input type="number" step="0.01" class="form-control" name="salaire_base" value="<?= $employe['salaire_base'] ?? 0 ?>">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Enregistrer
                            </button>
                            <a href="employes.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    // Liste des employés
    $search = $_GET['search'] ?? '';
    $where = '';
    $params = [];
    
    if ($search) {
        $where = "WHERE e.nom LIKE ? OR e.prenom LIKE ? OR e.matricule LIKE ? OR e.email LIKE ?";
        $search_term = "%$search%";
        $params = [$search_term, $search_term, $search_term, $search_term];
    }
    
    $stmt = $db->prepare("SELECT e.*, d.nom as departement_nom FROM employes e LEFT JOIN departements d ON e.departement_id = d.id $where ORDER BY e.date_creation DESC");
    $stmt->execute($params);
    $employes = $stmt->fetchAll();
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="bi bi-people"></i> Gestion des Employés</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="employes.php?action=add" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Ajouter un employé
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
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="text" class="form-control me-2" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i>
                        </button>
                        <?php if ($search): ?>
                            <a href="employes.php" class="btn btn-outline-secondary ms-2">Effacer</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom complet</th>
                            <th>Email</th>
                            <th>Poste</th>
                            <th>Département</th>
                            <th>Date embauche</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employes)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Aucun employé trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employes as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['matricule']) ?></td>
                                    <td><?= htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']) ?></td>
                                    <td><?= htmlspecialchars($emp['email']) ?></td>
                                    <td><?= htmlspecialchars($emp['poste']) ?></td>
                                    <td><?= htmlspecialchars($emp['departement_nom'] ?? 'N/A') ?></td>
                                    <td><?= date('d/m/Y', strtotime($emp['date_embauche'])) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'actif' => 'success',
                                            'inactif' => 'secondary',
                                            'congé' => 'warning',
                                            'démission' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badge_class[$emp['statut']] ?>">
                                            <?= ucfirst($emp['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="employes.php?action=edit&id=<?= $emp['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="employes.php?action=delete&id=<?= $emp['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')">
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
