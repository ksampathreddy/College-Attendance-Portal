<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_teachers'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            foreach ($_POST['teachers'] as $username) {
                // First delete any attendance records for this teacher
                $attendance_sql = "DELETE FROM attendance WHERE faculty = ?";
                $attendance_stmt = $conn->prepare($attendance_sql);
                $attendance_stmt->bind_param("s", $username);
                $attendance_stmt->execute();
                $attendance_stmt->close();
                
                // Then delete subject assignments
                $assign_sql = "DELETE FROM assign_subjects WHERE faculty_name = ?";
                $assign_stmt = $conn->prepare($assign_sql);
                $assign_stmt->bind_param("s", $username);
                $assign_stmt->execute();
                $assign_stmt->close();
                
                // Finally delete the teacher
                $teacher_sql = "DELETE FROM teachers WHERE username = ?";
                $teacher_stmt = $conn->prepare($teacher_sql);
                $teacher_stmt->bind_param("s", $username);
                $teacher_stmt->execute();
                $teacher_stmt->close();
            }
            
            // Commit transaction if all deletions were successful
            $conn->commit();
            echo "<div class='alert alert-success'>Selected faculty and all related records deleted successfully!</div>";
        } catch (Exception $e) {
            // Roll back transaction if any error occurs
            $conn->rollback();
            echo "<div class='alert alert-error'>Error deleting faculty: " . $e->getMessage() . "</div>";
        }
    }elseif (isset($_POST['delete_students'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            foreach ($_POST['students'] as $rollno) {
                // First delete attendance records for this student
                $attendance_sql = "DELETE FROM attendance WHERE rollno = ?";
                $attendance_stmt = $conn->prepare($attendance_sql);
                $attendance_stmt->bind_param("s", $rollno);
                $attendance_stmt->execute();
                $attendance_stmt->close();
                
                // Then delete the student
                $student_sql = "DELETE FROM students WHERE rollno = ?";
                $student_stmt = $conn->prepare($student_sql);
                $student_stmt->bind_param("s", $rollno);
                $student_stmt->execute();
                $student_stmt->close();
            }
            
            // Commit transaction if all deletions were successful
            $conn->commit();
            echo "<div class='alert alert-success'>Selected students and their attendance records deleted successfully!</div>";
        } catch (Exception $e) {
            // Roll back transaction if any error occurs
            $conn->rollback();
            echo "<div class='alert alert-error'>Error deleting students: " . $e->getMessage() . "</div>";
        }
    } elseif (isset($_POST['delete_attendance'])) {
        $batch = $_POST['batch'];
        $branch = $_POST['branch'];
        $semester = $_POST['semester'];

        // Delete attendance records
        $sql = "DELETE FROM attendance 
                WHERE batch = ? AND branch = ? AND semester = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $batch, $branch, $semester);
        
        if ($stmt->execute()) {
            $rowsAffected = $stmt->affected_rows;
            echo "<div class='alert alert-success'>$rowsAffected attendance records for Batch $batch, Branch $branch, Semester $semester have been erased successfully!</div>";
        } else {
            echo "<div class='alert alert-error'>Error erasing attendance records.</div>";
        }
        $stmt->close();
    }
}

// Get distinct batches and branches from students table
$batches = [];
$branches = [];
$semesters = [];

$batch_query = "SELECT DISTINCT batch FROM students ORDER BY batch DESC";
$branch_query = "SELECT DISTINCT branch FROM students ORDER BY branch";
$semester_query = "SELECT DISTINCT semester FROM attendance ORDER BY semester";

if ($result = $conn->query($batch_query)) {
    while ($row = $result->fetch_assoc()) {
        $batches[] = $row['batch'];
    }
    $result->free();
}

if ($result = $conn->query($branch_query)) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row['branch'];
    }
    $result->free();
}

if ($result = $conn->query($semester_query)) {
    while ($row = $result->fetch_assoc()) {
        $semesters[] = $row['semester'];
    }
    $result->free();
}

// Check which tab to show by default
$show_tab = 'teachers';
if (isset($_GET['batch_filter']) || isset($_GET['branch_filter'])) {
    $show_tab = 'students';
} elseif (isset($_GET['attendance_tab'])) {
    $show_tab = 'attendance';
}
?>
<style>
    .t{
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }

        
</style>

<h2 class="text-center">Erase Data</h2>

<div class="tab-container">
    <button class="tab-button <?= $show_tab == 'teachers' ? 'active' : '' ?>" onclick="openTab(event, 'teachers-tab')">Faculty</button>
    <button class="tab-button <?= $show_tab == 'students' ? 'active' : '' ?>" onclick="openTab(event, 'students-tab')">Students</button>
    <button class="tab-button <?= $show_tab == 'attendance' ? 'active' : '' ?>" onclick="openTab(event, 'attendance-tab')">Attendance</button>
</div>
<div class="t">
<div id="teachers-tab" class="tab-content" style="display: <?= $show_tab == 'teachers' ? 'block' : 'none' ?>;">
    <h3 style="text-align:center">Delete Faculty</h3>
    <form method="POST" action="">
        <table>
            <tr>
                <th>Select</th>
                <th>Username</th>
            </tr>
            <?php
            $sql = "SELECT username FROM teachers";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><input type='checkbox' name='teachers[]' value='" . htmlspecialchars($row['username']) . "'></td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='2'>No faculty found.</td></tr>";
            }
            ?>
        </table>
        <button type="submit" name="delete_teachers" class="btn btn-danger">Delete Selected Faculty</button>
    </form>
</div>
     
