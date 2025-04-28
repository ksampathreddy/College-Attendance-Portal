<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login page if not logged in as teacher
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: ../index.php");
    exit();
}

include '../db_connection.php';

// Initialize a variable to store the message
$message = "";

// Fetch distinct batches, branches, and subjects from the database
$batches = [];
$branches = [];
$subjects = [];

$batch_query = "SELECT DISTINCT batch FROM students ORDER BY batch DESC";
$branch_query = "SELECT DISTINCT branch FROM students ORDER BY branch";
$subject_query = "SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name";

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

if ($result = $conn->query($subject_query)) {
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
    $result->free();
}

// Handle erase attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batch = $_POST['batch'];
    $branch = $_POST['branch'];
    $subject = $_POST['subject'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Validate date range
    if ($startDate > $endDate) {
        $message = "<div class='alert alert-error'>Start date cannot be later than end date.</div>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete attendance records within the specified date range
            $sql = "DELETE a FROM attendance a
                    JOIN students s ON a.rollno = s.rollno
                    WHERE s.batch = ? AND s.branch = ? AND a.subject = ? 
                    AND a.date BETWEEN ? AND ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("sssss", $batch, $branch, $subject, $startDate, $endDate);
                if ($stmt->execute()) {
                    $rowsAffected = $stmt->affected_rows;
                    $conn->commit();
                    $message = "<div class='alert alert-success'>$rowsAffected attendance records from $startDate to $endDate have been erased successfully!</div>";
                } else {
                    throw new Exception("Error executing delete query");
                }
                $stmt->close();
            } else {
                throw new Exception("Database preparation error");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-error'>Error erasing attendance records: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Erase Attendance - DRKIST</title>
    <link rel="stylesheet" type="text/css" href="../assets/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/drk.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <style>
        .dashboard-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            /* background: #fff; */
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            background-color: #dc3545;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #c82333;
        }
        
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #800000;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Select2 customization */
        .select2-container--default .select2-selection--single {
            height: 42px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
    </style>
</head>
<body>
     <div class="dashboard-container">
        <h1>Erase Attendance</h1>
        
        <!-- Display the message here -->
        <?php if (!empty($message)): ?>
            <?= $message ?>
        <?php endif; ?>

        <!-- Erase Attendance Form -->
        <form method="POST" id="eraseForm">
            <div class="form-group">
                <label for="batch">Batch:</label>
                <select id="batch" name="batch" required>
                    <?php foreach ($batches as $batch): ?>
                        <option value="<?= htmlspecialchars($batch) ?>"><?= htmlspecialchars($batch) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="branch">Branch:</label>
                <select id="branch" name="branch" required>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= htmlspecialchars($branch) ?>"><?= htmlspecialchars($branch) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="subject">Subject:</label>
                <select id="subject" name="subject" class="subject-select" required>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= htmlspecialchars($subject) ?>"><?= htmlspecialchars($subject) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            <button type="submit" onclick="return confirm('Are you sure you want to delete all attendance records within this date range? This action cannot be undone.')">
                Erase Attendance
            </button>
        </form>

        <a href="teacher_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        // Initialize Select2 for subject dropdown with search
        $(document).ready(function() {
            $('.subject-select').select2({
                placeholder: "Select or search a subject",
                allowClear: true,
                width: '100%'
            });
            
            // Restrict date inputs to past and present dates
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').max = today;
            document.getElementById('end_date').max = today;
            
            // Set default end date to today
            document.getElementById('end_date').value = today;
            
            // Set default start date to 7 days ago
            const sevenDaysAgo = new Date();
            sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
            document.getElementById('start_date').value = sevenDaysAgo.toISOString().split('T')[0];
            
            // Date validation
            document.getElementById('start_date').addEventListener('change', function() {
                const endDate = document.getElementById('end_date');
                if (this.value > endDate.value) {
                    alert('Start date cannot be after end date!');
                    this.value = endDate.value;
                }
            });
            
            document.getElementById('end_date').addEventListener('change', function() {
                const startDate = document.getElementById('start_date');
                if (this.value < startDate.value) {
                    alert('End date cannot be before start date!');
                    this.value = startDate.value;
                }
            });
        });
    </script>
</body>
</html>
<?php
// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>