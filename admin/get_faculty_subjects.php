<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

include '../db_connection.php';

$faculty = $_GET['faculty'] ?? '';

if (empty($faculty)) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

// Get assigned subjects for the faculty
$sql = "SELECT subject_name FROM assign_subjects WHERE faculty_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $faculty);
$stmt->execute();
$result = $stmt->get_result();

$assigned_subjects = [];
while ($row = $result->fetch_assoc()) {
    $assigned_subjects[] = $row['subject_name'];
}

// Get all unique subject names
$sql = "SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name";
$result = $conn->query($sql);
$subjects = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
}
?>

<div class="currently-assigned">
    <h4>Currently Assigned to <?= htmlspecialchars($faculty) ?>:</h4>
    <?php if (!empty($assigned_subjects)): ?>
        <ul>
            <?php foreach ($assigned_subjects as $subject): ?>
                <li><?= htmlspecialchars($subject) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No subjects currently assigned.</p>
    <?php endif; ?>
</div>

<form method="POST" action="" id="assignForm">
    <input type="hidden" name="assign_faculty" value="<?= htmlspecialchars($faculty) ?>">
    
    <div class="subject-container">
        <div class="form-group">
            <label for="subjectSearch">Search Subjects:</label>
            <input type="text" id="subjectSearch" class="subject-search" placeholder="Type to search subjects...">
        </div>
        
        <div class="subject-list" id="subjectList">
            <?php foreach ($subjects as $subject): ?>
                <?php $is_assigned = in_array($subject, $assigned_subjects); ?>
                <div class="subject-item search-item <?= $is_assigned ? 'assigned' : '' ?>">
                    <input type="checkbox" 
                           id="subject_<?= md5($subject) ?>" 
                           name="assign_subjects[]" 
                           value="<?= htmlspecialchars($subject) ?>"
                           <?= $is_assigned ? 'checked' : '' ?>
                           class="subject-checkbox">
                    <label for="subject_<?= md5($subject) ?>">
                        <?= htmlspecialchars($subject) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <button type="submit" class="btn btn-assign" id="assignButton">Update Subject Assignments</button>
</form>