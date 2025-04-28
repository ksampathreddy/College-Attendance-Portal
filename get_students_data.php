<?php
include 'db_connection.php';

$rollno = $_GET['rollno'];

// Get student's branch
$sql = "SELECT branch FROM students WHERE rollno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $rollno);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if ($student) {
    $branch = $student['branch'];
    
    // Get all semesters for this branch
    $semesterQuery = "SELECT DISTINCT semester FROM subjects WHERE branch = ? ORDER BY semester";
    $semesterStmt = $conn->prepare($semesterQuery);
    $semesterStmt->bind_param("s", $branch);
    $semesterStmt->execute();
    $semesterResult = $semesterStmt->get_result();
    
    $semesters = [];
    while ($row = $semesterResult->fetch_assoc()) {
        $semesters[] = $row['semester'];
    }
    
    // Get subjects for the first semester by default
    $subjectQuery = "SELECT DISTINCT subject_name FROM subjects WHERE branch = ? AND semester = ? ORDER BY subject_name";
    $subjectStmt = $conn->prepare($subjectQuery);
    $firstSemester = $semesters[0] ?? '';
    $subjectStmt->bind_param("ss", $branch, $firstSemester);
    $subjectStmt->execute();
    $subjectResult = $subjectStmt->get_result();
    
    $subjects = [];
    while ($row = $subjectResult->fetch_assoc()) {
        $subjects[] = $row['subject_name'];
    }
    
    echo json_encode([
        'semesters' => $semesters,
        'subjects' => $subjects
    ]);
} else {
    echo json_encode([
        'semesters' => [],
        'subjects' => []
    ]);
}
?>