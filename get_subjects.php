<?php
include 'db_connection.php';

$rollno = $_GET['rollno'];
$semester = $_GET['semester'] ?? '';

// Get student's branch
$sql = "SELECT branch FROM students WHERE rollno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $rollno);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($student) {
    $branch = $student['branch'];
    
    // Get subjects for this branch and semester
    $subjectQuery = "SELECT DISTINCT subject_name FROM subjects WHERE branch = ? AND semester = ? ORDER BY subject_name";
    $subjectStmt = $conn->prepare($subjectQuery);
    $subjectStmt->bind_param("ss", $branch, $semester);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    
    $subjects = [];
    while ($row = $subjectResult->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
    
    echo json_encode([
        'subjects' => $subjects
    ]);
} else {
    echo json_encode([
        'subjects' => []
    ]);
}
?>