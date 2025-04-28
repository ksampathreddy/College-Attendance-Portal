<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

$message = '';
$username = $_SESSION['username'];

// Get unique batches, branches, and dates from attendance table where the faculty has taught
$batches = $branches = $dates = [];

$batchQuery = "SELECT DISTINCT a.batch FROM attendance a 
               JOIN assign_subjects s ON a.subject = s.subject_name
               WHERE s.faculty_name = ? 
               ORDER BY a.batch DESC";
$stmt = $conn->prepare($batchQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $batches[] = $row['batch'];
}

$branchQuery = "SELECT DISTINCT a.branch FROM attendance a 
                JOIN assign_subjects s ON a.subject = s.subject_name
                WHERE s.faculty_name = ? 
                ORDER BY a.branch";
$stmt = $conn->prepare($branchQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $branches[] = $row['branch'];
}

$dateQuery = "SELECT DISTINCT date FROM attendance 
              WHERE faculty = ? 
              ORDER BY date DESC";
$stmt = $conn->prepare($dateQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $dates[] = $row['date'];
}

// Get all sections from attendance table where the faculty has taught
$sectionQuery = "SELECT DISTINCT section FROM attendance 
                WHERE faculty = ? AND section IS NOT NULL AND section != ''
                ORDER BY section";
$stmt = $conn->prepare($sectionQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = $row['section'];
}

// Semester options array
$semester_options = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];

