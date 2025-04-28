<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

include '../db_connection.php';

$response = [
    'assigned_subjects' => [],
    'assigned_html' => '<p>No subjects currently assigned.</p>'
];

if (isset($_GET['faculty'])) {
    $faculty = $_GET['faculty'];
    
    // Get assigned subjects
    $sql = "SELECT subject_name FROM assign_subjects WHERE faculty_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $faculty);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assigned_subjects = [];
    while ($row = $result->fetch_assoc()) {
        $assigned_subjects[] = $row['subject_name'];
    }
    $response['assigned_subjects'] = $assigned_subjects;
    
    // Generate HTML for assigned subjects list
    if (!empty($assigned_subjects)) {
        $assigned_html = '<h4>Currently Assigned to ' . htmlspecialchars($faculty) . ':</h4><ul>';
        foreach ($assigned_subjects as $subject) {
            $assigned_html .= '<li>' . htmlspecialchars($subject) . '</li>';
        }
        $assigned_html .= '</ul>';
        $response['assigned_html'] = $assigned_html;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>