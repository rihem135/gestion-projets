<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$current_project_id = 0;

if (isset($_GET['project_id'])) {
    $current_project_id = intval($_GET['project_id']);
    $_SESSION['current_project_id'] = $current_project_id;

    // Mettre à jour last_project_id dans la table users pour l'user connecté
    $stmt = $pdo->prepare("UPDATE users SET last_project_id = :project_id WHERE id = :user_id");
    $stmt->bindParam(':project_id', $current_project_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();

} elseif (isset($_SESSION['current_project_id'])) {
    $current_project_id = $_SESSION['current_project_id'];
}

// Récupérer la liste des projets
$projects = $pdo->query("SELECT id, name FROM projects")->fetchAll(PDO::FETCH_ASSOC);

// Trouver le projet courant
$currentProject = null;
foreach ($projects as $proj) {
    if ($proj['id'] == $current_project_id) {
        $currentProject = $proj;
        break;
    }
}

// Si aucun projet valide sélectionné, fallback vers le premier projet (s'il existe)
if (!$currentProject && count($projects) > 0) {
    $currentProject = $projects[0];
    $current_project_id = $currentProject['id'];
    $_SESSION['current_project_id'] = $current_project_id; // mettre à jour la session
}

// Récupérer les détails du projet courant
$projectDetails = [
    'description' => '',
    'deadline' => ''
];

if ($currentProject) {
    $stmt = $pdo->prepare("SELECT description, deadline FROM projects WHERE id = ?");
    $stmt->execute([$currentProject['id']]);
    $projectDetails = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupérer les membres
$members = $pdo->query("SELECT id, name FROM users WHERE role = 'membre'")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tâches du projet courant
$tasks = [];
if ($currentProject) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ?");
    $stmt->execute([$currentProject['id']]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//******************************************************* */
//corrcetion de deadline cote task
  $today = date('Y-m-d');
  $projectDeadline = $projectDetails['deadline'] ?? $today;


//********************************************************** */
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestion de projets</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link href='https://cdn.boxicons.com/fonts/brands/boxicons-brands.min.css' rel='stylesheet'>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>


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
    <li class="nav-item"><a class="nav-link" href="calendar.php">📅 Calendrier</a></li>
    <li class="nav-item"><a class="nav-link" href="messaging_admin.php">💬 Messages</a></li>

    <li class="nav-item mt-3">
      <div class="d-flex align-items-center gap-2">
        <div class="border border-secondary rounded px-3 py-2 bg-white text-dark d-flex align-items-center justify-content-center"
             style="width: 170px; height: 50px; font-weight: 900; font-family: 'Courier New', monospace;">
          Add Project
        </div>
        <a href="action/add_project.php?project_id=<?= $currentProject ? $currentProject['id'] : 0 ?>" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center"
           style="width: 55px; height: 50px;">+</a>
      </div>
    </li>

    <?php foreach ($projects as $index => $project): ?>
      <li class="nav-item mt-2">
        <div class="project-item d-flex justify-content-between align-items-center">
          <span class="project-name"><?= htmlspecialchars($project['name']) ?></span>
          <div class="dropdown">
            <button class="dot-button dropdown-toggle" type="button" id="dropdownMenu<?= $index ?>" data-bs-toggle="dropdown" aria-expanded="false">⋯</button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenu<?= $index ?>">
              <li><a class="dropdown-item" href="dashboard_ad.php?project_id=<?= $project['id'] ?>">✅ Tasks</a></li>
              <li><a class="dropdown-item" href="../statistique.php?project_id=<?= $project['id'] ?>">📊 Dashboard & Reporting</a></li>
              <li><a class="dropdown-item" href="action/modifier_projet.php?id=<?= $project['id'] ?>">✏️ Update Project</a></li>
              <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#confirmDeleteProjectModal" data-project-id="<?= $project['id'] ?>">🗑️ Delete Project</a></li>
            </ul>
          </div>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Logout button fixed at bottom INSIDE the sidebar -->
  <div class="p-3 border-top mb-4">
    <a href="../logout.php" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
      <i class='bx bx-log-out'></i> Logout
    </a>
  </div>
</div>

    <!-- Main content -->
    <div class="col-10 p-4" style="background-color: #f5f5f5;"> <!--#cbd7fd;-->
      <?php if ($currentProject): ?>
       
          <!-- tableau de 4 colonnes -->
        <table class="table table-bordered mt-4" style="background-color: white;">
            <thead style="background-color: white;"> <!--class="table-light"-->
                <tr>
                    <th>Project Name</th>
                    <th>Description</th>
                    <th>Deadline</th>
                    <th style="border-right: none;"></th> <!-- Colonne vide pour le bouton -->
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($currentProject['name']) ?></td>
                    <td><?= htmlspecialchars($projectDetails['description'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($projectDetails['deadline'] ?? 'N/A') ?></td>
                    <td style="border-right: none; text-align: center;">
                        <!-- Bouton Add Task -->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                            Add Task
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>

        

        <!-- Modal Add Task -->
        <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="POST" action="action/add_task.php">
                <div class="modal-header">
                  <h5 class="modal-title" id="addTaskModalLabel">Add a Task</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="project_id" value="<?= $currentProject['id'] ?>" />
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Title</label>
                      <input type="text" name="task_title" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Assign to</label>
                      <select name="assigned_to" class="form-select">
                        <option value=""> -- Public --</option>
                        <?php foreach ($members as $m): ?>
                          <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Status</label>
                      <div class="status-select-container">
                        <div class="status-color-indicator" id="status-color-preview"></div>
                        <select name="status" id="status-select" class="form-select" required style="width: 374px;">

                          <option value="Not Started" data-color="#e0e0e0">Not Started</option>
                          <option value="In Progress" data-color="#fff9c4">In Progress</option>
                          <option value="Stuck" data-color="#ffcdd2">Stuck</option>
                          <option value="Done" data-color="#c8e6c9">Done</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Deadline</label>
                        <!--************************************************************************************-->
                        <!--     corrcetion de deadline cote task          -->
                      <input type="date" name="deadline" class="form-control" required min="<?= $today ?>" max="<?= $projectDeadline ?>">
                       <!--************************************************************************************-->
                    </div>
                    <div class="col-12 mb-3">
                      <label class="form-label">Description</label>
                      <textarea name="task_description" class="form-control" rows="3" required></textarea>
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Add Task</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Table tâches -->
          <h3 style="color:#0d6efd"><i class="bxr bx-checklist mt-3" style="color:#0d6efd" ></i> Project Tasks</h3>
          <table class="table table-bordered align-middle mt-3" style="background-color: white;">
            <thead style="background-color: white;">
              <tr>
                <th>Task</th>
                <th>Members</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Description</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tasks as $task): ?>
                <tr>
                  <td>
                    <div class="d-flex justify-content-between align-items-center">
                      <span><?= htmlspecialchars($task['title']) ?></span>
                      <div>
                        <a href="action/modifier_tache.php?id=<?= $task['id'] ?>&project_id=<?= $currentProject['id'] ?>"
                           class="btn btn-outline-warning btn-sm me-2 hover-warning">
                          ✏️
                        </a>
                        <button class="btn btn-outline-danger btn-sm hover-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#confirmDeleteTaskModal" 
                                data-task-id="<?= $task['id'] ?>">
                          🗑️
                        </button>
                      </div>
                    </div>
                  </td>
               <td>
               <?php
                if ($task['assigned_to']) {
                 $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                 $stmt->execute([$task['assigned_to']]);
                 $assignedName = $stmt->fetchColumn();
                 echo htmlspecialchars($assignedName ?? 'N/A');
                } else {
                   echo '<span class="badge bg-secondary">Public</span>';
                }
                ?>
               </td>

                  <td>
                    <select class="form-select form-select-sm status-select" data-task-id="<?= $task['id'] ?>">
                      <option value="Not Started" <?= $task['status'] === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                      <option value="In Progress" <?= $task['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                      <option value="Stuck" <?= $task['status'] === 'Stuck' ? 'selected' : '' ?>>Stuck</option>
                      <option value="Done" <?= $task['status'] === 'Done' ? 'selected' : '' ?>>Done</option>
                    </select>
                  </td>
                  <td><?= htmlspecialchars($task['deadline']) ?></td>
                  <td><?= htmlspecialchars($task['description']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

      <?php else: ?>
        <p>Aucun projet disponible.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Delete Task Modal -->
<div class="modal fade" id="confirmDeleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteTaskModalLabel">Confirm Task Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Are you sure you want to delete this task?</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-danger" id="confirmDeleteTaskBtn">Delete</a>
      </div>
    </div>
  </div>
</div>

<!-- Project Deletion Modal -->
<div class="modal fade" id="confirmDeleteProjectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="projectModalLabel">Confirm Deletion</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this project?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="#" class="btn btn-danger" id="confirmDeleteProjectBtn">Delete</a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Gestion de la suppression de projet
    var projectModal = document.getElementById('confirmDeleteProjectModal');
    var confirmBtn = document.getElementById('confirmDeleteProjectBtn');

    projectModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var projectId = button.getAttribute('data-project-id');
        confirmBtn.href = "action/supprimer_projet.php?id=" + projectId;
    });

    // Gestion de la suppression de tâche
    var deleteTaskModal = document.getElementById('confirmDeleteTaskModal');
    var deleteTaskBtn = document.getElementById('confirmDeleteTaskBtn');

    deleteTaskModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var taskId = button.getAttribute('data-task-id');
       deleteTaskBtn.href = "action/supprimer_tache.php?id=" + taskId + "&project_id=<?= $currentProject['id'] ?>";
    });

    // Mise à jour du statut via AJAX
    document.querySelectorAll('.status-select').forEach(select => {
        function updateColor() {
            select.classList.remove('status-not-started', 'status-in-progress', 'status-stuck', 'status-done');
            switch(select.value) {
                case 'Not Started': 
                    select.classList.add('status-not-started');
                    break;
                case 'In Progress': 
                    select.classList.add('status-in-progress');
                    break;
                case 'Stuck': 
                    select.classList.add('status-stuck');
                    break;
                case 'Done': 
                    select.classList.add('status-done');
                    break;
            }
        }

        updateColor();

        select.addEventListener('change', function () {
            updateColor();
            const taskId = this.getAttribute('data-task-id');
            const newStatus = this.value;

            fetch('action/update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `task_id=${encodeURIComponent(taskId)}&status=${encodeURIComponent(newStatus)}`
            })
            .then(res => res.text())
            .then(response => {
                if (response.trim() !== 'success') {
                    alert("Erreur lors de la mise à jour du statut !");
                }
            })
            .catch(() => alert("Problème de connexion au serveur"));
        });
    });
});

// Initialisation de Select2 pour le statut
$(document).ready(function() {
    // Fonction pour mettre à jour l'indicateur de couleur
    function updateStatusColor() {
        const selectedOption = $('#status-select option:selected');
        const color = selectedOption.data('color');
        $('#status-color-preview').css('background-color', color);
    }

    // Initialiser Select2
    $('#status-select').select2({
        minimumResultsForSearch: -1,
        dropdownParent: $('#addTaskModal'),
        templateResult: function(state) {
            if (!state.id) return state.text;
            const $state = $(
                '<span class="status-option ' + state.id.toLowerCase().replace(' ', '-') + '-option">' + 
                state.text + '</span>'
            );
            return $state;
        },
        templateSelection: function(state) {
            if (!state.id) return state.text;
            return $('<span>' + state.text + '</span>');
        }
    });

    // Mettre à jour la couleur au chargement
    updateStatusColor();

    // Mettre à jour la couleur quand la sélection change
    $('#status-select').on('change', function() {
        updateStatusColor();
    });
});
</script>

</body>
</html>