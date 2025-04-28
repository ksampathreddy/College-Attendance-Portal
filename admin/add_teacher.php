<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

$message = "";
$assignment_message = "";
$faculty = [];
$subjects = [];
$assigned_subjects = [];
$current_faculty = '';

// Handle manual form submission for adding faculty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if username already exists
    $check_sql = "SELECT username FROM teachers WHERE username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $message = "<p class='error'>Username already exists!</p>";
    } else {
        $sql = "INSERT INTO teachers (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        
        if ($stmt->execute()) {
            $message = "<p class='success'>Faculty added successfully!</p>";
        } else {
            $message = "<p class='error'>Error adding Faculty: " . $conn->error . "</p>";
        }
    }
}

// Handle Excel file upload for adding faculty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    require '../vendor/autoload.php';
    
    $file = $_FILES['excel_file']['tmp_name'];
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        
        $successCount = 0;
        $errorCount = 0;
        $errorMessages = [];
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $username = $sheet->getCell('A' . $row)->getValue();
            $password = $sheet->getCell('B' . $row)->getValue();
            
            if (!empty($username) && !empty($password)) {
                $check_sql = "SELECT username FROM teachers WHERE username = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $username);
                $check_stmt->execute();
                $check_stmt->store_result();
                
                if ($check_stmt->num_rows == 0) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_sql = "INSERT INTO teachers (username, password) VALUES (?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("ss", $username, $hashed_password);
                    
                    if ($insert_stmt->execute()) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errorMessages[] = "Row $row: " . $conn->error;
                    }
                } else {
                    $errorCount++;
                    $errorMessages[] = "Row $row: Username '$username' already exists";
                }
            } else {
                $errorCount++;
                $errorMessages[] = "Row $row: Missing username or password";
            }
        }
        
        $message = "<p class='success'>Successfully imported $successCount faculty members.</p>";
        if ($errorCount > 0) {
            $message .= "<p class='error'>$errorCount rows failed to import.</p>";
            $message .= "<div class='error-details' style='max-height: 150px; overflow-y: auto; margin-top: 10px;'>";
            $message .= "<ul>";
            foreach ($errorMessages as $error) {
                $message .= "<li>$error</li>";
            }
            $message .= "</ul></div>";
        }
    } catch (Exception $e) {
        $message = "<p class='error'>Error processing Excel file: " . $e->getMessage() . "</p>";
    }
}

// Handle subject assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_faculty'])) {
    $current_faculty = $_POST['assign_faculty'];
    $selected_subjects = isset($_POST['assign_subjects']) ? $_POST['assign_subjects'] : [];
    
    // Clear existing assignments
    $delete_sql = "DELETE FROM assign_subjects WHERE faculty_name = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("s", $current_faculty);
    $delete_stmt->execute();
    
    // Add new assignments if any
    if (!empty($selected_subjects)) {
        foreach ($selected_subjects as $subject) {
            $insert_sql = "INSERT INTO assign_subjects (subject_name, faculty_name) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $subject, $current_faculty);
            $insert_stmt->execute();
        }
        $assignment_message = "<p class='success'>Subjects assigned successfully!</p>";
    } else {
        $assignment_message = "<p class='success'>All subjects unassigned from $current_faculty!</p>";
    }
}

// Get all faculty members
$sql = "SELECT username FROM teachers ORDER BY username";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $faculty[] = $row['username'];
    }
}

// Get all unique subject names (ignoring branch/semester)
$sql = "SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
}

