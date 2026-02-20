<?php
$pageTitle = 'Gestion des Congés';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $employe_id = $_POST['employe_id'] ?? '';
        $type_conge = $_POST['type_conge'] ?? '';
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $motif = $_POST['motif'] ?? '';
        
        $date1 = new DateTime($date_debut);
        $date2 = new DateTime($date_fin);
        $nombre_jours = $date1->diff($date2)->days + 1;

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO conges (employe_id, type_conge, date_debut, date_fin, nombre_jours, motif) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employe_id, $type_conge, $date_debut, $date_fin, $nombre_jours, $motif]);
            header('Location: conges.php?success=1');
        } else {
            $stmt = $db->prepare("UPDATE conges SET employe_id=?, type_conge=?, date_debut=?, date_fin=?, nombre_jours=?, motif=? WHERE id=?");
            $stmt->execute([$employe_id, $type_conge, $date_debut, $date_fin, $nombre_jours, $motif, $id]);
            header('Location: conges.php?success=1');
        }
        exit();
    }
}

// Traitement d'un congé (approuver/refuser)
if ($action === 'traiter' && $id) {
    $statut = $_GET['statut'] ?? '';
    if (in_array($statut, ['approuvé', 'refusé'])) {
        $user_id = $_SESSION['user_id'];
        $stmt = $db->prepare("UPDATE conges SET statut=?, date_traitement=NOW(), traite_par=? WHERE id=?");
        $stmt->execute([$statut, $user_id, $id]);
        header('Location: conges.php?success=1');
        exit();
    }
}

// Suppression
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM conges WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: conges.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $conge = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM conges WHERE id = ?");
        $stmt->execute([$id]);
        $conge = $stmt->fetch();
        if (!$conge) {
            header('Location: conges.php');
            exit();
        }
    }
    
    $stmt = $db->query("SELECT id, matricule, nom, prenom FROM employes WHERE statut = 'actif' ORDER BY nom, prenom");
    $employes = $stmt->fetchAll();
    ?>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-<?= $action === 'add' ? 'calendar-plus' : 'calendar-check' ?>"></i>
                <?= $action === 'add' ? 'Demander un congé' : 'Modifier un congé' ?>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="" id="congeForm">
                        <div class="mb-3">
                            <label class="form-label">Employé *</label>
                            <select class="form-select" name="employe_id" required>
                                <option value="">Sélectionner un employé</option>
                                <?php foreach ($employes as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($conge['employe_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['matricule'] . ' - ' . $emp['prenom'] . ' ' . $emp['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de congé *</label>
                            <select class="form-select" name="type_conge" required>
                                <option value="">Sélectionner un type</option>
                                <option value="annuel" <?= ($conge['type_conge'] ?? '') === 'annuel' ? 'selected' : '' ?>>Annuel</option>
                                <option value="maladie" <?= ($conge['type_conge'] ?? '') === 'maladie' ? 'selected' : '' ?>>Maladie</option>
                                <option value="maternité" <?= ($conge['type_conge'] ?? '') === 'maternité' ? 'selected' : '' ?>>Maternité</option>
                                <option value="paternité" <?= ($conge['type_conge'] ?? '') === 'paternité' ? 'selected' : '' ?>>Paternité</option>
                                <option value="exceptionnel" <?= ($conge['type_conge'] ?? '') === 'exceptionnel' ? 'selected' : '' ?>>Exceptionnel</option>
                                <option value="sans_solde" <?= ($conge['type_conge'] ?? '') === 'sans_solde' ? 'selected' : '' ?>>Sans solde</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de début *</label>
                                <input type="date" class="form-control" name="date_debut" value="<?= $conge['date_debut'] ?? '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de fin *</label>
                                <input type="date" class="form-control" name="date_fin" value="<?= $conge['date_fin'] ?? '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif</label>
                            <textarea class="form-control" name="motif" rows="3"><?= htmlspecialchars($conge['motif'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Enregistrer
                            </button>
                            <a href="conges.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    $filter = $_GET['filter'] ?? 'all';
    $where = '';
    
    if ($filter === 'en_attente') {
        $where = "WHERE c.statut = 'en_attente'";
    } elseif ($filter === 'approuvé') {
        $where = "WHERE c.statut = 'approuvé'";
    } elseif ($filter === 'refusé') {
        $where = "WHERE c.statut = 'refusé'";
    }
    
    $stmt = $db->query("SELECT c.*, e.matricule, e.nom, e.prenom FROM conges c JOIN employes e ON c.employe_id = e.id $where ORDER BY c.date_demande DESC");
    $conges = $stmt->fetchAll();
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="bi bi-calendar-check"></i> Gestion des Congés</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="conges.php?action=add" class="btn btn-primary">
                <i class="bi bi-calendar-plus"></i> Demander un congé
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Opération réussie !
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="conges.php" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?>">Tous</a>
                <a href="conges.php?filter=en_attente" class="btn btn-<?= $filter === 'en_attente' ? 'warning' : 'outline-warning' ?>">En attente</a>
                <a href="conges.php?filter=approuvé" class="btn btn-<?= $filter === 'approuvé' ? 'success' : 'outline-success' ?>">Approuvés</a>
                <a href="conges.php?filter=refusé" class="btn btn-<?= $filter === 'refusé' ? 'danger' : 'outline-danger' ?>">Refusés</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Type</th>
                            <th>Période</th>
                            <th>Nombre de jours</th>
                            <th>Statut</th>
                            <th>Date demande</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($conges)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Aucun congé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($conges as $conge): ?>
                                <tr>
                                    <td><?= htmlspecialchars($conge['matricule'] . ' - ' . $conge['prenom'] . ' ' . $conge['nom']) ?></td>
                                    <td><?= htmlspecialchars(ucfirst($conge['type_conge'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($conge['date_debut'])) ?> - <?= date('d/m/Y', strtotime($conge['date_fin'])) ?></td>
                                    <td><strong><?= $conge['nombre_jours'] ?></strong> jour(s)</td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'en_attente' => 'warning',
                                            'approuvé' => 'success',
                                            'refusé' => 'danger'
                                        ];
                                        $statut_text = [
                                            'en_attente' => 'En attente',
                                            'approuvé' => 'Approuvé',
                                            'refusé' => 'Refusé'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badge_class[$conge['statut']] ?>">
                                            <?= $statut_text[$conge['statut']] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($conge['date_demande'])) ?></td>
                                    <td>
                                        <?php if ($conge['statut'] === 'en_attente'): ?>
                                            <a href="conges.php?action=traiter&id=<?= $conge['id'] ?>&statut=approuvé" class="btn btn-sm btn-success" title="Approuver" onclick="return confirm('Approuver ce congé ?')">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <a href="conges.php?action=traiter&id=<?= $conge['id'] ?>&statut=refusé" class="btn btn-sm btn-danger" title="Refuser" onclick="return confirm('Refuser ce congé ?')">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="conges.php?action=edit&id=<?= $conge['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="conges.php?action=delete&id=<?= $conge['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer ce congé ?')">
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
