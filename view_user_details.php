<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: manage_employees.php");
    exit();
}

$user_id = mysqli_real_escape_string($conn, $_GET['id']);

// User Data
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_res);

if (!$user_data) { die("User not found!"); }

// Data Fetching
$expenses = mysqli_query($conn, "SELECT * FROM expense_records WHERE user_id = '$user_id' ORDER BY date DESC");
$savings = mysqli_query($conn, "SELECT * FROM savings WHERE user_id = '$user_id' ORDER BY date DESC");
$loans = mysqli_query($conn, "SELECT * FROM loans WHERE user_id = '$user_id' ORDER BY due_date DESC");

// Total Calculations
$total_ex = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM expense_records WHERE user_id = '$user_id'"))['total'] ?? 0;
$total_sv = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM savings WHERE user_id = '$user_id'"))['total'] ?? 0;
$total_ln = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM loans WHERE user_id = '$user_id'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Report | <?php echo $user_data['full_name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        :root {
            --primary: #6366f1; --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
            --slate-800: #1e293b; --slate-500: #64748b; --bg: #f1f5f9;
        }

        body { background: var(--bg); font-family: 'Inter', sans-serif; padding: 40px; color: var(--slate-800); }
        .container { max-width: 900px; margin: 0 auto; }

        .no-print-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        
        .btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; cursor: pointer; border: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-back { background: transparent; color: var(--slate-500); }
        .btn-pdf { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        /* Report Styling */
        #report-content { background: white; padding: 50px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        
        .report-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; }
        .logo-text { font-size: 1.5rem; font-weight: 800; color: var(--primary); letter-spacing: -1px; }
        
        .user-meta h1 { font-size: 1.8rem; margin-bottom: 5px; }
        .user-meta p { color: var(--slate-500); }

        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .summary-card { padding: 20px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .summary-card label { display: block; font-size: 0.75rem; text-transform: uppercase; color: var(--slate-500); font-weight: 700; margin-bottom: 5px; }
        .summary-card .val { font-size: 1.2rem; font-weight: 800; }

        .table-section { margin-bottom: 35px; }
        .table-section h3 { font-size: 1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; border-left: 4px solid var(--primary); padding-left: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f5f9; color: var(--slate-500); font-size: 0.75rem; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #f8fafc; font-size: 0.85rem; }

        .footer-note { margin-top: 50px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 0.75rem; color: var(--slate-500); }
    </style>
</head>
<body>

<div class="container">
    <div class="no-print-area">
        <a href="manage_employees.php" class="btn btn-back"><i class="fas fa-chevron-left"></i> Back</a>
        <button onclick="downloadPDF()" class="btn btn-pdf"><i class="fas fa-file-pdf"></i> Download PDF Report</button>
    </div>

    <div id="report-content">
        <div class="report-header">
            <div class="logo-text">SMART BUDGET <span style="color:var(--slate-800)">PRO</span></div>
            <div style="text-align: right; color: var(--slate-500); font-size: 0.8rem;">
                Generated on: <?php echo date('Y-m-d H:i'); ?>
            </div>
        </div>

        <div class="user-meta">
            <h1><?php echo strtoupper($user_data['full_name']); ?></h1>
            <p><?php echo $user_data['department']; ?> Section | ID: #<?php echo $user_data['user_id']; ?></p>
            <p style="font-size: 0.85rem;"><?php echo $user_data['email']; ?></p>
        </div>

        <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 30px 0;">

        <div class="summary-grid">
            <div class="summary-card">
                <label>Total Expenses</label>
                <div class="val" style="color: var(--danger);">Rs. <?php echo number_format($total_ex, 2); ?></div>
            </div>
            <div class="summary-card">
                <label>Total Savings</label>
                <div class="val" style="color: var(--success);">Rs. <?php echo number_format($total_sv, 2); ?></div>
            </div>
            <div class="summary-card">
                <label>Active Loans</label>
                <div class="val" style="color: var(--warning);">Rs. <?php echo number_format($total_ln, 2); ?></div>
            </div>
        </div>

        <div class="table-section">
            <h3><i class="fas fa-arrow-up"></i> Recent Expenses</h3>
            <table>
                <thead>
                    <tr><th>Date</th><th>Category/Description</th><th style="text-align:right">Amount</th></tr>
                </thead>
                <tbody>
                    <?php while($ex = mysqli_fetch_assoc($expenses)): ?>
                    <tr>
                        <td><?php echo $ex['date']; ?></td>
                        <td><?php echo $ex['description'] ?? 'General'; ?></td>
                        <td style="text-align:right; font-weight:600;">Rs. <?php echo number_format($ex['amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-section">
            <h3><i class="fas fa-hand-holding-dollar"></i> Loan Statements</h3>
            <table>
                <thead>
                    <tr><th>Issue Date</th><th>Reason</th><th style="text-align:right">Principle Amt</th></tr>
                </thead>
                <tbody>
                    <?php while($ln = mysqli_fetch_assoc($loans)): ?>
                    <tr>
                        <td><?php echo $ln['loan_date']; ?></td>
                        <td><?php echo $ln['reason']; ?></td>
                        <td style="text-align:right; font-weight:600;">Rs. <?php echo number_format($ln['total_amount'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-note">
            This is a computer-generated financial statement from Smart Budget Management System.
        </div>
    </div>
</div>

<script>
    function downloadPDF() {
        const element = document.getElementById('report-content');
        const options = {
            margin:       0.5,
            filename:     'Financial_Report_<?php echo $user_data["full_name"]; ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(options).from(element).save();
    }
</script>

</body>
</html>