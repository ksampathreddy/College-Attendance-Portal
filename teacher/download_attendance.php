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

// Require PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to send error response
function sendError($message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required parameters
        if (!isset($_POST['view_type'])) {
            sendError('Missing view_type parameter');
        }

        $batch = $_POST['batch'] ?? '';
        $branch = $_POST['branch'] ?? '';
        $view_type = $_POST['view_type'];
        $semester = $_POST['semester'] ?? '';
        $section = isset($_POST['section']) && $_POST['section'] === 'NULL' ? NULL : ($_POST['section'] ?? '');
        $date = $_POST['date'] ?? '';
        $faculty = $_POST['faculty'] ?? $_SESSION['username'];

        // Create new Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        if ($view_type == 'subject_all') {
            // Subject-wise attendance for all subjects (from subject form)
            
            // Build WHERE conditions
            $whereConditions = "WHERE a.faculty = ?";
            $params = [$faculty];
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
            if ($section !== '') {
                if ($section === NULL) {
                    $whereConditions .= " AND a.section IS NULL";
                } else {
                    $whereConditions .= " AND a.section = ?";
                    $params[] = $section;
                    $paramTypes .= "s";
                }
            }
            
            // Get all subjects assigned to this faculty with the selected filters
            $sqlSubjects = "SELECT DISTINCT a.subject 
                           FROM attendance a
                           JOIN assign_subjects s ON a.subject = s.subject_name
                           $whereConditions
                           ORDER BY a.subject";
            $stmtSubjects = $conn->prepare($sqlSubjects);
            if (!$stmtSubjects) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmtSubjects->bind_param($paramTypes, ...$params);
            if (!$stmtSubjects->execute()) {
                throw new Exception("Execute failed: " . $stmtSubjects->error);
            }
            $resultSubjects = $stmtSubjects->get_result();
            
            $subjectList = [];
            while ($subjectRow = $resultSubjects->fetch_assoc()) {
                $subjectList[] = $subjectRow['subject'];
            }
            
            if (empty($subjectList)) {
                sendError('No subjects found for the selected criteria');
            }
            
            // Get total classes for each subject
            $subjectTotals = [];
            foreach ($subjectList as $subject) {
                $sqlTotal = "SELECT COUNT(DISTINCT date) as total FROM attendance 
                            WHERE faculty = ? AND subject = ?";
                $paramsTotal = [$faculty, $subject];
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
                if ($section !== '') {
                    if ($section === NULL) {
                        $sqlTotal .= " AND section IS NULL";
                    } else {
                        $sqlTotal .= " AND section = ?";
                        $paramsTotal[] = $section;
                        $typesTotal .= "s";
                    }
                }
                
                $stmtTotal = $conn->prepare($sqlTotal);
                if (!$stmtTotal) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                $stmtTotal->bind_param($typesTotal, ...$paramsTotal);
                if (!$stmtTotal->execute()) {
                    throw new Exception("Execute failed: " . $stmtTotal->error);
                }
                $resultTotal = $stmtTotal->get_result();
                $row = $resultTotal->fetch_assoc();
                $subjectTotals[$subject] = $row ? $row['total'] : 0;
            }
            
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
            if ($section !== '') {
                if ($section === NULL) {
                    $sqlStudents .= " AND section IS NULL";
                } else {
                    $sqlStudents .= " AND section = ?";
                    $studentParams[] = $section;
                    $studentTypes .= "s";
                }
            }
            
            $sqlStudents .= " ORDER BY rollno";
            $stmtStudents = $conn->prepare($sqlStudents);
            if (!$stmtStudents) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            if (!empty($studentParams)) {
                $stmtStudents->bind_param($studentTypes, ...$studentParams);
            }
            
            if (!$stmtStudents->execute()) {
                throw new Exception("Execute failed: " . $stmtStudents->error);
            }
            $resultStudents = $stmtStudents->get_result();
            
            if ($resultStudents->num_rows === 0) {
                sendError('No students found for the selected criteria');
            }
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator("DRKIST Attendance System")
                ->setTitle("Subject-wise Attendance Report")
                ->setSubject("Subject-wise Attendance Report");
            
            // Add title
            $lastCol = chr(ord('A') + count($subjectList) * 2 + 1);
            $sheet->mergeCells('A1:'.$lastCol.'1');
            $title = "Subject-wise Attendance Report - Faculty: $faculty";
            if (!empty($batch)) $title .= ", Batch: $batch";
            if (!empty($branch)) $title .= ", Branch: $branch";
            if (!empty($semester)) $title .= ", Semester: $semester";
            if ($section !== '') $title .= ", Section: " . ($section === NULL ? "No Section" : $section);
            
            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add headers
            $headers = ['Roll No', 'Name'];
            foreach ($subjectList as $subject) {
                $headers[] = $subject . ' (Attended)';
                $headers[] = $subject . ' (%)';
            }
            $sheet->fromArray($headers, null, 'A2');
            
            // Add data
            $row = 3;
            while ($student = $resultStudents->fetch_assoc()) {
                $data = [$student['rollno'], $student['name']];
                
                foreach ($subjectList as $subject) {
                    $sqlAttended = "SELECT COUNT(*) as attended FROM attendance 
                                  WHERE faculty = ? AND rollno = ? AND subject = ?";
                    $paramsAttended = [$faculty, $student['rollno'], $subject];
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
                    if ($section !== '') {
                        if ($section === NULL) {
                            $sqlAttended .= " AND section IS NULL";
                        } else {
                            $sqlAttended .= " AND section = ?";
                            $paramsAttended[] = $section;
                            $typesAttended .= "s";
                        }
                    }
                    
                    $sqlAttended .= " AND status = 'Present'";
                    
                    $stmtAttended = $conn->prepare($sqlAttended);
                    if (!$stmtAttended) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmtAttended->bind_param($typesAttended, ...$paramsAttended);
                    if (!$stmtAttended->execute()) {
                        throw new Exception("Execute failed: " . $stmtAttended->error);
                    }
                    $resultAttended = $stmtAttended->get_result();
                    $attended = $resultAttended->fetch_assoc()['attended'];
                    
                    $total = $subjectTotals[$subject];
                    $percentage = $total > 0 ? round(($attended / $total) * 100, 2) : 0;
                    
                    $data[] = $attended;
                    $data[] = $percentage/100;
                }
                
                $sheet->fromArray($data, null, 'A'.$row);
                
                // Format percentage columns
                $col = 'C';
                foreach ($subjectList as $subject) {
                    $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('0');
                    $col++;
                    $sheet->getStyle($col.$row)->getNumberFormat()->setFormatCode('0.00%');
                    $col++;
                }
                $row++;
            }
            
            // Set filename
            $filename = "Subject_Attendance_Report_".date('Y-m-d').".xlsx";
            
        } elseif ($view_type == 'date') {
            // Date-wise attendance (from date form)
            
            if (empty($date)) {
                sendError('Date parameter is required for date-wise report');
            }
            
            // Build WHERE conditions
            $whereConditions = "WHERE a.faculty = ?";
            $params = [$faculty];
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
            if ($section !== '') {
                if ($section === NULL) {
                    $whereConditions .= " AND a.section IS NULL";
                } else {
                    $whereConditions .= " AND a.section = ?";
                    $params[] = $section;
                    $paramTypes .= "s";
                }
            }
            
            // Get all subjects that had classes with the selected filters
            $sqlSubjects = "SELECT DISTINCT a.subject FROM attendance a 
                           $whereConditions
                           ORDER BY a.subject";
            $stmtSubjects = $conn->prepare($sqlSubjects);
            if (!$stmtSubjects) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmtSubjects->bind_param($paramTypes, ...$params);
            if (!$stmtSubjects->execute()) {
                throw new Exception("Execute failed: " . $stmtSubjects->error);
            }
            $resultSubjects = $stmtSubjects->get_result();
            
            $subjectList = [];
            while ($subjectRow = $resultSubjects->fetch_assoc()) {
                $subjectList[] = $subjectRow['subject'];
            }
            
            if (empty($subjectList)) {
                sendError('No attendance records found for the selected date and criteria');
            }
            
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
            if ($section !== '') {
                if ($section === NULL) {
                    $sqlStudents .= " AND section IS NULL";
                } else {
                    $sqlStudents .= " AND section = ?";
                    $studentParams[] = $section;
                    $studentTypes .= "s";
                }
            }
            
            $sqlStudents .= " ORDER BY rollno";
            $stmtStudents = $conn->prepare($sqlStudents);
            if (!$stmtStudents) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            if (!empty($studentParams)) {
                $stmtStudents->bind_param($studentTypes, ...$studentParams);
            }
            
            if (!$stmtStudents->execute()) {
                throw new Exception("Execute failed: " . $stmtStudents->error);
            }
            $resultStudents = $stmtStudents->get_result();
            
            if ($resultStudents->num_rows === 0) {
                sendError('No students found for the selected criteria');
            }
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator("DRKIST Attendance System")
                ->setTitle("Date-wise Attendance Report")
                ->setSubject("Date-wise Attendance Report");
            
            // Add title
            $lastCol = chr(ord('A') + count($subjectList) + 1);
            $sheet->mergeCells('A1:'.$lastCol.'1');
            $title = "Date-wise Attendance Report - Faculty: $faculty, Date: ".date('d-m-Y', strtotime($date));
            if (!empty($batch)) $title .= ", Batch: $batch";
            if (!empty($branch)) $title .= ", Branch: $branch";
            if ($section !== '') $title .= ", Section: " . ($section === NULL ? "No Section" : $section);
            
            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Add headers
            $headers = ['Roll No', 'Name'];
            $headers = array_merge($headers, $subjectList);
            $sheet->fromArray($headers, null, 'A2');
            
            // Add data
            $row = 3;
            while ($student = $resultStudents->fetch_assoc()) {
                $data = [$student['rollno'], $student['name']];
                
                foreach ($subjectList as $subject) {
                    $sqlStatus = "SELECT a.status FROM attendance a 
                                 WHERE a.faculty = ? AND a.rollno = ? AND a.subject = ?";
                    $paramsStatus = [$faculty, $student['rollno'], $subject];
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
                    if ($section !== '') {
                        if ($section === NULL) {
                            $sqlStatus .= " AND a.section IS NULL";
                        } else {
                            $sqlStatus .= " AND a.section = ?";
                            $paramsStatus[] = $section;
                            $typesStatus .= "s";
                        }
                    }
                    
                    $stmtStatus = $conn->prepare($sqlStatus);
                    if (!$stmtStatus) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    $stmtStatus->bind_param($typesStatus, ...$paramsStatus);
                    if (!$stmtStatus->execute()) {
                        throw new Exception("Execute failed: " . $stmtStatus->error);
                    }
                    $resultStatus = $stmtStatus->get_result();
                    
                    if ($resultStatus->num_rows > 0) {
                        $status = $resultStatus->fetch_assoc()['status'];
                    } else {
                        $status = 'Absent';
                    }
                    
                    $data[] = $status;
                }
                
                $sheet->fromArray($data, null, 'A'.$row);
                
                // Color coding for status cells
                $col = 'C';
                foreach ($subjectList as $subject) {
                    $cellValue = $sheet->getCell($col.$row)->getValue();
                    $fillColor = ($cellValue == 'Present') ? 'FFC6EFCE' : 'FFFFC7CE';
                    
                    $sheet->getStyle($col.$row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($fillColor);
                    
                    $col++;
                }
                $row++;
            }
            
            // Set filename
            $filename = "Date_Attendance_Report_".date('Y-m-d').".xlsx";
        } else {
            sendError('Invalid view_type specified');
        }
        
        // Style headers
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF800000']
            ]
        ];
        
        // Apply header style
        $lastHeaderRow = 2;
        $lastHeaderCol = $view_type == 'subject_all' ? chr(ord('A') + count($subjectList) * 2 + 1) : chr(ord('A') + count($subjectList) + 1);
        
        $sheet->getStyle('A'.$lastHeaderRow.':'.$lastHeaderCol.$lastHeaderRow)->applyFromArray($headerStyle);
        
        // Set alignment for all cells
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().$sheet->getHighestRow())
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Freeze panes for better navigation
        $sheet->freezePane($view_type == 'subject_all' ? 'C3' : 'C3');
        
        // Clear any previous output
        if (ob_get_length()) ob_end_clean();
        
        // Set headers
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create writer and save to output
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
        
    } catch (Exception $e) {
        // Log the error
        error_log("Error generating attendance report: " . $e->getMessage());
        
        // Send error response
        sendError('Error generating report: ' . $e->getMessage());
    }
} else {
    // If not POST request, redirect back
    header("Location: view_attendance.php");
    exit();
}