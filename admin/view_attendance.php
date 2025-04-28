<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

$message = '';

// Get unique batches, branches, sections, and dates from attendance table
$batches = $branches = $sections = $dates = [];

$batchResult = $conn->query("SELECT DISTINCT batch FROM attendance ORDER BY batch DESC");
while ($row = $batchResult->fetch_assoc()) $batches[] = $row['batch'];

$branchResult = $conn->query("SELECT DISTINCT branch FROM attendance ORDER BY branch");
while ($row = $branchResult->fetch_assoc()) $branches[] = $row['branch'];

$sectionResult = $conn->query("SELECT DISTINCT section FROM attendance WHERE section IS NOT NULL AND section != '' ORDER BY section");
while ($row = $sectionResult->fetch_assoc()) $sections[] = $row['section'];

$dateResult = $conn->query("SELECT DISTINCT date FROM attendance ORDER BY date DESC");
while ($row = $dateResult->fetch_assoc()) $dates[] = $row['date'];

// Semester options array
$semester_options = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Admin - DRKIST</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <style>
        .view-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            /* background: white; */
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
            width: 100%;
            
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        select.form-control {
            height: 42px;
        }
        
        .btn {
            background-color: #007bff;
            margin: 10px 0;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
        
        .btn-excel {
            background-color: #1d6f42;
            margin-top: 20px;
        }
        
        .btn-excel:hover {
            background-color: #165a32;
        }
        
        .table-responsive {
            overflow-x: auto;
            margin: 30px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #800000;
            color: white;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f1f1f1;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        h2, h3 {
            color: #800000;
            margin-bottom: 20px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-cente {
            color: black;
            text-align: center;
        }
        
        .attendance-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .attendance-option {
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
        
        .attendance-option.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .attendance-form {
            display: none;
        }
        
        .attendance-form.active {
            display: block;
        }
        
        .present {
            color: green;
            font-weight: bold;
        }
        
        .absent {
            color: red;
            font-weight: bold;
        }
        
        .back-link {
            display: block;
            margin-top: 50px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            font-size:20px;
        }
        
        .faculty-name {
            font-size: 0.8em;
            color: #666;
            font-style: italic;
        }
        
        @media (min-width: 768px) {
            .attendance-options {
                flex-direction: row;
                justify-content: center;
            }
            
            .attendance-option {
                min-width: 120px;
            }
            
            .btn {
                width: auto;
            }
        }
    </style>
</head>
<body>
    <main class="container">
    <h3 style="text-align:center;font-size:23px">View Attendance of Students in a Class</h3>
        <div class="view-container">
       
            <div class="attendance-options">
                <div class="attendance-option <?= (!isset($_GET['view_type']) || $_GET['view_type'] == 'total' ? 'active' : '') ?>" 
                     data-target="total-attendance">Total Attendance</div>
                <div class="attendance-option <?= (isset($_GET['view_type']) && $_GET['view_type'] == 'subject' ? 'active' : '') ?>" 
                     data-target="subject-attendance">Subject Attendance</div>
                <div class="attendance-option <?= (isset($_GET['view_type']) && $_GET['view_type'] == 'date' ? 'active' : '') ?>" 
                     data-target="date-attendance">Date Attendance</div>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>
            
            <!-- Total Attendance Form -->
<section id="total-attendance" class="attendance-form <?= (!isset($_GET['view_type']) || $_GET['view_type'] == 'total' ? 'active' : '') ?>">
    <h3 style="text-align:center">Total Attendance of Semester</h3>
    <form method="GET" action="">
        <input type="hidden" name="action" value="view_attendance">
        <input type="hidden" name="view_type" value="total">
        <div class="form-group">
            <label for="batch-total">Batch</label>
            <select id="batch-total" name="batch" required class="form-control">
                <option value="">Select Batch</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= htmlspecialchars($b) ?>" <?= (isset($_GET['batch']) && (!isset($_GET['view_type']) || $_GET['view_type'] == 'total') && $_GET['batch'] == $b ? 'selected' : '') ?>>
                        <?= htmlspecialchars($b) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="branch-total">Branch</label>
            <select id="branch-total" name="branch" required class="form-control">
                <option value="">Select Branch</option>
                <?php foreach ($branches as $br): ?>
                    <option value="<?= htmlspecialchars($br) ?>" <?= (isset($_GET['branch']) && (!isset($_GET['view_type']) || $_GET['view_type'] == 'total') && $_GET['branch'] == $br ? 'selected' : '') ?>>
                        <?= htmlspecialchars($br) ?>
                    </option>
                <?php endforeach; ?>
                </select>
        </div>
        <div class="form-group">
            <label for="section-total">Section (Optional)</label>
            <select id="section-total" name="section" class="form-control">
                <option value="">All Sections</option>
                <?php foreach ($sections as $sec): ?>
                    <option value="<?= htmlspecialchars($sec) ?>" <?= (isset($_GET['section']) && (!isset($_GET['view_type']) || $_GET['view_type'] == 'total') && $_GET['section'] == $sec ? 'selected' : '') ?>>
                        <?= htmlspecialchars($sec) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="semester-total">Semester</label>
            <select id="semester-total" name="semester" required class="form-control">
                <option value="">Select Semester</option>
                <?php foreach ($semester_options as $sem): ?>
                    <option value="<?= htmlspecialchars($sem) ?>" <?= (isset($_GET['semester']) && (!isset($_GET['view_type']) || $_GET['view_type'] == 'total') && $_GET['semester'] == $sem ? 'selected' : '') ?>>
                        <?= htmlspecialchars($sem) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
       
        <button type="submit" class="btn">View Attendance</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['view_type']) && $_GET['view_type'] == 'total' && isset($_GET['batch'])) {
        $batch = $_GET['batch'];
        $branch = $_GET['branch'];
        $semester = $_GET['semester'];
        $section = isset($_GET['section']) && !empty($_GET['section']) ? $_GET['section'] : null;
        
        // Modified section condition to handle NULL sections
        $sectionCondition = $section ? "AND (section = ? OR section IS NULL OR section = '')" : "";
        $params = $section ? [$batch, $branch, $semester, $section] : [$batch, $branch, $semester];
        $paramTypes = $section ? "ssss" : "sss";
        
        // Get total classes
        $sqlTotal = "SELECT COUNT(DISTINCT CONCAT(subject, date)) as total FROM attendance 
                    WHERE batch = ? AND branch = ? AND semester = ? $sectionCondition";
        $stmtTotal = $conn->prepare($sqlTotal);
        $stmtTotal->bind_param($paramTypes, ...$params);
        $stmtTotal->execute();
        $resultTotal = $stmtTotal->get_result();
        $totalClasses = $resultTotal->fetch_assoc()['total'];
        
        // Get subjects with faculty
        $sqlSubjects = "SELECT DISTINCT subject, faculty FROM attendance 
                       WHERE batch = ? AND branch = ? AND semester = ? $sectionCondition
                       ORDER BY subject";
        $stmtSubjects = $conn->prepare($sqlSubjects);
        $stmtSubjects->bind_param($paramTypes, ...$params);
        $stmtSubjects->execute();
        $resultSubjects = $stmtSubjects->get_result();
        
        // Get students with proper section handling
$studentCondition = $section ? "AND (section = ? OR section IS NULL OR section = '')" : "";
$studentParams = $section ? [$batch, $branch, $section] : [$batch, $branch];
$studentParamTypes = $section ? "sss" : "ss";

// Simplified query with proper parameter binding
$sql = "SELECT s.rollno, s.name, 
        (
            SELECT COUNT(*) 
            FROM attendance a 
            WHERE a.rollno = s.rollno 
            AND a.batch = s.batch 
            AND a.branch = s.branch 
            AND a.semester = ?
            " . ($section ? "AND (a.section = ? OR a.section IS NULL OR a.section = '')" : "") . "
            AND a.status = 'Present'
        ) as attended,
        ? as total
        FROM students s
        WHERE s.batch = ? AND s.branch = ? " . ($section ? "AND (s.section = ? OR s.section IS NULL OR s.section = '')" : "") . "
        ORDER BY s.rollno";

if ($section) {
    // Correct parameter binding for section case
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", 
        $semester,    // For subquery semester
        $section,     // For subquery section condition
        $totalClasses, // For total
        $batch,       // For main query batch
        $branch,      // For main query branch
        $section      // For main query section condition
    );
} else {
    // Correct parameter binding for non-section case
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("siss", 
        $semester,    // For subquery semester
        $totalClasses, // For total
        $batch,       // For main query batch
        $branch       // For main query branch
    );
}
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $sectionText = $section ? ", Section: $section" : "";
            echo "<h3>Total Attendance Summary for Batch: $batch, Branch: $branch$sectionText, Semester: $semester</h3>";
            echo "<p>Total class sessions (all subjects): $totalClasses</p>";
            
            if ($resultSubjects->num_rows > 0) {
                echo "<p>Subjects: ";
                $subjects = [];
                while ($row = $resultSubjects->fetch_assoc()) {
                    $subjects[] = $row['subject'] . " (Faculty: " . $row['faculty'] . ")";
                }
                echo implode(", ", $subjects) . "</p>";
            }
            
            echo "<div class='table-responsive'>";
            echo "<table>";
            echo "<thead><tr><th>Roll No</th><th>Name</th><th>Attended</th><th>Total</th><th>Percentage</th></tr></thead>";
            echo "<tbody>";
            
            while ($row = $result->fetch_assoc()) {
                $attended = min($row['attended'], $row['total']);
                $percentage = $row['total'] > 0 ? round(($attended / $row['total']) * 100, 2) : 0;
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['rollno']) . "</td>";
                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                echo "<td>" . $attended . "</td>";
                echo "<td>" . $row['total'] . "</td>";
                echo "<td>" . $percentage . "%</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table></div>";
            
            echo "<form method='POST' action='download_attendance.php'>";
            echo "<input type='hidden' name='batch' value='" . htmlspecialchars($batch) . "'>";
            echo "<input type='hidden' name='branch' value='" . htmlspecialchars($branch) . "'>";
            echo "<input type='hidden' name='semester' value='" . htmlspecialchars($semester) . "'>";
            if ($section) echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";
            echo "<input type='hidden' name='view_type' value='total'>";
            echo "<button type='submit' class='btn btn-excel'>Download as Excel</button>";
            echo "</form>";
        } else {
            echo "<div class='alert alert-error'>No attendance records found.</div>";
        }
    }
    ?>