// Get assigned subjects for selected faculty
if (isset($_GET['faculty'])) {
    $current_faculty = $_GET['faculty'];
    $sql = "SELECT subject_name FROM assign_subjects WHERE faculty_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_faculty);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assigned_subjects = [];
    while ($row = $result->fetch_assoc()) {
        $assigned_subjects[] = $row['subject_name'];
    }
} elseif (isset($_POST['assign_faculty'])) {
    $current_faculty = $_POST['assign_faculty'];
    $sql = "SELECT subject_name FROM assign_subjects WHERE faculty_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $current_faculty);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assigned_subjects = [];
    while ($row = $result->fetch_assoc()) {
        $assigned_subjects[] = $row['subject_name'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>DRK Faculty Management</title>
    <link rel="stylesheet" type="text/css" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .dashboard-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab-btn {
            padding: 10px 30px;
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #7f8c8d;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            margin: 0 10px;
        }

        .tab-btn:hover {
            color: #3498db;
        }

        .tab-btn.active {
            color: #3498db;
            border-bottom: 3px solid #3498db;
        }

        .tab-content {
            display: none;
            padding: 20px 0;
        }

        .tab-content.active {
            display: block;
        }
        
        .add-faculty-options {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
        }
        
        .add-faculty-option {
            width: 300px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .add-faculty-option:hover {
            border-color: #3498db;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .add-faculty-option.active {
            border-color: #3498db;
            background-color: #e9f7fe;
        }
        
        .add-faculty-option h3 {
            margin-top: 0;
            color: #3498db;
        }
        
        .add-faculty-option p {
            color: #666;
        }
        
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="file"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
        }
        
        .btn-assign {
            background-color: #4CAF50;
            margin-top: 20px;
            width: 100%;
        }
        
        .btn-assign:hover {
            background-color: #45a049;
        }
        
        .subject-container {
            margin: 30px 0;
        }
        
        .subject-search {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .subject-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        
        .subject-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
            transition: all 0.2s;
        }
        
        .subject-item:hover {
            background: #f0f0f0;
        }
        
        .assigned {
            background: #e0f7fa;
            border-color: #4dd0e1;
        }
        
        .subject-item input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .hidden {
            display: none;
        }
        
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #007bff;
            text-decoration: none;
            font-size: 16px;
        }
        
        .select2-container {
            width: 100% !important;
            margin-bottom: 15px;
        }
        
        .select2-selection {
            height: auto !important;
            min-height: 38px !important;
            padding: 6px !important;
        }
        
        .currently-assigned {
            margin-bottom: 20px;
            padding: 15px;
            background: #e8f4f8;
            border-radius: 4px;
        }
        
        .currently-assigned h4 {
            margin-top: 0;
            color: #3498db;
        }
        
        .currently-assigned ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        
        #assign-subjects-content {
            display: none;
        }
        
        #assign-subjects-content.active {
            display: block;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #3498db;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h2 class="text-center">Faculty Management</h2>
        
        <?php if (!empty($message)): ?>
            <div class="message-container">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('add-faculty')">Add Faculty</button>
            <button class="tab-btn" onclick="openTab('assign-subjects')">Assign Subjects</button>
        </div>
        
        <!-- Add Faculty Tab -->
        <div id="add-faculty" class="tab-content active">
            <div class="add-faculty-options">
                <div class="add-faculty-option active" onclick="showFacultyOption('manual')">
                    <h3>Manual Entry</h3>
                    <p>Add faculty members one by one</p>
                </div>
                <div class="add-faculty-option" onclick="showFacultyOption('excel')">
                    <h3>Excel Upload</h3>
                    <p>Bulk upload faculty from Excel</p>
                </div>
            </div>
            
            <!-- Manual Entry Form -->
            <div id="manual-form" class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Add Faculty</button>
                </form>
            </div>
            
            <!-- Excel Upload Form -->
            <div id="excel-form" class="form-container hidden">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">Select Excel File</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".xls,.xlsx" required>
                    </div>
                    <button type="submit" class="btn">Upload Faculty</button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 4px;">
                    <h4>Excel File Format:</h4>
                    <a href="../templates/faculty_template.xlsx" class="btn btn-secondary" download>
    <i class="fas fa-file-download"></i> Download Template
</a>
                    <p>Please prepare your Excel file with exactly 2 columns:</p>
                    <ul>
                        <li><strong>Column A:</strong> Faculty name</li>
                        <li><strong>Column B:</strong> Password</li>
                    </ul>
                    <p>First row should contain headers, data starts from row 2.</p>
                </div>
            </div>
        </div>
        
        <!-- Assign Subjects Tab -->
        <div id="assign-subjects" class="tab-content">
            <?php if (!empty($assignment_message)): ?>
                <div class="message-container">
                    <?= $assignment_message ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-group">
                    <label for="assign_faculty">Select Faculty Member:</label>
                    <select id="assign_faculty" name="assign_faculty" class="faculty-select" required>
                        <option value="">-- Select Faculty --</option>
                        <?php foreach ($faculty as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" 
                                <?= ($current_faculty == $f ? 'selected' : '') ?>>
                                <?= htmlspecialchars($f) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="assign-subjects-content" class="<?= !empty($current_faculty) ? 'active' : '' ?>">
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner"></div>
                        <p>Loading faculty data...</p>
                    </div>
                    
                    <div id="facultySubjectsContent">
                        <?php if (!empty($current_faculty)): ?>
                            <div class="currently-assigned">
                                <h4>Currently Assigned to <?= htmlspecialchars($current_faculty) ?>:</h4>
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
                                <input type="hidden" name="assign_faculty" value="<?= htmlspecialchars($current_faculty) ?>">
                                
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function openTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Deactivate all tab buttons
            const tabButtons = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove('active');
            }
            
            // Activate the selected tab
            document.getElementById(tabName).classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        function showFacultyOption(option) {
            // Hide all forms
            document.getElementById('manual-form').classList.add('hidden');
            document.getElementById('excel-form').classList.add('hidden');
            
            // Show selected form
            document.getElementById(option + '-form').classList.remove('hidden');
            
            // Update active option
            document.querySelectorAll('.add-faculty-option').forEach(opt => {
                opt.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        $(document).ready(function() {
            // Initialize Select2 for faculty dropdown
            $('.faculty-select').select2({
                placeholder: "Select a faculty member",
                allowClear: true
            }).on('change', function() {
                const faculty = $(this).val();
                const contentDiv = $('#assign-subjects-content');
                const loadingSpinner = $('#loadingSpinner');
                const facultyContent = $('#facultySubjectsContent');
                
                if (faculty) {
                    // Show loading spinner
                    loadingSpinner.show();
                    facultyContent.hide();
                    contentDiv.addClass('active');
                    
                    // AJAX request to get faculty subjects
                    $.ajax({
                        url: 'get_faculty_subjects.php',
                        type: 'GET',
                        data: { faculty: faculty },
                        success: function(response) {
                            facultyContent.html(response);
                            facultyContent.show();
                            loadingSpinner.hide();
                            
                            // Initialize subject search functionality
                            $('#subjectSearch').on('input', function() {
                                const searchTerm = $(this).val().toLowerCase();
                                $('#subjectList .search-item').each(function() {
                                    const subjectText = $(this).text().toLowerCase();
                                    $(this).toggle(subjectText.includes(searchTerm));
                                });
                            });
                        },
                        error: function() {
                            facultyContent.html('<div class="error">Error loading faculty data</div>');
                            facultyContent.show();
                            loadingSpinner.hide();
                        }
                    });
                } else {
                    contentDiv.removeClass('active');
                }
            });

            // If faculty is already selected on page load, trigger the change event
            if ($('#assign_faculty').val()) {
                $('#assign_faculty').trigger('change');
            }

            // Subject search functionality for initial load (if faculty is selected)
            $('#subjectSearch').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('#subjectList .search-item').each(function() {
                    const subjectText = $(this).text().toLowerCase();
                    $(this).toggle(subjectText.includes(searchTerm));
                });
            });
        });
    </script>
    
    <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
</body>
</html>