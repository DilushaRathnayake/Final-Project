<?php
require_once 'config.php';

if (isset($_GET['month'])) {
    $month = $_GET['month'];
    $filename = "Salary_Report_" . $month . ".csv";
    
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$filename");
    
    $output = fopen("php://output", "w");
    // CSV එකේ තීරු නාම
    fputcsv($output, array('Date', 'Employee Name', 'Gross Salary', 'EPF 8%', 'EPF 12%', 'ETF 3%', 'Net Salary'));
    
    $query = "SELECT s.*, u.full_name FROM salary_records s 
              JOIN users u ON s.user_id = u.user_id 
              WHERE DATE_FORMAT(s.pay_date, '%Y-%m') = '$month'";
              
    $rows = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($rows)) {
        fputcsv($output, array(
            $row['pay_date'],
            $row['full_name'],
            $row['gross_salary'],
            $row['epf_employee'],
            $row['epf_employer'],
            $row['etf_employer'],
            $row['net_salary']
        ));
    }
    fclose($output);
    exit;
}
?>