</section>
            <!-- Subject-wise Attendance Form -->
            <section id="subject-attendance" class="attendance-form <?= (isset($_GET['view_type']) && $_GET['view_type'] == 'subject' ? 'active' : '') ?>">
                <h3 style="text-align:center">Subject-Wise Attendance of Semester</h3>
                <form method="GET" action="">
                    <input type="hidden" name="action" value="view_attendance">
                    <input type="hidden" name="view_type" value="subject">
                    <div class="form-group">
                        <label for="batch-subject">Batch</label>
                        <select id="batch-subject" name="batch" required class="form-control">
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>" <?= (isset($_GET['batch']) && isset($_GET['view_type']) && $_GET['view_type'] == 'subject' && $_GET['batch'] == $b ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($b) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="branch-subject">Branch</label>
                        <select id="branch-subject" name="branch" required class="form-control">
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= htmlspecialchars($br) ?>" <?= (isset($_GET['branch']) && isset($_GET['view_type']) && $_GET['view_type'] == 'subject' && $_GET['branch'] == $br ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($br) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                   
                    <div class="form-group">
                        <label for="section-subject">Section (Optional)</label>
                        <select id="section-subject" name="section" class="form-control">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= htmlspecialchars($sec) ?>" <?= (isset($_GET['section']) && isset($_GET['view_type']) && $_GET['view_type'] == 'subject' && $_GET['section'] == $sec ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($sec) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="semester-subject">Semester</label>
                        <select id="semester-subject" name="semester" required class="form-control">
                            <option value="">Select Semester</option>
                            <?php foreach ($semester_options as $sem): ?>
                                <option value="<?= htmlspecialchars($sem) ?>" <?= (isset($_GET['semester']) && isset($_GET['view_type']) && $_GET['view_type'] == 'subject' && $_GET['semester'] == $sem ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($sem) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">View Attendance</button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['view_type']) && $_GET['view_type'] == 'subject' && isset($_GET['batch'])) {
                    $batch = $_GET['batch'];
                    $branch = $_GET['branch'];
                    $semester = $_GET['semester'];
                    $section = isset($_GET['section']) && !empty($_GET['section']) ? $_GET['section'] : null;
                    
                    $sectionCondition = $section ? "AND section = ?" : "";
                    $params = $section ? [$batch, $branch, $semester, $section] : [$batch, $branch, $semester];
                    $paramTypes = $section ? "ssss" : "sss";
                    
                    // Get subjects with faculty
                    $sqlSubjects = "SELECT DISTINCT subject, faculty FROM attendance 
                                   WHERE batch = ? AND branch = ? AND semester = ? $sectionCondition
                                   ORDER BY subject";
                    $stmtSubjects = $conn->prepare($sqlSubjects);
                    $stmtSubjects->bind_param($paramTypes, ...$params);
                    $stmtSubjects->execute();
                    $resultSubjects = $stmtSubjects->get_result();
                    
                    // Get students
                    $studentCondition = $section ? "AND section = ?" : "";
                    $studentParams = $section ? [$batch, $branch, $section] : [$batch, $branch];
                    $studentParamTypes = $section ? "sss" : "ss";
                    
                    $sqlStudents = "SELECT rollno, name FROM students 
                                   WHERE batch = ? AND branch = ? $studentCondition
                                   ORDER BY rollno";
                    $stmtStudents = $conn->prepare($sqlStudents);
                    $stmtStudents->bind_param($studentParamTypes, ...$studentParams);
                    $stmtStudents->execute();
                    $resultStudents = $stmtStudents->get_result();
                    
                    if ($resultStudents->num_rows > 0 && $resultSubjects->num_rows > 0) {
                        $sectionText = $section ? ", Section: $section" : "";
                        echo "<h3>Subject-wise Attendance for Batch: $batch, Branch: $branch$sectionText, Semester: $semester</h3>";
                        
                        $subjectList = [];
                        while ($row = $resultSubjects->fetch_assoc()) {
                            $subjectList[] = [
                                'name' => $row['subject'],
                                'faculty' => $row['faculty']
                            ];
                        }
                        
                        // Get total classes per subject
                        $subjectTotals = [];
                        foreach ($subjectList as $subject) {
                            $sqlTotal = "SELECT COUNT(DISTINCT date) as total FROM attendance 
                                        WHERE batch = ? AND branch = ? AND semester = ? AND subject = ? $sectionCondition";
                            $stmtTotal = $conn->prepare($sqlTotal);
                            $totalParams = $section ? 
                                [$batch, $branch, $semester, $subject['name'], $section] : 
                                [$batch, $branch, $semester, $subject['name']];
                            $totalParamTypes = $section ? "sssss" : "ssss";
                            $stmtTotal->bind_param($totalParamTypes, ...$totalParams);
                            $stmtTotal->execute();
                            $resultTotal = $stmtTotal->get_result();
                            $subjectTotals[$subject['name']] = $resultTotal->fetch_assoc()['total'];
                        }
                        
                        echo "<div class='table-responsive'>";
                        echo "<table>";
                        echo "<thead><tr><th>Roll No</th><th>Name</th>";
                        
                        foreach ($subjectList as $subject) {
                            echo "<th>" . htmlspecialchars($subject['name']) . 
                                 "<br><small>Total: " . $subjectTotals[$subject['name']] . 
                                 "<br>Faculty: " . htmlspecialchars($subject['faculty']) . "</small></th>";
                        }
                        
                        echo "</tr></thead><tbody>";
                        
                        while ($student = $resultStudents->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($student['rollno']) . "</td>";
                            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                            
                            foreach ($subjectList as $subject) {
                                $sqlAttended = "SELECT COUNT(*) as attended FROM attendance 
                                              WHERE rollno = ? AND batch = ? AND branch = ? 
                                              AND subject = ? AND semester = ? $sectionCondition AND status = 'Present'";
                                $stmtAttended = $conn->prepare($sqlAttended);
                                $attendedParams = $section ? 
                                    [$student['rollno'], $batch, $branch, $subject['name'], $semester, $section] : 
                                    [$student['rollno'], $batch, $branch, $subject['name'], $semester];
                                $attendedParamTypes = $section ? "ssssss" : "sssss";
                                $stmtAttended->bind_param($attendedParamTypes, ...$attendedParams);
                                $stmtAttended->execute();
                                $resultAttended = $stmtAttended->get_result();
                                $attended = $resultAttended->fetch_assoc()['attended'];
                                
                                $percentage = $subjectTotals[$subject['name']] > 0 ? round(($attended / $subjectTotals[$subject['name']]) * 100, 2) : 0;
                                
                                echo "<td>";
                                echo "<div>Attended: $attended</div>";
                                echo "<div>Percentage: $percentage%</div>";
                                echo "</td>";
                            }
                            
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table></div>";
                        
                        echo "<form method='POST' action='download_attendance.php'>";
                        echo "<input type='hidden' name='batch' value='" . htmlspecialchars($batch) . "'>";
                        echo "<input type='hidden' name='branch' value='" . htmlspecialchars($branch) . "'>";
                        echo "<input type='hidden' name='semester' value='" . htmlspecialchars($semester) . "'>";
                        if ($section) echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";
                        echo "<input type='hidden' name='view_type' value='subject_all'>";
                        echo "<button type='submit' class='btn btn-excel'>Download as Excel</button>";
                        echo "</form>";
                    } else {
                        echo "<div class='alert alert-error'>No attendance records found.</div>";
                    }
                }
                ?>
            </section>
            
            <!-- Date-wise Attendance Form -->
            <section id="date-attendance" class="attendance-form <?= (isset($_GET['view_type']) && $_GET['view_type'] == 'date' ? 'active' : '') ?>">
                <h3 style="text-align:center">Attendance on Date</h3>
                <form method="GET" action="">
                    <input type="hidden" name="action" value="view_attendance">
                    <input type="hidden" name="view_type" value="date">
                    <div class="form-group">
                        <label for="batch-date">Batch</label>
                        <select id="batch-date" name="batch" required class="form-control">
                            <option value="">Select Batch</option>
                            <?php foreach ($batches as $b): ?>
                                <option value="<?= htmlspecialchars($b) ?>" <?= (isset($_GET['batch']) && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && $_GET['batch'] == $b ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($b) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="branch-date">Branch</label>
                        <select id="branch-date" name="branch" required class="form-control">
                            <option value="">Select Branch</option>
                            <?php foreach ($branches as $br): ?>
                                <option value="<?= htmlspecialchars($br) ?>" <?= (isset($_GET['branch']) && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && $_GET['branch'] == $br ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($br) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="section-date">Section (Optional)</label>
                        <select id="section-date" name="section" class="form-control">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?= htmlspecialchars($sec) ?>" <?= (isset($_GET['section']) && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && $_GET['section'] == $sec ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($sec) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" required class="form-control" 
                               value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="btn">View Attendance</button>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && isset($_GET['batch'])) {
                    $batch = $_GET['batch'];
                    $branch = $_GET['branch'];
                    $date = $_GET['date'];
                    $section = isset($_GET['section']) && !empty($_GET['section']) ? $_GET['section'] : null;
                    
                    $sectionCondition = $section ? "AND section = ?" : "";
                    $params = $section ? [$batch, $branch, $date, $section] : [$batch, $branch, $date];
                    $paramTypes = $section ? "ssss" : "sss";
                    
                    // Get subjects with faculty for the date
                    $sqlSubjects = "SELECT DISTINCT subject, faculty FROM attendance 
                                   WHERE batch = ? AND branch = ? AND date = ? $sectionCondition
                                   ORDER BY subject";
                    $stmtSubjects = $conn->prepare($sqlSubjects);
                    $stmtSubjects->bind_param($paramTypes, ...$params);
                    $stmtSubjects->execute();
                    $resultSubjects = $stmtSubjects->get_result();
                    
                    // Get students
                    $studentCondition = $section ? "AND section = ?" : "";
                    $studentParams = $section ? [$batch, $branch, $section] : [$batch, $branch];
                    $studentParamTypes = $section ? "sss" : "ss";
                    
                    $sqlStudents = "SELECT rollno, name FROM students 
                                   WHERE batch = ? AND branch = ? $studentCondition
                                   ORDER BY rollno";
                    $stmtStudents = $conn->prepare($sqlStudents);
                    $stmtStudents->bind_param($studentParamTypes, ...$studentParams);
                    $stmtStudents->execute();
                    $resultStudents = $stmtStudents->get_result();
                    
                    if ($resultStudents->num_rows > 0 && $resultSubjects->num_rows > 0) {
                        $sectionText = $section ? ", Section: $section" : "";
                        echo "<h3>Attendance for Batch: $batch, Branch: $branch$sectionText, Date: " . date('d-m-Y', strtotime($date)) . "</h3>";
                        
                        $subjectList = [];
                        while ($row = $resultSubjects->fetch_assoc()) {
                            $subjectList[] = [
                                'name' => $row['subject'],
                                'faculty' => $row['faculty']
                            ];
                        }
                        
                        echo "<div class='table-responsive'>";
                        echo "<table>";
                        echo "<thead><tr><th>Roll No</th><th>Name</th>";
                        
                        foreach ($subjectList as $subject) {
                            echo "<th>" . htmlspecialchars($subject['name']) . 
                                 "<br><small>Faculty: " . htmlspecialchars($subject['faculty']) . "</small></th>";
                        }
                        
                        echo "</tr></thead><tbody>";
                        
                        while ($student = $resultStudents->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($student['rollno']) . "</td>";
                            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                            
                            foreach ($subjectList as $subject) {
                                $sqlStatus = "SELECT status FROM attendance 
                                             WHERE rollno = ? AND batch = ? AND branch = ? 
                                             AND subject = ? AND date = ? $sectionCondition";
                                $stmtStatus = $conn->prepare($sqlStatus);
                                $statusParams = $section ? 
                                    [$student['rollno'], $batch, $branch, $subject['name'], $date, $section] : 
                                    [$student['rollno'], $batch, $branch, $subject['name'], $date];
                                $statusParamTypes = $section ? "ssssss" : "sssss";
                                $stmtStatus->bind_param($statusParamTypes, ...$statusParams);
                                $stmtStatus->execute();
                                $resultStatus = $stmtStatus->get_result();
                                
                                if ($resultStatus->num_rows > 0) {
                                    $status = $resultStatus->fetch_assoc()['status'];
                                    $class = strtolower($status) == 'present' ? 'present' : 'absent';
                                    echo "<td class='$class'>" . htmlspecialchars($status) . "</td>";
                                } else {
                                    echo "<td class='absent'>Absent</td>";
                                }
                            }
                            
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table></div>";
                        
                        echo "<form method='POST' action='download_attendance.php'>";
                        echo "<input type='hidden' name='batch' value='" . htmlspecialchars($batch) . "'>";
                        echo "<input type='hidden' name='branch' value='" . htmlspecialchars($branch) . "'>";
                        echo "<input type='hidden' name='date' value='" . htmlspecialchars($date) . "'>";
                        if ($section) echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";
                        echo "<input type='hidden' name='view_type' value='date'>";
                        echo "<button type='submit' class='btn btn-excel'>Download as Excel</button>";
                        echo "</form>";
                    } else {
                        echo "<div class='alert alert-error'>No attendance records found for this date.</div>";
                    }
                }
                ?>
            </section>
            
            <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>   
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.attendance-option').click(function() {
                $('.attendance-option').removeClass('active');
                $('.attendance-form').removeClass('active');
                $(this).addClass('active');
                const target = $(this).data('target');
                $('#' + target).addClass('active');
                $('input[name="view_type"]').val(target.replace('-attendance', ''));
            });
        });
    </script>
</body>
</html>