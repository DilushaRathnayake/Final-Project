<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 1. DATA FETCHING ---
$inc_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM income_records WHERE user_id = '$user_id'");
$income = mysqli_fetch_assoc($inc_q)['total'] ?? 0;

$exp_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM expense_records WHERE user_id = '$user_id'");
$expenses = mysqli_fetch_assoc($exp_q)['total'] ?? 0;

$debt_q = mysqli_query($conn, "SELECT SUM(monthly_repayment) as emi FROM loans WHERE user_id = '$user_id' AND status = 'Active'");
$debt = mysqli_fetch_assoc($debt_q)['emi'] ?? 0;

// --- 2. CALCULATIONS ---
$score = 0;
$savings_rate = 0;
$debt_percent = 0;
$status_color = "#64748b"; 
$status_msg = "No Data Available";

if ($income > 0) {
    $savings_rate = (($income - $expenses - $debt) / $income) * 100;
    $debt_percent = ($debt / $income) * 100;
    $disposable_income = $income - $expenses - $debt;

    $score = 40; 
    if ($savings_rate > 20) $score += 30;
    elseif ($savings_rate > 10) $score += 15;

    if ($debt_percent < 30) $score += 30;
    elseif ($debt_percent > 50) $score -= 20;

    if ($score > 100) $score = 100;
    if ($score < 0) $score = 0;

    if ($score >= 75) { $status_msg = "Excellent Financial Health"; $status_color = "#10b981"; }
    elseif ($score >= 50) { $status_msg = "Stable - Improving"; $status_color = "#f59e0b"; }
    else { $status_msg = "Critical Financial State"; $status_color = "#ef4444"; }
} else {
    $disposable_income = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Health Advisor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            --bg: #f1f5f9;
            --sidebar-bg: #1e293b;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --primary: #6366f1;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            background: var(--bg); 
            color: var(--text-main); 
            font-family: 'Inter', sans-serif; 
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 1000;
        }

        .sidebar-brand {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 40px;
            text-align: center;
            letter-spacing: 1px;
        }

        .nav-links { flex: 1; overflow-y: auto; }
        .nav-group-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 25px 0 10px 10px;
            letter-spacing: 1px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 4px;
            transition: 0.3s;
            font-size: 0.9rem;
        }

        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .nav-item.active { background: var(--primary); }

        .logout-link {
            padding: 15px;
            color: #fca5a5;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 40px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .analysis-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        
        .box { 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 18px; 
            border-left: 5px solid var(--primary);
            border: 1px solid var(--border);
            border-left-width: 5px;
        }

        .gauge-container {
            width: 250px; 
            margin: auto; 
            position: relative;
        }

        .score-text {
            position: absolute; 
            top: 70%; 
            left: 50%; 
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .progress-bar-bg {
            height: 12px; 
            background: #f1f5f9; 
            border-radius: 10px; 
            overflow: hidden;
            margin: 15px 0;
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">SMART BUDGET</div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i> <span>Dashboard</span>
        </a>
        
        <div class="nav-group-label">Money Management</div>
        <a href="income.php" class="nav-item">
            <i class="fas fa-hand-holding-usd"></i> <span>Live Income</span>
        </a>
        <a href="expenses.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i> <span>Live Expenses</span>
        </a>
        <a href="budget.php" class="nav-item">
            <i class="fas fa-piggy-bank"></i> <span>Budget Planning</span>
        </a>

        <div class="nav-group-label">Factory Specific</div>
        <a href="savings.php" class="nav-item">
            <i class="fas fa-coins"></i> <span>Savings & Goals</span>
        </a>
        <a href="loans.php" class="nav-item">
            <i class="fas fa-shield-virus"></i> <span>Loan Guard</span>
        </a>

        <div class="nav-group-label">Advanced Tools</div>
        <a href="payday.php" class="nav-item">
            <i class="fas fa-calendar-check"></i> <span>Pay Day Planner</span>
        </a>
        <a href="health_score.php" class="nav-item active">
            <i class="fas fa-heartbeat"></i> <span>Health Score</span>
        </a>
        <a href="seettu.php" class="nav-item">
            <i class="fas fa-users-cog"></i> <span>Seettu Groups</span>
        </a> 
        <a href="reminders.php" class="nav-item">
            <i class="fas fa-bell"></i> <span>Reminders</span>
        </a>
        <a href="reports.php" class="nav-item">
            <i class="fas fa-chart-pie"></i> <span>Reports & Charts</span>
        </a>
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>


<div class="main-content">
    <div class="header">
        <div>
            <h2 style="font-weight: 700;">Financial Health Advisor</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Personalized Analysis for <?php echo htmlspecialchars($full_name); ?></p>
        </div>
        <div style="color: var(--primary); font-weight: 600; font-size: 0.85rem;">
            <i class="fas fa-shield-alt"></i> Secure Data Analysis
        </div>
    </div>

    <div class="card" style="text-align: center;">
        <div class="gauge-container">
            <canvas id="healthGauge"></canvas>
            <div class="score-text">
                <span style="font-size: 3rem; font-weight: 800; color: <?php echo $status_color; ?>;"><?php echo round($score); ?></span>
                <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Score</p>
            </div>
        </div>
        <h2 style="color: <?php echo $status_color; ?>; margin-top: 10px; font-weight: 700;"><?php echo $status_msg; ?></h2>
    </div>

    <div class="card">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle" style="color: var(--danger);"></i> Debt-to-Income Analysis
        </h3>
        
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 600; color: var(--text-sub);">Monthly Debt Burden</span>
            <span style="font-weight: 800; font-size: 1.2rem; color: <?php echo ($debt_percent > 40) ? 'var(--danger)' : 'var(--success)'; ?>;">
                <?php echo round($debt_percent); ?>%
            </span>
        </div>

        <div class="progress-bar-bg">
            <div style="width: <?php echo $debt_percent; ?>%; height: 100%; background: <?php echo ($debt_percent > 40) ? 'var(--danger)' : 'var(--success)'; ?>; border-radius: 10px;"></div>
        </div>

        <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin-top: 10px;">
            <p style="font-size: 0.9rem; line-height: 1.5; color: var(--text-main);">
                <?php 
                    if($debt_percent > 40) echo "⚠️ <b>Warning:</b> Your debt level is in the <b>Danger Zone</b>. This significantly reduces your ability to save and invest.";
                    else echo "✅ <b>Good Job:</b> Your debt level is manageable. Maintaining it below 30% ensures long-term financial freedom.";
                ?>
            </p>
        </div>
    </div>

    <div class="analysis-grid">
        <div class="box" style="border-left-color: var(--success);">
            <small style="color: var(--text-sub); text-transform: uppercase; font-weight: 700; font-size: 0.7rem; letter-spacing: 0.5px;">Net Cash Remaining</small>
            <h3 style="margin: 10px 0; font-size: 1.5rem; font-weight: 800; color: var(--success);">LKR <?php echo number_format($disposable_income); ?></h3>
            <p style="font-size: 0.75rem; color: var(--text-sub);">Disposable income available for lifestyle and emergency savings.</p>
        </div>
        
        <div class="box" style="border-left-color: var(--warning);">
            <small style="color: var(--text-sub); text-transform: uppercase; font-weight: 700; font-size: 0.7rem; letter-spacing: 0.5px;">Emergency Resilience</small>
            <h3 style="margin: 10px 0; font-size: 1.5rem; font-weight: 800; color: var(--warning);">
                <?php echo ($disposable_income > 5000) ? "Stable" : "Critical"; ?>
            </h3>
            <p style="font-size: 0.75rem; color: var(--text-sub);">Current ability to handle unexpected financial shocks this month.</p>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('healthGauge').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $score; ?>, <?php echo 100 - $score; ?>],
                backgroundColor: ['<?php echo $status_color; ?>', '#f1f5f9'],
                borderWidth: 0,
                circumference: 180,
                rotation: 270,
                borderRadius: 10
            }]
        },
        options: { 
            cutout: '85%', 
            plugins: { legend: { display: false } },
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

</body>
</html>