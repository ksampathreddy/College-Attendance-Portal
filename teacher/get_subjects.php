<?php
include '../db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$branch = $_GET['branch'] ?? '';
$semester = $_GET['semester'] ?? '';
$faculty = $_SESSION['username']; // Get logged-in teacher's username

if (empty($branch) || empty($semester)) {
    echo json_encode(['error' => 'Branch and semester are required']);
    exit();
}

try {
    // Get subjects assigned to this teacher for selected branch/semester
    $query = "SELECT s.subject_name 
              FROM subjects s
              JOIN assign_subjects a ON s.subject_name = a.subject_name
              WHERE s.branch = ? 
              AND s.semester = ? 
              AND a.faculty_name = ?
              ORDER BY s.subject_name";
              
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sss", $branch, $semester, $faculty);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subjects = [];
    while ($row = $result->fetch_assoc()) {
        $subjects[] = $row;
    }
    
    if (empty($subjects)) {
        echo json_encode(['message' => 'No subjects assigned to you for this branch/semester']);
    } else {
        echo json_encode($subjects);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>