<div id="students-tab" class="tab-content" style="display: <?= $show_tab == 'students' ? 'block' : 'none' ?>;">
    <h3 style="text-align:center">Delete Students</h3>
    <form method="POST" action="" id="students-form">
        <div class="form-ro">
            <div class="form-group">
                <label for="batch-filter">Filter by Batch</label>
                <select id="batch-filter" name="batch_filter" class="form-control">
                    <option value="">Select Batch</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= htmlspecialchars($b) ?>" <?= (isset($_GET['batch_filter']) && $_GET['batch_filter'] == $b) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="branch-filter">Filter by Branch</label>
                <select id="branch-filter" name="branch_filter" class="form-control">
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $br): ?>
                        <option value="<?= htmlspecialchars($br) ?>" <?= (isset($_GET['branch_filter']) && $_GET['branch_filter'] == $br) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($br) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="button" onclick="filterStudents()" class="btn">Filter</button>
        
        <table>
            <tr>
                <th><input type="checkbox" id="select-all" onclick="toggleSelectAll(this)"></th>
                <th>Roll No</th>
                <th>Name</th>
                <th>Batch</th>
                <th>Branch</th>
            </tr>
            <?php
            $batch_filter = isset($_GET['batch_filter']) ? $_GET['batch_filter'] : '';
            $branch_filter = isset($_GET['branch_filter']) ? $_GET['branch_filter'] : '';
            
            $sql = "SELECT rollno, name, batch, branch FROM students WHERE 1=1";
            if (!empty($batch_filter)) {
                $sql .= " AND batch = ?";
            }
            if (!empty($branch_filter)) {
                $sql .= " AND branch = ?";
            }
            
            $stmt = $conn->prepare($sql);
            
            if (!empty($batch_filter) && !empty($branch_filter)) {
                $stmt->bind_param("ss", $batch_filter, $branch_filter);
            } elseif (!empty($batch_filter)) {
                $stmt->bind_param("s", $batch_filter);
            } elseif (!empty($branch_filter)) {
                $stmt->bind_param("s", $branch_filter);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td><input type='checkbox' name='students[]' value='" . htmlspecialchars($row['rollno']) . "' checked></td>";
                    echo "<td>" . htmlspecialchars($row['rollno']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['batch']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['branch']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No students found.</td></tr>";
            }
            ?>
        </table>
        <button type="submit" name="delete_students" class="btn btn-danger" 
                onclick="return confirm('Are you sure you want to delete these students? This will also delete all their attendance records and cannot be undone.')">
            Delete Selected Students
        </button>
    </form>
</div>

<div id="attendance-tab" class="tab-content" style="display: <?= $show_tab == 'attendance' ? 'block' : 'none' ?>;">
    <h3 style="text-align:center">Delete Attendance Records</h3>
    <form method="POST" action="" class="vertical-form">
        <div class="form-group">
            <label for="batch">Batch:</label>
            <select id="batch" name="batch" required class="form-control">
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="branch">Branch:</label>
            <select id="branch" name="branch" required class="form-control">
                <option value="">Select Branch</option>
                <?php foreach ($branches as $br): ?>
                    <option value="<?= htmlspecialchars($br) ?>"><?= htmlspecialchars($br) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="semester">Semester:</label>
            <select id="semester" name="semester" required class="form-control">
                <option value="">Select Semester</option>
                <?php foreach ($semesters as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <button type="submit" name="delete_attendance" class="btn btn-danger" 
                    onclick="return confirm('Are you sure you want to delete all attendance records for this batch, branch and semester? This action cannot be undone.')">
                Delete Attendance Records
            </button>
            
        </div>
    </form>
                </div>
</div>
<a href="admin_dashboard.php" class="back-link" style="font-size:20px">← Back to Dashboard</a>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tabbuttons;
    
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    
    tabbuttons = document.getElementsByClassName("tab-button");
    for (i = 0; i < tabbuttons.length; i++) {
        tabbuttons[i].className = tabbuttons[i].className.replace(" active", "");
    }
    
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
    
    // Update URL without reloading
    const params = new URLSearchParams(window.location.search);
    params.delete('batch_filter');
    params.delete('branch_filter');
    params.delete('attendance_tab');
    
    if (tabName === 'students-tab') {
        window.history.pushState({}, '', '?action=erase&batch_filter=' + document.getElementById('batch-filter').value + 
                              '&branch_filter=' + document.getElementById('branch-filter').value);
    } else if (tabName === 'attendance-tab') {
        window.history.pushState({}, '', '?action=erase&attendance_tab=1');
    } else {
        window.history.pushState({}, '', '?action=erase');
    }
}
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('#students-form input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        if (checkbox !== source) { // Don't toggle the "Select All" checkbox itself
            checkbox.checked = source.checked;
        }
    });
}

// Auto-check all checkboxes when filter is applied
function filterStudents() {
    var batch = document.getElementById('batch-filter').value;
    var branch = document.getElementById('branch-filter').value;
    window.location.href = '?action=erase&batch_filter=' + encodeURIComponent(batch) + '&branch_filter=' + encodeURIComponent(branch) + '#students-tab';
    
    // After filter, all checkboxes will be checked by default (see PHP code)
}

// Check if we're coming from a filter action and check all boxes
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.search.includes('batch_filter') || window.location.search.includes('branch_filter')) {
        const checkboxes = document.querySelectorAll('#students-form input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        // Also check the "Select All" checkbox
        document.getElementById('select-all').checked = true;
    }
});
</script>
<style>
.back-link {
            display: block;
            margin-top: 50px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            font-size:20px;
        }
</style>