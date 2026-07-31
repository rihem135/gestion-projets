<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'membre') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupère l'ID du projet depuis l'URL s’il existe
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// ✅ Correction : Inclure les projets contenant des tâches publiques aussi
$stmt = $pdo->prepare("SELECT DISTINCT p.* 
                       FROM projects p 
                       JOIN tasks t ON t.project_id = p.id 
                       WHERE t.assigned_to = :uid 
                          OR (t.assigned_to IS NULL AND t.is_public = 1)");
$stmt->execute(['uid' => $user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour récupérer les tâches de l’utilisateur dans un projet
function getUserTasks($pdo, $project_id, $user_id) {
    $stmt = $pdo->prepare("SELECT t.* 
                           FROM tasks t 
                           WHERE t.project_id = ?
                             AND (
                                 t.assigned_to = ? 
                                 OR (t.assigned_to IS NULL AND t.is_public = 1)
                             )");
    $stmt->execute([$project_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Définir les couleurs par statut
$statusColors = [
    'Not Started' => '#e0e0e0',
    'In Progress' => '#fff9c4',
    'Stuck'       => '#ffcdd2',
    'Done'        => '#c8e6c9'
];
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Sidebar */
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding: 1rem;
            width: 220px;
            position: fixed;
            color: #fff;
        }
        .content {
            margin-left: 240px;
            padding: 2rem;
        }
        .sidebar a {
            display: block;
            margin: 0.75rem 0;
            text-decoration: none;
            color: #fff;
            padding: 0.5rem;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .card {
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 2rem;
        }
    </style>
    <style>
    .project-item {
      padding: 10px 12px;
      border-radius: 5px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      transition: background-color 0.2s ease;
      position: relative;
    }
    .project-item:hover {
      background-color: #f1f1f1;
    }
    .dot-button.dropdown-toggle::after {
      display: none;
    }
    .dropdown-menu {
      min-width: 180px;
      position: absolute !important;
      top: 0;
      left: 100%;
      margin-left: 10px;
      z-index: 1000;
      display: none;
    }
    .dropdown.show .dropdown-menu {
      display: block;
    }
    .project-name {
      font-weight: 500;
    }
    .dot-button {
      background: none;
      border: none;
      font-size: 22px;
      color: #6c757d;
      opacity: 0.7;
    }
    .dot-button:hover {
      opacity: 1;
      color: #000;
    }

    .colored-box {
      border: 2px solid;
      border-radius: 6px;
      padding: 10px;
      transition: border-color 0.3s, background-color 0.3s;
    }

    /* Couleurs selon le statut */
    .status-not-started {
      background-color: #e0e0e0 !important;
      color: #000;
      border-color: #9e9e9e !important;
    }

    .status-in-progress {
      background-color: #fff9c4 !important;
      color: #000;
      border-color: #fbc02d !important;
    }

    .status-stuck {
      background-color: #ffcdd2 !important;
      color: #000;
      border-color: #d32f2f !important;
    }

    .status-done {
      background-color: #c8e6c9 !important;
      color: #000;
      border-color: #388e3c !important;
    }

    .select2-container--default .select2-selection--single {
      border: 1px solid #ced4da;
      height: 38px;
      border-radius: 0.25rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      line-height: 36px;
      padding-left: 40px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
      height: 36px;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
      background-color: #f8f9fa;
      color: #212529;
    }

    .btn-outline-warning:hover {
      background-color: #ffc107;
      color: white;
      border-color: #ffc107;
    }

    .btn-outline-danger:hover {
      background-color: #dc3545;
      color: white;
      border-color: #dc3545;
    }
   
    #status-select {
      min-width: 100%;
    }

    /* Nouveaux styles pour l'indicateur de couleur */
    .status-select-container {
      position: relative;
    }

    .status-select-container .status-select {
      padding-left: 40px !important;
      background-color: transparent !important;
    }

    .status-color-indicator {
      position: absolute;
      left: 8px;
      top: 50%;
      transform: translateY(-50%);
      width: 24px;
      height: 24px;
      border-radius: 4px;
      z-index: 5;
    }

    /* Style pour les options du dropdown */
    .status-option {
      padding-left: 30px !important;
      position: relative;
    }

    .status-option::before {
      content: "";
      position: absolute;
      left: 8px;
      top: 50%;
      transform: translateY(-50%);
      width: 16px;
      height: 16px;
      border-radius: 3px;
    }

    .status-not-started-option::before {
      background-color: #e0e0e0;
    }

    .status-in-progress-option::before {
      background-color: #fff9c4;
    }

    .status-stuck-option::before {
      background-color: #ffcdd2;
    }

    .status-done-option::before {
      background-color: #c8e6c9;
    }
  </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-2 border-end vh-100 d-flex flex-column position-relative">
            <h4 class="mt-3">Sidebar</h4>
            <ul class="nav flex-column flex-grow-1">
              <li class="nav-item"><a class="nav-link" href="calendrier_me.php">📅 Calendrier</a></li>
              <li class="nav-item"><a class="nav-link" href="messaging_user.php">💬 Messages</a></li>
                <li class="nav-item"><h5 class="mt-3">Projects</h5></li>
                <?php foreach ($projects as $index => $project): ?>
                    <li class="nav-item mt-2">
                        <div class="project-item d-flex justify-content-between align-items-center">
                            <span class="project-name"><?= htmlspecialchars($project['name']) ?></span>
                            <div class="dropdown">
            <button class="dot-button dropdown-toggle" type="button" id="dropdownMenu<?= $index ?>" data-bs-toggle="dropdown" aria-expanded="false">⋯</button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenu<?= $index ?>">
              <li><a class="dropdown-item" href="dashboard_me.php?project_id=<?= $project['id'] ?>">✅ Tasks</a></li>
              <li><a class="dropdown-item" href="../statistique_me.php?project_id=<?= $project['id'] ?>">📊 Dashboard & Reporting</a></li>
              </ul>
          </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="p-3 border-top mb-4">
                <a href="../logout.php" class="btn btn-outline-danger w-100">Logout</a>
            </div>
        </div>

        <!-- Content -->
        <div class="col-10 p-4" style="background-color: #f5f5f5;">
            <h1 class="d-flex align-items-center">
                <i class="bi bi-person-circle me-2 text-primary" style="font-size: 2rem;"></i>
                <?= htmlspecialchars($_SESSION['name']) ?>
            </h1>

            <!-- 👈 MODIF : Affiche bouton retour -->
            <?php if ($selected_project_id): ?>
                <a href="dashboard_me.php" class="btn btn-outline-secondary mb-4">⬅️ All Projects</a>
            <?php endif; ?>

            <?php if (empty($projects)): ?>
                <div class="alert alert-info">No projects linked to your tasks at the moment.</div>
            <?php endif; ?>

            <?php foreach ($projects as $project): ?>
                <?php if ($selected_project_id && $project['id'] != $selected_project_id) continue; ?> <!-- 👈 MODIF -->

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white fw-bold">
                        <?= htmlspecialchars($project['name']) ?>
                    </div>
                    <div class="card-body">
                        <?php $tasks = getUserTasks($pdo, $project['id'], $user_id); ?>

                        <?php if (empty($tasks)): ?>
                            <div class="alert alert-secondary">No tasks assigned in this project.</div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4 mt-3">
                                <?php foreach ($tasks as $task): ?>
                                    <div class="col">
                                        <div class="card h-100" style="background-color: <?= $statusColors[$task['status']] ?? '#ffffff' ?>">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($task['title']) ?></h5>
                                                <p class="card-text"><?= nl2br(htmlspecialchars($task['description'])) ?></p>

                                                <!-- Status label and select as you have -->
<label for="status_<?= $task['id'] ?>" class="form-label">Status:</label>
<select class="form-select form-select-sm status-select"
        id="status_<?= $task['id'] ?>"
        data-task-id="<?= $task['id'] ?>"
        <?= is_null($task['assigned_to']) ? 'disabled' : '' // disable if public ?>>
    <?php foreach (['Not Started', 'In Progress', 'Stuck', 'Done'] as $status): ?>
        <option value="<?= $status ?>" <?= $task['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
    <?php endforeach; ?>
</select>

<?php if (is_null($task['assigned_to']) && $task['is_public'] == 1): ?>
    <small class="text-muted d-block mt-1">Assigned to: <strong>Public</strong></small>
    <button class="btn btn-sm btn-outline-primary" 
        data-bs-toggle="modal" 
        data-bs-target="#confirmClaimTaskModal" 
        data-task-id="<?= $task['id'] ?>">
    Claim this task
</button>
<?php else: ?>
    <small class="text-muted d-block mt-1">Assigned to: You</small>
<?php endif; ?>


<!-- Modal -->
<div class="modal fade" id="confirmClaimTaskModal" tabindex="-1" aria-labelledby="confirmClaimTaskLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="confirmClaimTaskLabel">Claim Task</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to claim this public task?
      </div>
      <div class="modal-footer">
        <a href="#" id="confirmClaimTaskBtn" class="btn btn-success">Yes, claim it</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>


                                                <?php if (!empty($task['deadline'])): ?>
                                                    <div class="mt-2">
                                                        <span class="badge bg-light text-dark deadline-badge">
                                                            <i class="bi bi-calendar-event me-1"></i>
                                                            Deadline: <?= htmlspecialchars(date('d/m/Y', strtotime($task['deadline']))) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- JS pour status update -->
<script>
const statusColors = {
    "Not Started": "#e0e0e0",
    "In Progress": "#fff9c4",
    "Stuck": "#ffcdd2",
    "Done": "#c8e6c9"
};

$(document).ready(function () {
    $('.status-select').on('change', function () {
        const select = $(this);
        const taskId = select.data('task-id');
        const status = select.val();
        const card = select.closest('.card');

        $.post('action/update_status_me.php', { task_id: taskId, status: status })
            .done(function () {
                card.css('background-color', statusColors[status] || '#ffffff');
            })
            .fail(function () {
                alert('Error while updating status.');
            });
    });
});
</script>

<!-- nouveau code modifier ajourd'hui******************************************************* -->
<!-- JS pour modale de claim  -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const claimModal = document.getElementById('confirmClaimTaskModal');
    const confirmBtn = document.getElementById('confirmClaimTaskBtn');

    claimModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const taskId = button.getAttribute('data-task-id');

        // 🧠 Récupérer l'ID du projet courant depuis l'URL
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('project_id');

        // ✅ Ajouter project_id à l'URL si présent
        let targetUrl = "action/confirmer_tache.php?id=" + taskId;
        if (projectId) {
            targetUrl += "&project_id=" + projectId;
        }

        confirmBtn.href = targetUrl;
    });
});
</script>
<!--************************************************************************************-->

<script>

    // Gestion du dropdown "3 points"
    const dotButtons = document.querySelectorAll('.dot-button');
    dotButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                if (dropdown !== button.parentElement) {
                    dropdown.classList.remove('show');
                }
            });

            // Ouvrir/fermer ce dropdown
            button.parentElement.classList.toggle('show');
        });
    });

    // Fermer dropdown si on clique ailleurs
    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    });


</script>


</body>
</html>