// Determine which form should be active initially
$active_form = 'subject';
if (isset($_GET['view_type']) && ($_GET['view_type'] == 'subject' || $_GET['view_type'] == 'date')) {
    $active_form = $_GET['view_type'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - DRKIST</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <style>
        .view-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
   .form-group {
            margin-bottom: 20px;
            width: 100%;
              width:70%;
              margin: 0 auto; /* Center the input box while keeping text left-aligned */
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
            margin-top: 20px;
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
           @media (min-width: 350px) {
            .attendance-options {
                flex-direction: row;
              gap:12px;
            }
               .attendance-option {
                min-width: 100px;
                 font-size:0.9rem;
            }
        }
               
    </style>
</head>
<body>
    <main class="container">
        <div class="view-container">
            <h3 class="text-center">Attendance of Students in a Class</h3>
            <div class="attendance-options">
                <div class="attendance-option <?= $active_form == 'subject' ? 'active' : '' ?>" data-target="subject-attendance">Subject Attendance</div>
                <div class="attendance-option <?= $active_form == 'date' ? 'active' : '' ?>" data-target="date-attendance">Date Attendance</div>
            </div>
            
            <?php if (!empty($message)) echo $message; ?>
            
            <!-- Subject Attendance Form -->
            <section id="subject-attendance" class="attendance-form <?= $active_form == 'subject' ? 'active' : '' ?>">
                <h3 style="text-align:center">Subject-Wise Attendance of Semester</h3>
                <form method="GET" action="">
                    <input type="hidden" name="action" value="view_attendance">
                    <input type="hidden" name="view_type" value="subject">
                    
                      <div class="form-group">
                                <label for="batch-subject">Batch</label>
                                <select id="batch-subject" name="batch" class="form-control">
                                    <option value="">Select Batch</option>
                                    <?php foreach ($batches as $b): ?>
                                        <option value="<?php echo htmlspecialchars($b); ?>" 
                                            <?= (isset($_GET['batch']) && $_GET['view_type'] == 'subject' && $_GET['batch'] == $b ? 'selected' : '') ?>>
                                            <?php echo htmlspecialchars($b); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                      
                            <div class="form-group">
                                <label for="branch-subject">Branch</label>
                                <select id="branch-subject" name="branch" class="form-control">
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $br): ?>
                                        <option value="<?php echo htmlspecialchars($br); ?>" 
                                            <?= (isset($_GET['branch']) && $_GET['view_type'] == 'subject' && $_GET['branch'] == $br ? 'selected' : '') ?>>
                                            <?php echo htmlspecialchars($br); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                       
                            <div class="form-group">
                                <label for="section-subject">Section</label>
                                <select id="section-subject" name="section" class="form-control">
                                    <option value="">All Sections</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?php echo htmlspecialchars($sec); ?>" 
                                            <?= (isset($_GET['section']) && $_GET['view_type'] == 'subject' && $_GET['section'] == $sec ? 'selected' : '') ?>>
                                            <?php echo htmlspecialchars($sec); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                       
                            <div class="form-group">
                                <label for="semester-subject">Semester</label>
                                <select id="semester-subject" name="semester" class="form-control">
                                    <option value="">Select Semester</option>
                                    <?php foreach ($semester_options as $sem): ?>
                                        <option value="<?php echo htmlspecialchars($sem); ?>" 
                                            <?= (isset($_GET['semester']) && $_GET['view_type'] == 'subject' && $_GET['semester'] == $sem ? 'selected' : '') ?>>
                                            <?php echo htmlspecialchars($sem); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn">View Attendance</button>
                    </div>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['view_type']) && $_GET['view_type'] == 'subject') {
                    $batch = isset($_GET['batch']) ? $_GET['batch'] : '';
                    $branch = isset($_GET['branch']) ? $_GET['branch'] : '';
                    $semester = isset($_GET['semester']) ? $_GET['semester'] : '';
                    $section = isset($_GET['section']) ? ($_GET['section'] === '' ? NULL : $_GET['section']) : '';
                    
                    // Build WHERE conditions for assigned subjects
                    $whereConditions = "WHERE s.faculty_name = ?";
                    $params = [$username];
                    $paramTypes = "s";
                    
                    if (!empty($batch)) {
                        $whereConditions .= " AND a.batch = ?";
                        $params[] = $batch;
                        $paramTypes .= "s";
                    }
                    if (!empty($branch)) {
                        $whereConditions .= " AND a.branch = ?";
                        $params[] = $branch;
                        $paramTypes .= "s";
                    }
                    if (!empty($semester)) {
                        $whereConditions .= " AND a.semester = ?";
                        $params[] = $semester;
                        $paramTypes .= "s";
                    }
                    if (!empty($section)) {
                        $whereConditions .= " AND a.section = ?";
                        $params[] = $section;
                        $paramTypes .= "s";
                    }
                    
                    // Get all subjects assigned to this faculty with the selected filters
                    $sqlSubjects = "SELECT DISTINCT a.subject 
                                   FROM attendance a
                                   JOIN assign_subjects s ON a.subject = s.subject_name
                                   $whereConditions
                                   ORDER BY a.subject";
                    $stmtSubjects = $conn->prepare($sqlSubjects);
                    $stmtSubjects->bind_param($paramTypes, ...$params);
                    $stmtSubjects->execute();
                    $resultSubjects = $stmtSubjects->get_result();
                    
                    // Get all students with the selected filters
                    $sqlStudents = "SELECT rollno, name FROM students 
                                   WHERE 1=1";
                    $studentParams = [];
                    $studentTypes = "";
                    
                    if (!empty($batch)) {
                        $sqlStudents .= " AND batch = ?";
                        $studentParams[] = $batch;
                        $studentTypes .= "s";
                    }
                    if (!empty($branch)) {
                        $sqlStudents .= " AND branch = ?";
                        $studentParams[] = $branch;
                        $studentTypes .= "s";
                    }
                    if (!empty($section)) {
                        $sqlStudents .= " AND section = ?";
                        $studentParams[] = $section;
                        $studentTypes .= "s";
                    }
                    
                    $sqlStudents .= " ORDER BY rollno";
                    $stmtStudents = $conn->prepare($sqlStudents);
                    
                    if (!empty($studentParams)) {
                        $stmtStudents->bind_param($studentTypes, ...$studentParams);
                    }
                    
                    $stmtStudents->execute();
                    $resultStudents = $stmtStudents->get_result();
                    
                    if ($resultStudents->num_rows > 0 && $resultSubjects->num_rows > 0) {
                        echo "<h3>Subject-wise Attendance Summary";
                        if (!empty($batch)) echo ", Batch: $batch";
                        if (!empty($branch)) echo ", Branch: $branch";
                        if (!empty($semester)) echo ", Semester: $semester";
                        if (!empty($section)) echo ", Section: $section";
                        echo "</h3>";
                        
                        // Prepare subject list for query
                        $subjectList = [];
                        while ($subjectRow = $resultSubjects->fetch_assoc()) {
                            $subjectList[] = $subjectRow['subject'];
                        }
                        
                        // Get total classes for each subject
                        $subjectTotals = [];
                        foreach ($subjectList as $subject) {
                            $sqlTotal = "SELECT COUNT(DISTINCT date) as total FROM attendance 
                                        WHERE faculty = ? AND subject = ?";
                            $paramsTotal = [$username, $subject];
                            $typesTotal = "ss";
                            
                            if (!empty($batch)) {
                                $sqlTotal .= " AND batch = ?";
                                $paramsTotal[] = $batch;
                                $typesTotal .= "s";
                            }
                            if (!empty($branch)) {
                                $sqlTotal .= " AND branch = ?";
                                $paramsTotal[] = $branch;
                                $typesTotal .= "s";
                            }
                            if (!empty($semester)) {
                                $sqlTotal .= " AND semester = ?";
                                $paramsTotal[] = $semester;
                                $typesTotal .= "s";
                            }
                            if (!empty($section)) {
                                $sqlTotal .= " AND section = ?";
                                $paramsTotal[] = $section;
                                $typesTotal .= "s";
                            }
                            
                            $stmtTotal = $conn->prepare($sqlTotal);
                            $stmtTotal->bind_param($typesTotal, ...$paramsTotal);
                            $stmtTotal->execute();
                            $resultTotal = $stmtTotal->get_result();
                            $subjectTotals[$subject] = $resultTotal->fetch_assoc()['total'];
                        }
                        
                        echo "<div class='table-responsive'>";
                        echo "<table>";
                        echo "<thead><tr><th>Roll No</th><th>Name</th>";
                        
                        // Add subject columns to header with total classes
                        foreach ($subjectList as $subject) {
                            echo "<th>" . htmlspecialchars($subject) . "<br><small>Total: " . $subjectTotals[$subject] . "</small></th>";
                        }
                        
                        echo "</tr></thead><tbody>";
                        
                        // Process each student
                        while ($student = $resultStudents->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($student['rollno']) . "</td>";
                            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                            
                            // Get attendance status for each subject
                            foreach ($subjectList as $subject) {
                                $sqlAttended = "SELECT COUNT(*) as attended FROM attendance 
                                              WHERE faculty = ? AND rollno = ? AND subject = ?";
                                $paramsAttended = [$username, $student['rollno'], $subject];
                                $typesAttended = "sss";
                                
                                if (!empty($batch)) {
                                    $sqlAttended .= " AND batch = ?";
                                    $paramsAttended[] = $batch;
                                    $typesAttended .= "s";
                                }
                                if (!empty($branch)) {
                                    $sqlAttended .= " AND branch = ?";
                                    $paramsAttended[] = $branch;
                                    $typesAttended .= "s";
                                }
                                if (!empty($semester)) {
                                    $sqlAttended .= " AND semester = ?";
                                    $paramsAttended[] = $semester;
                                    $typesAttended .= "s";
                                }
                                if (!empty($section)) {
                                    $sqlAttended .= " AND section = ?";
                                    $paramsAttended[] = $section;
                                    $typesAttended .= "s";
                                }
                                
                                $sqlAttended .= " AND status = 'Present'";
                                
                                $stmtAttended = $conn->prepare($sqlAttended);
                                $stmtAttended->bind_param($typesAttended, ...$paramsAttended);
                                $stmtAttended->execute();
                                $resultAttended = $stmtAttended->get_result();
                                $attended = $resultAttended->fetch_assoc()['attended'];
                                
                                $percentage = $subjectTotals[$subject] > 0 ? round(($attended / $subjectTotals[$subject]) * 100, 2) : 0;
                                
                                echo "<td>";
                                echo "<div>Attended: $attended</div>";
                                echo "<div>Percentage: $percentage%</div>";
                                echo "</td>";
                            }
                            
                            echo "</tr>";
                        }
                        
                        echo "</tbody></table></div>";
                        
                        // Download Excel button
                        echo "<form method='POST' action='download_attendance.php'>";
                        echo "<input type='hidden' name='batch' value='" . htmlspecialchars($batch) . "'>";
                        echo "<input type='hidden' name='branch' value='" . htmlspecialchars($branch) . "'>";
                        echo "<input type='hidden' name='semester' value='" . htmlspecialchars($semester) . "'>";
                        if (!empty($section)) echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";
                        echo "<input type='hidden' name='view_type' value='subject_all'>";
                        echo "<input type='hidden' name='faculty' value='" . htmlspecialchars($username) . "'>";
                        echo "<button type='submit' class='btn btn-excel'>Download as Excel</button>";
                        echo "</form>";
                    } else {
                        echo "<div class='alert alert-error'>No attendance records found for the selected criteria.</div>";
                    }
                }
                ?>
            </section>
            
            <!-- Date Attendance Form -->
            <section id="date-attendance" class="attendance-form <?= $active_form == 'date' ? 'active' : '' ?>">
                <h3 style="text-align:center">Attendance on Date</h3>
                <form method="GET" action="">
                    <input type="hidden" name="action" value="view_attendance">
                    <input type="hidden" name="view_type" value="date">
                    
                            <div class="form-group">
                                <label for="batch-date">Batch</label>
                                <select id="batch-date" name="batch" class="form-control">
                                    <option value="">Select Batch</option>
                                    <?php foreach ($batches as $b): ?>
                                        <option value="<?php echo htmlspecialchars($b); ?>" 
                                            <?= (isset($_GET['batch']) && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && $_GET['batch'] == $b ? 'selected' : '' )?>>
                                            <?php echo htmlspecialchars($b); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                    
                            <div class="form-group">
                                <label for="branch-date">Branch</label>
                                <select id="branch-date" name="branch" class="form-control">
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $br): ?>
                                        <option value="<?php echo htmlspecialchars($br); ?>" 
                                            <?= (isset($_GET['branch']) && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && $_GET['branch'] == $br ? 'selected' : '') ?>>
                                            <?php echo htmlspecialchars($br); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                    
                            <div class="form-group">
                                <label for="section-date">Section</label>
                                <select id="section-date" name="section" class="form-control">
                                    <option value="">All Sections</option>
                                    <?php foreach ($sections as $sec): ?>
                                        <option value="<?php echo htmlspecialchars($sec); ?>" 
                                            <?= (isset($_GET['section']) && isset($_GET['view_type']) && $_GET['view_type'] == 'date' && $_GET['section'] == $sec ? 'selected' : '') ?>>
                                            <?php echo htmlspecialchars($sec); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" id="date" name="date" class="form-control" 
                                       value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d') ?>"
                                       max="<?= date('Y-m-d') ?>">
                            </div>
                    
                    <div class="form-group text-center">
                        <button type="submit" class="btn">View Attendance</button>
                    </div>
                </form>

                <?php
                if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['view_type']) && $_GET['view_type'] == 'date') {
                    $batch = isset($_GET['batch']) ? $_GET['batch'] : '';
                    $branch = isset($_GET['branch']) ? $_GET['branch'] : '';
                    $date = isset($_GET['date']) ? $_GET['date'] : '';
                    $section = isset($_GET['section']) ? ($_GET['section'] === '' ? NULL : $_GET['section']) : '';
                    
                    // Build WHERE conditions
                    $whereConditions = "WHERE a.faculty = ?";
                    $params = [$username];
                    $paramTypes = "s";
                    
                    if (!empty($batch)) {
                        $whereConditions .= " AND a.batch = ?";
                        $params[] = $batch;
                        $paramTypes .= "s";
                    }
                    if (!empty($branch)) {
                        $whereConditions .= " AND a.branch = ?";
                        $params[] = $branch;
                        $paramTypes .= "s";
                    }
                    if (!empty($date)) {
                        $whereConditions .= " AND a.date = ?";
                        $params[] = $date;
                        $paramTypes .= "s";
                    }
                    if (!empty($section)) {
                        $whereConditions .= " AND a.section = ?";
                        $params[] = $section;
                        $paramTypes .= "s";
                    }
                    
                    // Get all subjects that had classes with the selected filters
                    $sqlSubjects = "SELECT DISTINCT a.subject FROM attendance a 
                                   $whereConditions
                                   ORDER BY a.subject";
                    $stmtSubjects = $conn->prepare($sqlSubjects);
                    $stmtSubjects->bind_param($paramTypes, ...$params);
                    $stmtSubjects->execute();
                    $resultSubjects = $stmtSubjects->get_result();
                    
                    // Get all students with the selected filters
                    $sqlStudents = "SELECT rollno, name FROM students 
                                   WHERE 1=1";
                    $studentParams = [];
                    $studentTypes = "";
                    
                    if (!empty($batch)) {
                        $sqlStudents .= " AND batch = ?";
                        $studentParams[] = $batch;
                        $studentTypes .= "s";
                    }
                    if (!empty($branch)) {
                        $sqlStudents .= " AND branch = ?";
                        $studentParams[] = $branch;
                        $studentTypes .= "s";
                    }
                    if (!empty($section)) {
                        $sqlStudents .= " AND section = ?";
                        $studentParams[] = $section;
                        $studentTypes .= "s";
                    }
                    
                    $sqlStudents .= " ORDER BY rollno";
                    $stmtStudents = $conn->prepare($sqlStudents);
                    
                    if (!empty($studentParams)) {
                        $stmtStudents->bind_param($studentTypes, ...$studentParams);
                    }
                    
                    $stmtStudents->execute();
                    $resultStudents = $stmtStudents->get_result();
                    
                    if ($resultStudents->num_rows > 0 && $resultSubjects->num_rows > 0) {
                        echo "<h3>Date Attendance Summary";
                        if (!empty($batch)) echo ", Batch: $batch";
                        if (!empty($branch)) echo ", Branch: $branch";
                        if (!empty($date)) echo ", Date: " . date('d-m-Y', strtotime($date));
                        if (!empty($section)) echo ", Section: $section";
                        echo "</h3>";
                        
                        // Prepare subject list for query
                        $subjectList = [];
                        while ($subjectRow = $resultSubjects->fetch_assoc()) {
                            $subjectList[] = $subjectRow['subject'];
                        }
                        
                        echo "<div class='table-responsive'>";
                        echo "<table>";
                        echo "<thead><tr><th>Roll No</th><th>Name</th>";
                        
                        // Add subject columns to header
                        foreach ($subjectList as $subject) {
                            echo "<th>" . htmlspecialchars($subject) . "</th>";
                        }
                        
                        echo "</tr></thead><tbody>";
                        
                        // Process each student
                        while ($student = $resultStudents->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($student['rollno']) . "</td>";
                            echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                            
                            // Get attendance status for each subject
                            foreach ($subjectList as $subject) {
                                $sqlStatus = "SELECT a.status FROM attendance a 
                                             WHERE a.faculty = ? AND a.rollno = ? AND a.subject = ?";
                                $paramsStatus = [$username, $student['rollno'], $subject];
                                $typesStatus = "sss";
                                
                                if (!empty($batch)) {
                                    $sqlStatus .= " AND a.batch = ?";
                                    $paramsStatus[] = $batch;
                                    $typesStatus .= "s";
                                }
                                if (!empty($branch)) {
                                    $sqlStatus .= " AND a.branch = ?";
                                    $paramsStatus[] = $branch;
                                    $typesStatus .= "s";
                                }
                                if (!empty($date)) {
                                    $sqlStatus .= " AND a.date = ?";
                                    $paramsStatus[] = $date;
                                    $typesStatus .= "s";
                                }
                                if (!empty($section)) {
                                    $sqlStatus .= " AND a.section = ?";
                                    $paramsStatus[] = $section;
                                    $typesStatus .= "s";
                                }
                                
                                $stmtStatus = $conn->prepare($sqlStatus);
                                $stmtStatus->bind_param($typesStatus, ...$paramsStatus);
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
                        
                        // Download Excel button
                        echo "<form method='POST' action='download_attendance.php'>";
                        echo "<input type='hidden' name='batch' value='" . htmlspecialchars($batch) . "'>";
                        echo "<input type='hidden' name='branch' value='" . htmlspecialchars($branch) . "'>";
                        echo "<input type='hidden' name='date' value='" . htmlspecialchars($date) . "'>";
                        if (!empty($section)) echo "<input type='hidden' name='section' value='" . htmlspecialchars($section) . "'>";
                        echo "<input type='hidden' name='view_type' value='date'>";
                        echo "<input type='hidden' name='faculty' value='" . htmlspecialchars($username) . "'>";
                        echo "<button type='submit' class='btn btn-excel'>Download as Excel</button>";
                        echo "</form>";
                    } else {
                        echo "<div class='alert alert-error'>No attendance records found for the selected criteria.</div>";
                    }
                }
                ?>
            </section>
            
            <a href="teacher_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>   
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Toggle between attendance options
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