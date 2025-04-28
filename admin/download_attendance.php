<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

include '../db_connection.php';

// Check if required parameters are present
if (!isset($_POST['batch']) || !isset($_POST['branch']) || !isset($_POST['view_type'])) {
    die("Invalid request");
}

$batch = $_POST['batch'];
$branch = $_POST['branch'];
$viewType = $_POST['view_type'];
$section = isset($_POST['section']) ? $_POST['section'] : null;
$semester = isset($_POST['semester']) ? $_POST['semester'] : null;
$date = isset($_POST['date']) ? $_POST['date'] : null;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set default styles
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '800000']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];

$titleStyle = [
    'font' => ['bold' => true, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];

$dataStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
];

$row = 1;

switch ($viewType) {
    case 'total':
        // Get total classes
        $totalClasses = $conn->query("SELECT COUNT(DISTINCT CONCAT(subject, date)) as total FROM attendance 
                                    WHERE batch='$batch' AND branch='$branch' 
                                    AND semester='$semester' " . ($section ? "AND (section='$section' OR section IS NULL)" : ""))->fetch_assoc()['total'];
        
        // Set title
        $sheet->setCellValue('A'.$row, 'Total Attendance Summary');
        $sheet->mergeCells('A'.$row.':E'.$row);
        $sheet->getStyle('A'.$row)->applyFromArray($titleStyle);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Batch: '.$batch.', Branch: '.$branch.', Semester: '.$semester.($section ? ', Section: '.$section : ''));
        $sheet->mergeCells('A'.$row.':E'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Total Classes: '.$totalClasses);
        $sheet->mergeCells('A'.$row.':E'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        
        // Set headers
        $sheet->setCellValue('A'.$row, 'Roll No');
        $sheet->setCellValue('B'.$row, 'Name');
        $sheet->setCellValue('C'.$row, 'Present');
        $sheet->setCellValue('D'.$row, 'Total');
        $sheet->setCellValue('E'.$row, 'Percentage');
        $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($headerStyle);
        $row++;
        
        // Get students and their attendance
        $students = $conn->query("SELECT s.rollno, s.name, 
                                (SELECT COUNT(*) FROM attendance a 
                                 WHERE a.rollno=s.rollno AND a.batch=s.batch AND a.branch=s.branch 
                                 AND a.semester='$semester' " . ($section ? "AND (a.section='$section' OR a.section IS NULL)" : "") . " AND a.status='Present') as attended
                                FROM students s 
                                WHERE s.batch='$batch' AND s.branch='$branch' " . 
                                ($section ? "AND (s.section='$section' OR s.section IS NULL)" : "") . 
                                " ORDER BY s.rollno");
        
        while ($student = $students->fetch_assoc()) {
            $percentage = $totalClasses > 0 ? round(($student['attended'] / $totalClasses) * 100, 2) : 0;
            
            $sheet->setCellValue('A'.$row, $student['rollno']);
            $sheet->setCellValue('B'.$row, $student['name']);
            $sheet->setCellValue('C'.$row, $student['attended']);
            $sheet->setCellValue('D'.$row, $totalClasses);
            $sheet->setCellValue('E'.$row, $percentage.'%');
            $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($dataStyle);
            $row++;
        }
        break;
        
    case 'subject_all':
        // Get all subjects
        $subjects = $conn->query("SELECT DISTINCT subject, faculty FROM attendance 
                                WHERE batch='$batch' AND branch='$branch' 
                                AND semester='$semester' " . ($section ? "AND (section='$section' OR section IS NULL)" : "") . "
                                ORDER BY subject");
        
        $subjectData = [];
        while ($subj = $subjects->fetch_assoc()) {
            $subject = $subj['subject'];
            $total = $conn->query("SELECT COUNT(DISTINCT date) as total FROM attendance 
                                 WHERE batch='$batch' AND branch='$branch' 
                                 AND semester='$semester' AND subject='$subject' " . ($section ? "AND (section='$section' OR section IS NULL)" : ""))
                                 ->fetch_assoc()['total'];
            $subjectData[] = [
                'name' => $subject,
                'total' => $total,
                'faculty' => $subj['faculty']
            ];
        }
        
        // Set title
        $sheet->setCellValue('A'.$row, 'Subject-wise Attendance');
        $lastCol = chr(ord('A') + count($subjectData) + 1);
        $sheet->mergeCells('A'.$row.':'.$lastCol.$row);
        $sheet->getStyle('A'.$row)->applyFromArray($titleStyle);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Batch: '.$batch.', Branch: '.$branch.', Semester: '.$semester.($section ? ', Section: '.$section : ''));
        $sheet->mergeCells('A'.$row.':'.$lastCol.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        
        // Set headers
        $sheet->setCellValue('A'.$row, 'Roll No');
        $sheet->setCellValue('B'.$row, 'Name');
        
        $col = 'C';
        foreach ($subjectData as $subject) {
            $sheet->setCellValue($col.$row, $subject['name']."\n(Total: ".$subject['total'].")\nFaculty: ".$subject['faculty']);
            $sheet->getStyle($col.$row)->getAlignment()->setWrapText(true);
            $col++;
        }
        
        $sheet->getStyle('A'.$row.':'.$lastCol.$row)->applyFromArray($headerStyle);
        $row++;
        
        // Get students
        $students = $conn->query("SELECT rollno, name FROM students 
                                WHERE batch='$batch' AND branch='$branch' " . 
                                ($section ? "AND (section='$section' OR section IS NULL)" : "") . 
                                " ORDER BY rollno");
        
        while ($student = $students->fetch_assoc()) {
            $sheet->setCellValue('A'.$row, $student['rollno']);
            $sheet->setCellValue('B'.$row, $student['name']);
            
            $col = 'C';
            foreach ($subjectData as $subject) {
                $attended = $conn->query("SELECT COUNT(*) as attended FROM attendance 
                                        WHERE rollno='{$student['rollno']}' AND batch='$batch' 
                                        AND branch='$branch' AND semester='$semester' 
                                        AND subject='{$subject['name']}' " . ($section ? "AND (section='$section' OR section IS NULL)" : "") . " AND status='Present'")
                                  ->fetch_assoc()['attended'];
                $percentage = $subject['total'] > 0 ? round(($attended / $subject['total']) * 100, 2) : 0;
                
                $sheet->setCellValue($col.$row, "Present: $attended\nPercentage: $percentage%");
                $sheet->getStyle($col.$row)->getAlignment()->setWrapText(true);
                $col++;
            }
            
            $sheet->getStyle('A'.$row.':'.$lastCol.$row)->applyFromArray($dataStyle);
            $row++;
        }
        break;
        
    case 'date':
        // Get subjects taught on this date
        $subjects = $conn->query("SELECT DISTINCT subject, faculty FROM attendance 
                                WHERE batch='$batch' AND branch='$branch' 
                                AND date='$date' " . ($section ? "AND (section='$section' OR section IS NULL)" : "") . "
                                ORDER BY subject");
        
        $subjectList = [];
        while ($subj = $subjects->fetch_assoc()) {
            $subjectList[] = [
                'name' => $subj['subject'],
                'faculty' => $subj['faculty']
            ];
        }
        
        // Set title
        $sheet->setCellValue('A'.$row, 'Attendance on '.date('d-m-Y', strtotime($date)));
        $lastCol = chr(ord('A') + count($subjectList) + 1);
        $sheet->mergeCells('A'.$row.':'.$lastCol.$row);
        $sheet->getStyle('A'.$row)->applyFromArray($titleStyle);
        $row++;
        
        $sheet->setCellValue('A'.$row, 'Batch: '.$batch.', Branch: '.$branch.($section ? ', Section: '.$section : ''));
        $sheet->mergeCells('A'.$row.':'.$lastCol.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $row++;
        
        // Set headers
        $sheet->setCellValue('A'.$row, 'Roll No');
        $sheet->setCellValue('B'.$row, 'Name');
        
        $col = 'C';
        foreach ($subjectList as $subject) {
            $sheet->setCellValue($col.$row, $subject['name']."\n(Faculty: ".$subject['faculty'].')');
            $sheet->getStyle($col.$row)->getAlignment()->setWrapText(true);
            $col++;
        }
        
        $sheet->getStyle('A'.$row.':'.$lastCol.$row)->applyFromArray($headerStyle);
        $row++;
        
        // Get students
        $students = $conn->query("SELECT rollno, name FROM students 
                                WHERE batch='$batch' AND branch='$branch' " . 
                                ($section ? "AND (section='$section' OR section IS NULL)" : "") . 
                                " ORDER BY rollno");
        
        while ($student = $students->fetch_assoc()) {
            $sheet->setCellValue('A'.$row, $student['rollno']);
            $sheet->setCellValue('B'.$row, $student['name']);
            
            $col = 'C';
            foreach ($subjectList as $subject) {
                $status = $conn->query("SELECT status FROM attendance 
                                      WHERE rollno='{$student['rollno']}' AND batch='$batch' 
                                      AND branch='$branch' AND date='$date' 
                                      AND subject='{$subject['name']}' " . ($section ? "AND (section='$section' OR section IS NULL)" : ""))
                               ->fetch_assoc();
                $status = $status ? $status['status'] : 'Absent';
                
                $sheet->setCellValue($col.$row, $status);
                $sheet->getStyle($col.$row)->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(
                        $status == 'Present' ? \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKGREEN : \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED
                    )
                );
                $col++;
            }
            
            $sheet->getStyle('A'.$row.':'.$lastCol.$row)->applyFromArray($dataStyle);
            $row++;
        }
        break;
}

// Auto-size columns
foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="attendance_report_'.date('Y-m-d').'.xlsx"');
header('Cache-Control: max-age=0');

// Create Xlsx writer and output to browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();