<?php
$pageTitle = 'Gestion des Présences';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $employe_id = $_POST['employe_id'] ?? '';
        $date_presence = $_POST['date_presence'] ?? '';
        $heure_arrivee = $_POST['heure_arrivee'] ?? null;
        $heure_depart = $_POST['heure_depart'] ?? null;
        $statut = $_POST['statut'] ?? 'présent';
        $remarques = $_POST['remarques'] ?? '';
        
        // Calcul des heures travaillées
        $heures_travaillees = 0;
        if ($heure_arrivee && $heure_depart) {
            $arrivee = new DateTime($heure_arrivee);
            $depart = new DateTime($heure_depart);
            $diff = $arrivee->diff($depart);
            $heures_travaillees = $diff->h + ($diff->i / 60);
        }

        if ($action === 'add') {
            $stmt = $db->prepare("INSERT INTO presences (employe_id, date_presence, heure_arrivee, heure_depart, heures_travaillees, statut, remarques) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employe_id, $date_presence, $heure_arrivee ?: null, $heure_depart ?: null, $heures_travaillees, $statut, $remarques]);
            header('Location: presences.php?success=1');
        } else {
            $stmt = $db->prepare("UPDATE presences SET employe_id=?, date_presence=?, heure_arrivee=?, heure_depart=?, heures_travaillees=?, statut=?, remarques=? WHERE id=?");
            $stmt->execute([$employe_id, $date_presence, $heure_arrivee ?: null, $heure_depart ?: null, $heures_travaillees, $statut, $remarques, $id]);
            header('Location: presences.php?success=1');
        }
        exit();
    }
}

// Suppression
if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM presences WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: presences.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $presence = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM presences WHERE id = ?");
        $stmt->execute([$id]);
        $presence = $stmt->fetch();
        if (!$presence) {
            header('Location: presences.php');
            exit();
        }
    }
    
    $stmt = $db->query("SELECT id, matricule, nom, prenom FROM employes WHERE statut = 'actif' ORDER BY nom, prenom");
    $employes = $stmt->fetchAll();
    ?>
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">
                <i class="bi bi-<?= $action === 'add' ? 'clock-history' : 'pencil' ?>"></i>
                <?= $action === 'add' ? 'Enregistrer une présence' : 'Modifier une présence' ?>
            </h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Employé *</label>
                            <select class="form-select" name="employe_id" required>
                                <option value="">Sélectionner un employé</option>
                                <?php foreach ($employes as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= ($presence['employe_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['matricule'] . ' - ' . $emp['prenom'] . ' ' . $emp['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date *</label>
                                <input type="date" class="form-control" name="date_presence" value="<?= $presence['date_presence'] ?? date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statut *</label>
                                <select class="form-select" name="statut" required>
                                    <option value="présent" <?= ($presence['statut'] ?? '') === 'présent' ? 'selected' : '' ?>>Présent</option>
                                    <option value="absent" <?= ($presence['statut'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
                                    <option value="retard" <?= ($presence['statut'] ?? '') === 'retard' ? 'selected' : '' ?>>Retard</option>
                                    <option value="congé" <?= ($presence['statut'] ?? '') === 'congé' ? 'selected' : '' ?>>Congé</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Heure d'arrivée</label>
                                <input type="time" class="form-control" name="heure_arrivee" value="<?= $presence['heure_arrivee'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Heure de départ</label>
                                <input type="time" class="form-control" name="heure_depart" value="<?= $presence['heure_depart'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarques</label>
                            <textarea class="form-control" name="remarques" rows="3"><?= htmlspecialchars($presence['remarques'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Enregistrer
                            </button>
                            <a href="presences.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
} else {
    $date_filter = $_GET['date'] ?? date('Y-m-d');
    
    $stmt = $db->prepare("SELECT p.*, e.matricule, e.nom, e.prenom FROM presences p JOIN employes e ON p.employe_id = e.id WHERE p.date_presence = ? ORDER BY p.heure_arrivee");
    $stmt->execute([$date_filter]);
    $presences = $stmt->fetchAll();
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><i class="bi bi-clock-history"></i> Gestion des Présences</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="presences.php?action=add" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Enregistrer une présence
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
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="date" class="form-control" name="date" value="<?= $date_filter ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employé</th>
                            <th>Date</th>
                            <th>Heure arrivée</th>
                            <th>Heure départ</th>
                            <th>Heures travaillées</th>
                            <th>Statut</th>
                            <th>Remarques</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($presences)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Aucune présence enregistrée pour cette date</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($presences as $pres): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pres['matricule'] . ' - ' . $pres['prenom'] . ' ' . $pres['nom']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($pres['date_presence'])) ?></td>
                                    <td><?= $pres['heure_arrivee'] ? date('H:i', strtotime($pres['heure_arrivee'])) : '-' ?></td>
                                    <td><?= $pres['heure_depart'] ? date('H:i', strtotime($pres['heure_depart'])) : '-' ?></td>
                                    <td><?= number_format($pres['heures_travaillees'], 2) ?> h</td>
                                    <td>
                                        <?php
                                        $badge_class = [
                                            'présent' => 'success',
                                            'absent' => 'danger',
                                            'retard' => 'warning',
                                            'congé' => 'info'
                                        ];
                                        ?>
                                        <span class="badge bg-<?= $badge_class[$pres['statut']] ?>">
                                            <?= ucfirst($pres['statut']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($pres['remarques'] ?? '') ?></td>
                                    <td>
                                        <a href="presences.php?action=edit&id=<?= $pres['id'] ?>" class="btn btn-sm btn-primary" title="Modifier">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="presences.php?action=delete&id=<?= $pres['id'] ?>" class="btn btn-sm btn-danger" title="Supprimer" onclick="return confirm('Supprimer cette présence ?')">
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
