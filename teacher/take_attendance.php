<?php
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

// Initialize variables
$message = '';
$batch = $_POST['batch'] ?? '';
$branch = $_POST['branch'] ?? '';
$section = $_POST['section'] ?? '';
$subject_name = $_POST['subject'] ?? '';
$semester = $_POST['semester'] ?? '';
$date = $_POST['date'] ?? date('Y-m-d');
$today = date('Y-m-d');
$students = [];
$attendance_saved = false;
$faculty_name = $_SESSION['username']; // Get the logged-in teacher's username

// Semester options
$semester_options = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];

// Get distinct batches, branches from students table
$batches = $conn->query("SELECT DISTINCT batch FROM students ORDER BY batch DESC")->fetch_all(MYSQLI_ASSOC);
$branches = $conn->query("SELECT DISTINCT branch FROM students ORDER BY branch")->fetch_all(MYSQLI_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['get_students'])) {
        // Validate inputs
        if (empty($batch) || empty($branch) || empty($semester) || empty($subject_name) || empty($date)) {
            $message = '<div class="alert alert-danger">All fields except section are required!</div>';
        } else {
            // Check for existing attendance
            $checkQuery = "SELECT COUNT(*) as count FROM attendance 
                          WHERE batch = ? AND branch = ? AND semester = ? 
                          AND subject = ? AND date = ? AND faculty = ?";
            $params = [$batch, $branch, $semester, $subject_name, $date, $faculty_name];
            
            if (!empty($section)) {
                $checkQuery .= " AND section = ?";
                $params[] = $section;
            } else {
                $checkQuery .= " AND (section IS NULL OR section = '')";
            }
            
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $message = '<div class="alert alert-danger">Attendance already recorded for this combination.</div>';
            } else {
                try {
                    // Fetch students
                    $query = "SELECT rollno, name FROM students 
                             WHERE batch = ? AND branch = ?";
                    $params = [$batch, $branch];
                    
                    if (!empty($section)) {
                        $query .= " AND (section = ? OR section IS NULL)";
                        $params[] = $section;
                    }
                    
                    $query .= " ORDER BY rollno";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    $students = $result->num_rows > 0 ? $result->fetch_all(MYSQLI_ASSOC) : [];
                    if (empty($students)) {
                        $message = '<div class="alert alert-warning">No students found.</div>';
                    }
                } catch (mysqli_sql_exception $e) {
                    $message = '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
                }
            }
        }
    } elseif (isset($_POST['save_attendance'])) {
        // Save attendance - default all students to Absent, then mark Present for checked ones
        $conn->begin_transaction();
        try {
            // First get all student rollnos for this batch/branch/section
            $query = "SELECT rollno FROM students WHERE batch = ? AND branch = ?";
            $params = [$batch, $branch];
            
            if (!empty($section)) {
                $query .= " AND (section = ? OR section IS NULL)";
                $params[] = $section;
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $all_students = $result->fetch_all(MYSQLI_ASSOC);
            
            // Insert attendance for all students (default Absent)
            foreach ($all_students as $student) {
                $status = isset($_POST['present_students'][$student['rollno']]) ? 'Present' : 'Absent';
                
                $query = "INSERT INTO attendance 
                         (rollno, batch, branch, section, semester, subject, date, status, faculty) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssssssss", $student['rollno'], $batch, $branch, $section, 
                                 $semester, $subject_name, $date, $status, $faculty_name);
                $stmt->execute();
            }
            
            $conn->commit();
            $message = '<div class="alert alert-success">Attendance saved successfully!</div>';
            $attendance_saved = true;
            $students = [];
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f5f5f5; }
        .view-container {
            max-width: 750px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            /* background: white; */
            

        }
        .form-group { margin-bottom: 20px; }
        .table th { background-color: #800000; color: white; }
        .present { color: green; font-weight: bold; }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }
        .checkbox-cell {
            text-align: center;
        }
        .mark-all-container {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <main class="container">
        <div class="view-container">
            <h2 class="text-center mb-4">Post Attendance</h2>
            
            <?php if (!empty($message)) echo $message; ?>
            
            <form method="POST" id="attendanceForm">
            
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="batch" class="form-label">Batch</label>
                            <select id="batch" name="batch" class="form-control" required>
                                <option value="">Select Batch</option>
                                <?php foreach ($batches as $b): ?>
                                    <option value="<?= htmlspecialchars($b['batch']) ?>" <?= $batch == $b['batch'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['batch']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch" class="form-label">Branch</label>
                            <select id="branch" name="branch" class="form-control" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $br): ?>
                                    <option value="<?= htmlspecialchars($br['branch']) ?>" <?= $branch == $br['branch'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($br['branch']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
               

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="section" class="form-label">Section (Optional)</label>
                            <select id="section" name="section" class="form-control">
                                <option value="">All Sections</option>
                                <?php 
                                if (!empty($batch) && !empty($branch)) {
                                    $sections = $conn->query("SELECT DISTINCT section FROM students 
                                                            WHERE batch = '$batch' AND branch = '$branch' 
                                                            AND section IS NOT NULL ORDER BY section")
                                                    ->fetch_all(MYSQLI_ASSOC);
                                    foreach ($sections as $sec) {
                                        echo '<option value="'.htmlspecialchars($sec['section']).'" '.
                                            ($section == $sec['section'] ? 'selected' : '').'>'.
                                            htmlspecialchars($sec['section']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="semester" class="form-label">Semester</label>
                            <select id="semester" name="semester" class="form-control" required>
                                <option value="">Select Semester</option>
                                <?php foreach ($semester_options as $sem): ?>
                                    <option value="<?= htmlspecialchars($sem) ?>" <?= $semester == $sem ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sem) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
             

                
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="subject" class="form-label">Subject</label>
                            <select id="subject" name="subject" class="form-control" required disabled>
                                <option value="">Select Branch and Semester First</option>
                                <?php if (!empty($subject_name)): ?>
                                    <option value="<?= htmlspecialchars($subject_name) ?>" selected>
                                        <?= htmlspecialchars($subject_name) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" name="date" class="form-control" 
                                   value="<?= htmlspecialchars($date) ?>" max="<?= $today ?>" required>
                        </div>
                    </div>
           

                <div class="form-group text-center">
                    <button type="submit" name="get_students" class="btn btn-primary">Get Students</button>
                </div>
            </form>

            <?php if (!empty($students) && !$attendance_saved): ?>
            <div class="mt-4">
                <h4>Student List - <?= htmlspecialchars($subject_name) ?></h4>
                <div class="mark-all-container">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="markAllCheckbox">
                        <label class="form-check-label" for="markAllCheckbox">Mark All Present</label>
                    </div>
                    <div id="marked-count" class="text-muted mt-2">0 students marked as present</div>
                </div>
                
                <form method="POST" id="attendance-record-form">
                    <input type="hidden" name="batch" value="<?= htmlspecialchars($batch) ?>">
                    <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
                    <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
                    <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
                    <input type="hidden" name="subject" value="<?= htmlspecialchars($subject_name) ?>">
                    <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th class="checkbox-cell">Present</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['rollno']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td class="checkbox-cell">
                                        <div class="form-check">
                                            <input class="form-check-input present-checkbox" type="checkbox" 
                                                   name="present_students[<?= $student['rollno'] ?>]" 
                                                   id="present_<?= $student['rollno'] ?>" 
                                                   value="1">
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" name="save_attendance" class="btn btn-success">Submit Attendance</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <a href="teacher_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Load subjects when branch and semester are selected
        $('#branch, #semester').change(function() {
            const branch = $('#branch').val();
            const semester = $('#semester').val();
            const subjectDropdown = $('#subject');
            
            if (!branch || !semester) {
                subjectDropdown.prop('disabled', true).html('<option value="">Select Branch and Semester First</option>');
                return;
            }
            
            subjectDropdown.prop('disabled', false).html('<option value="">Loading...</option>');
            
            $.ajax({
                url: 'get_subjects.php',
                type: 'GET',
                data: { branch: branch, semester: semester },
                success: function(response) {
                    subjectDropdown.empty().append('<option value="">Select Subject</option>');
                    if (response.error) {
                        subjectDropdown.append('<option value="">Error loading subjects</option>');
                    } else {
                        response.forEach(function(subject) {
                            subjectDropdown.append(
                                $('<option>', {
                                    value: subject.subject_name,
                                    text: subject.subject_name
                                })
                            );
                        });
                    }
                },
                error: function() {
                    subjectDropdown.empty().append('<option value="">Error loading subjects</option>');
                }
            });
        });

        // Load sections when batch and branch are selected
        $('#batch, #branch').change(function() {
            const batch = $('#batch').val();
            const branch = $('#branch').val();
            const sectionDropdown = $('#section');
            
            if (!batch || !branch) {
                sectionDropdown.html('<option value="">All Sections</option>');
                return;
            }
            
            sectionDropdown.html('<option value="">Loading...</option>');
            
            $.ajax({
                url: 'get_sections.php',
                type: 'GET',
                data: { batch: batch, branch: branch },
                success: function(response) {
                    sectionDropdown.empty().append('<option value="">All Sections</option>');
                    if (response.error) {
                        sectionDropdown.append('<option value="">Error loading sections</option>');
                    } else {
                        response.forEach(function(section) {
                            sectionDropdown.append(
                                $('<option>', {
                                    value: section.section,
                                    text: section.section
                                })
                            );
                        });
                    }
                },
                error: function() {
                    sectionDropdown.empty().append('<option value="">Error loading sections</option>');
                }
            });
        });

        // Mark all checkbox functionality
        $('#markAllCheckbox').change(function() {
            $('.present-checkbox').prop('checked', $(this).prop('checked'));
            updateMarkedCount();
        });

        // Update marked count
        function updateMarkedCount() {
            const marked = $('.present-checkbox:checked').length;
            const total = $('.present-checkbox').length;
            $('#marked-count').text(marked + ' of ' + total + ' students marked as present');
        }

        // Checkbox change handler
        $('.present-checkbox').change(function() {
            // Uncheck "Mark All" if any checkbox is unchecked
            if (!$(this).prop('checked')) {
                $('#markAllCheckbox').prop('checked', false);
            }
            // Check "Mark All" if all checkboxes are checked
            else if ($('.present-checkbox:checked').length === $('.present-checkbox').length) {
                $('#markAllCheckbox').prop('checked', true);
            }
            
            updateMarkedCount();
        });

        // Initialize
        updateMarkedCount();
    });
    </script>
</body>
</html>