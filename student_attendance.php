<?php
session_start();
include 'db_connection.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fixed semester options
$semesters = ['1-1', '1-2', '2-1', '2-2', '3-1', '3-2', '4-1', '4-2'];
$subjects = [];
$branch = '';
$sections = [];
$current_section = '';

if (isset($_GET['type'])) {
    if (($_GET['type'] == 'subject' || $_GET['type'] == 'total' || $_GET['type'] == 'date') && isset($_GET['rollno'])) {
        $rollno = $_GET['rollno'];
        $sql = "SELECT branch, section FROM students WHERE rollno = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $rollno);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $branch = $student['branch'];
            $current_section = $student['section'];
            
            // Get all sections for this branch
            $sectionQuery = "SELECT DISTINCT section FROM students WHERE branch = ? AND section IS NOT NULL ORDER BY section";
            $sectionStmt = $conn->prepare($sectionQuery);
            $sectionStmt->bind_param("s", $branch);
            $sectionStmt->execute();
            $sectionResult = $sectionStmt->get_result();
            
            while ($row = $sectionResult->fetch_assoc()) {
                $sections[] = $row['section'];
            }
            
            if (isset($_GET['semester'])) {
                $semester = $_GET['semester'];
                $subjectQuery = "SELECT DISTINCT subject_name FROM subjects WHERE branch = ? AND semester = ? ORDER BY subject_name";
                $subjectStmt = $conn->prepare($subjectQuery);
                $subjectStmt->bind_param("ss", $branch, $semester);
                $subjectStmt->execute();
                $subjectResult = $subjectStmt->get_result();
                
                while ($row = $subjectResult->fetch_assoc()) {
                    $subjects[] = $row['subject_name'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Attendance - DRKIST</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="icon" type="image/x-icon" href="assets/images/drk.png">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #8e0000;
            --secondary-color: #6c757d;
            --accent-color: #007bff;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
         
        header {
            background-color: white;
            padding: 15px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
        }

        .logo-title-container {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }

      .logo-title-container img {
            height: 50px;
            width: 30%;
            position: absolute;
            left: 0px;
        }

        .logo-title-container h1 {
            margin: 0;
            font-size: 1.8rem;
            color: maroon;
            text-align: center;
            width: 100%;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .home-btn {
            display: inline-block;
            background-color: #343a40;
            color: white;
            padding: 5px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin: 10px 0;
            text-align: center;
            transition: background-color 0.3s;
            margin-left: auto;
            margin-right: 50px;
            position: relative;
            z-index: 1;
        }
        
        .home-btn:hover {
            background-color: rgb(27, 86, 144);
        }
        
        .attendance-options {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .attendance-option {
            padding: 10px 20px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            cursor: pointer;
            min-width: 150px;
            text-align: center;
        }
        
        .attendance-option.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .attendance-form {
            display: none;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .attendance-form.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
            width:65%;
              margin: 0 auto; /* Center the input box while keeping text left-aligned */

        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
       margin: 0 auto; /* Center the input box while keeping text left-aligned*/
                display: flex;
    justify-content: center;
    margin-top: 20px;
    width: 50%;

        }
        
        .btn:hover {
            background-color: #6e0000;
        }
        
        .attendance-result {
            margin-top: 30px;
        }
        
        .student-info {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .student-info h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        .form-control{
            width:60%
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .error {
            color: #dc3545;
            text-align: center;
            margin-top: 15px;
        }
        
        footer {
            background-color: black;
            padding: 20px;
            width: 100%;
        }
        
        .contain {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .footer-develop {
            font-size: 0.9rem;
            color: #aaa;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .present {
            color: green;
            font-weight: bold;
        }
        
        .absent {
            color: red;
            font-weight: bold;
        }
        /* Responsive adjustments */
        @media (max-width: 767px) {
            .logo-title-container {
                flex-direction: column;
                padding-top: 10px;
            }
            
            .logo-title-container img {
                position: static;
                margin-bottom: 10px;
                width:auto;
            }
            
            .logo-title-container h1 {
                position: static;
                transform: none;
                order: 2;
            }
            
            .home-btn {
                order: 3;
                margin: 10px auto 0;
            }
        }
    @media (max-width: 480px) {        
            .logo-title-container img {
                height: 40px;
                     width: auto;
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
<header>
    <div class="header-container">
        <div class="logo-title-container">
            <img src="assets/images/D.png" alt="DRKIST Logo">
            <h1>ATTENDANCE PORTAL</h1>
            <a href="index.php" class="home-btn">Home</a>
        </div>
    </div>
</header>

<main class="container">
    <div class="attendance-options">
        <div class="attendance-option <?= (!isset($_GET['type']) || $_GET['type'] == 'total' ? 'active' : '') ?>" 
             data-target="total-attendance">Total Attendance</div>
        <div class="attendance-option <?= (isset($_GET['type']) && $_GET['type'] == 'subject' ? 'active' : '' )?>" 
             data-target="subject-attendance">Subject Attendance</div>
        <div class="attendance-option <?= (isset($_GET['type']) && $_GET['type'] == 'date' ? 'active' : '' )?>" 
             data-target="date-attendance">Attendance on Date</div>
    </div>

    <!-- Total Attendance Form -->
    <section id="total-attendance" class="attendance-form <?= (!isset($_GET['type']) || $_GET['type'] == 'total' ? 'active' : '') ?>">
        <h2 class="text-center">Total Attendance of Semester</h2>
        <form method="GET" action="">
            <input type="hidden" name="type" value="total">
            <div class="form-group">
                <label for="rollno">Roll Number</label>
                <input type="text" id="rollno" name="rollno" value="<?= isset($_GET['rollno']) ? htmlspecialchars($_GET['rollno']) : '' ?>" required>
            </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" class="form-control" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= htmlspecialchars($sem) ?>" <?= (isset($_GET['semester']) && $_GET['semester'] == $sem ? 'selected' : '' )?>>
                                <?= htmlspecialchars($sem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <button type="submit" class="btn">Check Attendance</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['type']) && $_GET['type'] == 'total' && isset($_GET['rollno']) && isset($_GET['semester'])) {
            $rollno = $_GET['rollno'];
            $semester = $_GET['semester'];
            $section = $_GET['section'] ?? null;

            $sql = "SELECT name, batch, branch, section FROM students WHERE rollno = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $rollno);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                // Build the base query
                $baseQuery = "FROM attendance WHERE rollno = ? AND semester = ?";
                $params = [$rollno, $semester];
                $types = "ss";
                
                // Add section filter if specified
                if (!empty($section)) {
                    $baseQuery .= " AND section = ?";
                    $params[] = $section;
                    $types .= "s";
                }

                // Get total classes
                $totalSql = "SELECT COUNT(*) as total $baseQuery";
                $stmt = $conn->prepare($totalSql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                $total = $result->fetch_assoc()['total'];

                // Get attended classes
                $attendedSql = "SELECT COUNT(*) as attended $baseQuery AND status = 'Present'";
                $stmt = $conn->prepare($attendedSql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                $attended = $result->fetch_assoc()['attended'];

                $percentage = ($total > 0) ? round(($attended / $total) * 100, 2) : 0;

                echo "<div class='attendance-result'>";
                echo "<div class='student-info'>";
                echo "<h3>Student Information</h3>";
                echo "<table>";
                echo "<tr><th>Roll No</th><th>Name</th><th>Batch</th><th>Branch</th><th>Section</th><th>Semester</th></tr>";
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rollno) . "</td>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['batch']) . "</td>";
                echo "<td>" . htmlspecialchars($student['branch']) . "</td>";
                echo "<td>" . htmlspecialchars($student['section']) . "</td>";
                echo "<td>" . htmlspecialchars($semester) . "</td>";
                echo "</tr>";
                echo "</table>";
                echo "</div>";

                echo "<h3>Attendance Summary</h3>";
                echo "<table>";
                echo "<tr><th>Total Classes Attended</th><th>Total Classes Held</th><th>Attendance Percentage</th></tr>";
                echo "<tr>";
                echo "<td>" . $attended . "</td>";
                echo "<td>" . $total . "</td>";
                echo "<td>" . $percentage . "%</td>";
                echo "</tr>";
                echo "</table>";
                echo "</div>";
            } else {
                echo "<p class='error'>Student not found.</p>";
            }
        }
        ?>
    </section>

    <!-- Subject Attendance Form -->
    <section id="subject-attendance" class="attendance-form <?= (isset($_GET['type']) && $_GET['type'] == 'subject' ? 'active' : '') ?>">
        <h2 class="text-center">Subject-wise Attendance of Semester</h2>
        <form method="GET" action="">
            <input type="hidden" name="type" value="subject">
            <div class="form-group">
                <label for="rollno-subject">Roll Number</label>
                <input type="text" id="rollno-subject" name="rollno" value="<?= isset($_GET['rollno']) ? htmlspecialchars($_GET['rollno']) : '' ?>" required>
            </div>
                <div class="form-group">
                    <label for="semester-subject">Semester</label>
                    <select id="semester-subject" name="semester" class="form-control" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= htmlspecialchars($sem) ?>" <?= (isset($_GET['semester']) && $_GET['semester'] == $sem ? 'selected' : '') ?>>
                                <?= htmlspecialchars($sem) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <button type="submit" class="btn">Check Attendance</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['type']) && $_GET['type'] == 'subject' && isset($_GET['rollno']) && isset($_GET['semester'])) {
            $rollno = $_GET['rollno'];
            $semester = $_GET['semester'];
            $section = $_GET['section'] ?? null;

            $sql = "SELECT name, batch, branch, section FROM students WHERE rollno = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $rollno);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                // Build the base query
                $baseQuery = "FROM attendance WHERE rollno = ? AND semester = ?";
                $params = [$rollno, $semester];
                $types = "ss";
                
                // Add section filter if specified
                if (!empty($section)) {
                    $baseQuery .= " AND section = ?";
                    $params[] = $section;
                    $types .= "s";
                }

                $sql = "SELECT subject, 
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as attended,
                        COUNT(*) as total 
                        $baseQuery
                        GROUP BY subject
                        ORDER BY subject";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                echo "<div class='attendance-result'>";
                echo "<div class='student-info'>";
                echo "<h3>Student Information</h3>";
                echo "<table>";
                echo "<tr><th>Roll No</th><th>Name</th><th>Batch</th><th>Branch</th><th>Section</th><th>Semester</th></tr>";
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rollno) . "</td>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['batch']) . "</td>";
                echo "<td>" . htmlspecialchars($student['branch']) . "</td>";
                echo "<td>" . htmlspecialchars($student['section']) . "</td>";
                echo "<td>" . htmlspecialchars($semester) . "</td>";
                echo "</tr>";
                echo "</table>";
                echo "</div>";
                
                if ($result->num_rows > 0) {
                    echo "<h3>Subject-wise Attendance Details</h3>";
                    echo "<table>";
                    echo "<tr><th>Subject</th><th>Attended</th><th>Total</th><th>Percentage</th></tr>";
                    
                    while ($row = $result->fetch_assoc()) {
                        $percentage = ($row['total'] > 0) ? round(($row['attended'] / $row['total']) * 100, 2) : 0;
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                        echo "<td>" . $row['attended'] . "</td>";
                        echo "<td>" . $row['total'] . "</td>";
                        echo "<td>" . $percentage . "%</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>No attendance records found for this semester.";
                    if (!empty($section)) {
                        echo " (Section: " . htmlspecialchars($section) . ")";
                    }
                    echo "</p>";
                }
                echo "</div>";
            } else {
                echo "<p class='error'>Student not found.</p>";
            }
        }
        ?>
    </section>
    
    <!-- Date Attendance Form -->
    <section id="date-attendance" class="attendance-form <?= (isset($_GET['type']) && $_GET['type'] == 'date' ? 'active' : '') ?>">
        <h2 class="text-center">Attendance on Specific Date</h2>
        <form method="GET" action="">
            <input type="hidden" name="type" value="date">
            <div class="form-group">
                <label for="rollno-date">Roll Number</label>
                <input type="text" id="rollno-date" name="rollno" value="<?= isset($_GET['rollno']) ? htmlspecialchars($_GET['rollno']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="<?= isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '' ?>" required>
            </div>
            <button type="submit" class="btn">Check Attendance</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['type']) && $_GET['type'] == 'date' && isset($_GET['rollno']) && isset($_GET['date'])) {
            $rollno = $_GET['rollno'];
            $date = $_GET['date'];

            // Get student info
            $sql = "SELECT name, batch, branch, section FROM students WHERE rollno = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $rollno);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student) {
                // Get attendance for the specific date
                $sql = "SELECT subject, status, faculty FROM attendance 
                        WHERE rollno = ? AND date = ? 
                        ORDER BY subject";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $rollno, $date);
                $stmt->execute();
                $result = $stmt->get_result();
                
                echo "<div class='attendance-result'>";
                echo "<div class='student-info'>";
                echo "<h3>Student Information</h3>";
                echo "<table>";
                echo "<tr><th>Roll No</th><th>Name</th><th>Batch</th><th>Branch</th><th>Section</th><th>Date</th></tr>";
                echo "<tr>";
                echo "<td>" . htmlspecialchars($rollno) . "</td>";
                echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                echo "<td>" . htmlspecialchars($student['batch']) . "</td>";
                echo "<td>" . htmlspecialchars($student['branch']) . "</td>";
                echo "<td>" . htmlspecialchars($student['section']) . "</td>";
                echo "<td>" . htmlspecialchars($date) . "</td>";
                echo "</tr>";
                echo "</table>";
                echo "</div>";
                
                if ($result->num_rows > 0) {
                    echo "<h3>Attendance Details for " . htmlspecialchars($date) . "</h3>";
                    echo "<table>";
                    echo "<tr><th>Subject</th><th>Status</th><th>Faculty</th></tr>";
                    
                    while ($row = $result->fetch_assoc()) {
                        $statusClass = strtolower($row['status']) == 'present' ? 'present' : 'absent';
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                        echo "<td class='$statusClass'>" . htmlspecialchars($row['status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['faculty']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    // Calculate summary
                    $presentCount = 0;
                    $absentCount = 0;
                    $result->data_seek(0); // Reset pointer
                    while ($row = $result->fetch_assoc()) {
                        if (strtolower($row['status']) == 'present') {
                            $presentCount++;
                        } else {
                            $absentCount++;
                        }
                    }
                    
                    echo "<h4>Summary</h4>";
                    echo "<table>";
                    echo "<tr><th>Total Classes</th><th>Present</th><th>Absent</th></tr>";
                    echo "<tr>";
                    echo "<td>" . ($presentCount + $absentCount) . "</td>";
                    echo "<td class='present'>" . $presentCount . "</td>";
                    echo "<td class='absent'>" . $absentCount . "</td>";
                    echo "</tr>";
                    echo "</table>";
                } else {
                    echo "<p class='error'>No attendance records found for " . htmlspecialchars($date) . "</p>";
                }
                echo "</div>";
            } else {
                echo "<p class='error'>Student not found.</p>";
            }
        }
        ?>
    </section>
</main>

<footer>
    <div class="container">
        <p style="color:white;margin: 0;">© <span id="year"></span> DRK Institute of Science and Technology, All Rights Reserved</p>
        <p class="footer-develop" style="margin: 0;font-size:13px;color:white">Developed by K Sampath Reddy CSE[AI&ML]</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#semester, #semester-subject').select2({
            placeholder: "Select an option",
            allowClear: true,
            minimumResultsForSearch: Infinity
        });

        document.querySelectorAll('.attendance-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.attendance-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                document.querySelectorAll('.attendance-form').forEach(form => {
                    form.classList.remove('active');
                });
                
                this.classList.add('active');
                const target = this.getAttribute('data-target');
                document.getElementById(target).classList.add('active');
            });
        });

        // Set current year in footer
        document.getElementById("year").textContent = new Date().getFullYear();
        
        // Set default date to today for date attendance
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').value = today;
    });
</script>
</body>
</html>