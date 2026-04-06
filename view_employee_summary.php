<?php
session_start();
require_once 'config.php';

// 1. ආරක්ෂක පියවර: Supervisor පරීක්ෂාව
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: supervisor_dashboard.php");
    exit();
}

$target_user_id = mysqli_real_escape_string($conn, $_GET['id']);
$supervisor_dept = $_SESSION['department'];

// 2. සේවකයාගේ විස්තර ලබා ගැනීම
$user_query = "SELECT * FROM users WHERE user_id = '$target_user_id' AND department = '$supervisor_dept' AND role = 'user'";
$user_res = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_res);

if (!$user_data) {
    die("<div style='padding:50px; text-align:center; font-family:sans-serif;'>
            <h2>Access Denied!</h2>
            <p>You can only view reports of employees in your department.</p>
            <a href='supervisor_dashboard.php'>Go Back</a>
         </div>");
}

// 3. මූල්‍ය දත්ත ලබා ගැනීම (Calculations)
// Total Income
$inc_res = mysqli_query($conn, "SELECT SUM(amount) as total FROM income_records WHERE user_id = '$target_user_id'");
$total_income = mysqli_fetch_assoc($inc_res)['total'] ?: 0;

// Total Expenses
$exp_res = mysqli_query($conn, "SELECT SUM(amount) as total FROM expense_records WHERE user_id = '$target_user_id'");
$total_expenses = mysqli_fetch_assoc($exp_res)['total'] ?: 0;

// Total Savings
$sav_res = mysqli_query($conn, "SELECT SUM(amount) as total FROM savings WHERE user_id = '$target_user_id'");
$total_savings = mysqli_fetch_assoc($sav_res)['total'] ?: 0;

// Seettu Participation
$seettu_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM seettu_members WHERE user_id = '$target_user_id'");
$seettu_count = mysqli_fetch_assoc($seettu_res)['count'] ?: 0;

// --- Division by Zero Error එක වැළැක්වීමේ Logic එක ---
$savings_ratio = ($total_income > 0) ? ($total_savings / $total_income) * 100 : 0;
$expense_ratio = ($total_income > 0) ? ($total_expenses / $total_income) * 100 : 0;
$disposable_income = $total_income - ($total_expenses + $total_savings);

// Financial Health Score (100 සිට වියදම් ප්‍රතිශතය අඩු කිරීමෙන්)
$health_score = ($total_income > 0) ? round(100 - $expense_ratio) : 0;
if ($health_score < 0) $health_score = 0; // ලකුණු සෘණ අගයක් වීම වැළැක්වීම
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Management Report | <?php echo htmlspecialchars($user_data['full_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #0f172a; --accent: #4f46e5; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --bg: #f8fafc; }
        * { box-sizing: border-box; font-family: 'Inter', -apple-system, sans-serif; }
        body { background: var(--bg); color: var(--primary); margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        /* UI Buttons */
        .header-actions { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; border: none; transition: 0.3s; }
        .btn-back { background: white; color: #64748b; border: 1px solid #e2e8f0; }
        .btn-print { background: var(--primary); color: white; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        /* Report Layout */
        .report-paper { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; }
        .report-header { background: var(--primary); color: white; padding: 40px; display: flex; justify-content: space-between; align-items: center; }
        
        /* Analytics Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; padding: 30px; background: #fff; }
        .stat-box { padding: 20px; border-radius: 12px; background: #f8fafc; border: 1px solid #f1f5f9; }
        .stat-box small { color: #64748b; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .stat-box h2 { margin: 8px 0 0; font-size: 1.25rem; font-weight: 800; }

        /* Progress Area */
        .progress-section { padding: 0 30px 30px; }
        .progress-meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: 600; font-size: 0.9rem; }
        .progress-bar-bg { height: 12px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--success); transition: 1s ease-in-out; }

        /* Insights Box */
        .insight-card { margin: 30px; padding: 25px; border-radius: 15px; background: #eff6ff; border-left: 6px solid var(--accent); }
        .insight-card h4 { margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px; color: #1e40af; }

        @media print {
            body { padding: 0; background: white; }
            .header-actions { display: none; }
            .report-paper { box-shadow: none; border: none; }
            .report-header { background: #000 !important; color: #fff !important; }
            .insight-card { border: 1px solid #ddd !important; border-left: 6px solid #000 !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-actions">
        <a href="supervisor_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to List</a>
        <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Generate PDF Report</button>
    </div>

    <div class="report-paper">
        <header class="report-header">
            <div>
                <span style="background: rgba(255,255,255,0.1); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fas fa-microchip"></i> SMART BUDGET SYSTEM v2.0
                </span>
                <h1 style="margin: 15px 0 5px 0; font-size: 2rem;"><?php echo htmlspecialchars($user_data['full_name']); ?></h1>
                <p style="margin:0; opacity: 0.8;">Dept: <?php echo htmlspecialchars($user_data['department']); ?> | Employee ID: #<?php echo $target_user_id; ?></p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 0.85rem; opacity: 0.7; font-weight: 600;">FINANCIAL HEALTH SCORE</div>
                <div style="font-size: 3rem; font-weight: 900; color: #10b981; line-height: 1;"><?php echo $health_score; ?>%</div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-box">
                <small>Monthly Income</small>
                <h2 style="color: var(--primary);">Rs. <?php echo number_format($total_income, 2); ?></h2>
            </div>
            <div class="stat-box">
                <small>Total Expenses</small>
                <h2 style="color: var(--danger);">Rs. <?php echo number_format($total_expenses, 2); ?></h2>
            </div>
            <div class="stat-box">
                <small>Total Savings</small>
                <h2 style="color: var(--success);">Rs. <?php echo number_format($total_savings, 2); ?></h2>
            </div>
            <div class="stat-box">
                <small>Disposable Balance</small>
                <h2 style="color: var(--accent);">Rs. <?php echo number_format($disposable_income, 2); ?></h2>
            </div>
        </div>

        <div class="progress-section">
            <div class="progress-meta">
                <span>Savings Progress (Achievement)</span>
                <span><?php echo round($savings_ratio, 1); ?>%</span>
            </div>
            <div class="progress-bar-bg">
                <div class="progress-fill" style="width: <?php echo ($savings_ratio > 100) ? 100 : $savings_ratio; ?>%;"></div>
            </div>
            
            <div style="display: flex; gap: 40px; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <div>
                    <small style="color:#64748b; font-weight:700;">NIC NUMBER</small>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($user_data['nic']); ?></div>
                </div>
                <div>
                    <small style="color:#64748b; font-weight:700;">PHONE</small>
                    <div style="font-weight:600;"><?php echo htmlspecialchars($user_data['phone'] ?: 'N/A'); ?></div>
                </div>
                <div>
                    <small style="color:#64748b; font-weight:700;">SEETTU GROUPS</small>
                    <div style="font-weight:600;"><?php echo $seettu_count; ?> Active</div>
                </div>
            </div>
        </div>

        <div class="insight-card">
            <h4><i class="fas fa-robot"></i> Smart Management Insights</h4>
            <p style="margin:0; font-size: 0.95rem; color: #1e40af;">
                <?php 
                if ($total_income == 0) {
                    echo "No financial data available for this period. Please update income records.";
                } elseif ($savings_ratio < 10) {
                    echo "<strong>Action Required:</strong> Savings are below the 10% safety threshold. Recommend a budget review session with the employee.";
                } elseif ($savings_ratio >= 20) {
                    echo "<strong>Excellent Profile:</strong> High savings consistency detected. Eligible for the 'Financial Star' recognition program.";
                } else {
                    echo "<strong>Stable:</strong> Financial habits are within the standard factory parameters for the " . htmlspecialchars($user_data['department']) . " sector.";
                }
                ?>
            </p>
        </div>

        <footer style="padding: 20px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; font-size: 0.8rem; color: #94a3b8;">
            <span>Generated on: <?php echo date('F j, Y, g:i a'); ?></span>
            <span style="font-style: italic;">Authorized Signature: _______________________</span>
        </footer>
    </div>
</div>

</body>
</html>