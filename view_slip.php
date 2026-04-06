<?php
require_once 'config.php';
if(isset($_GET['id'])){
    $s_id = mysqli_real_escape_string($conn, $_GET['id']);
    $res = mysqli_query($conn, "SELECT s.*, u.full_name, u.department FROM salary_records s JOIN users u ON s.user_id = u.user_id WHERE s.salary_id = '$s_id'");
    $data = mysqli_fetch_assoc($res);
} else {
    die("Invalid Request");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - <?php echo $data['full_name']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; padding: 40px; }
        .slip-card { background: white; width: 600px; margin: auto; padding: 40px; border-radius: 15px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .header { text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 30px; }
        .header h2 { color: #6366f1; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem; }
        .label { color: #64748b; font-weight: 500; }
        .value { color: #1e293b; font-weight: 700; }
        .divider { border-top: 1px dashed #e2e8f0; margin: 20px 0; }
        .total-row { background: #f8fafc; padding: 15px; border-radius: 10px; display: flex; justify-content: space-between; margin-top: 20px; }
        .total-label { font-weight: 800; color: #1e293b; }
        .total-value { font-weight: 800; color: #10b981; font-size: 1.2rem; }
        .footer-note { text-align: center; color: #94a3b8; font-size: 0.75rem; margin-top: 30px; }
        .no-print { text-align: center; margin-top: 30px; }
        .btn-print { background: #1e293b; color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; font-weight: 600; }
        @media print { .no-print { display: none; } body { background: white; padding: 0; } .slip-card { box-shadow: none; border: 1px solid #000; } }
    </style>
</head>
<body>

<div class="slip-card">
    <div class="header">
        <h2>Smart Budget Systems</h2>
        <p style="color: #64748b; font-size: 0.8rem;">Employee Monthly Pay Slip</p>
    </div>

    <div class="info-row">
        <span class="label">Employee Name:</span>
        <span class="value"><?php echo $data['full_name']; ?></span>
    </div>
    <div class="info-row">
        <span class="label">Department:</span>
        <span class="value"><?php echo $data['department']; ?></span>
    </div>
    <div class="info-row">
        <span class="label">Pay Date:</span>
        <span class="value"><?php echo date('F d, Y', strtotime($data['pay_date'])); ?></span>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span class="label">Gross Salary:</span>
        <span class="value">LKR <?php echo number_format($data['gross_salary'], 2); ?></span>
    </div>
    <div class="info-row" style="color: #ef4444;">
        <span class="label">EPF Deduction (8%):</span>
        <span class="value">- <?php echo number_format($data['epf_employee'], 2); ?></span>
    </div>
    
    <div class="divider"></div>

    <div class="info-row" style="font-size: 0.85rem; color: #64748b;">
        <span>Employer EPF (12%):</span>
        <span><?php echo number_format($data['epf_employer'], 2); ?></span>
    </div>
    <div class="info-row" style="font-size: 0.85rem; color: #64748b;">
        <span>Employer ETF (3%):</span>
        <span><?php echo number_format($data['etf_employer'], 2); ?></span>
    </div>

    <div class="total-row">
        <span class="total-label">NET SALARY (TAKE HOME):</span>
        <span class="total-value">LKR <?php echo number_format($data['net_salary'], 2); ?></span>
    </div>

    <div class="footer-note">
        <p>This is a computer-generated salary slip and does not require a physical signature.</p>
        <p>© 2026 Smart Budget Finance System</p>
    </div>
</div>

<div class="no-print">
    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Salary Slip</button>
</div>

</body>
</html>