<?php
session_start();
require_once 'config.php';

// Security: Ensure only logged-in 'user' role can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// --- 1. LIVE DATA FETCHING (Current Month) ---
$income_query = "SELECT SUM(amount) as total_inc FROM income_records 
                 WHERE user_id = '$user_id' 
                 AND MONTH(date) = MONTH(CURRENT_DATE()) 
                 AND YEAR(date) = YEAR(CURRENT_DATE())";
$inc_res = mysqli_query($conn, $income_query);
$live_income = mysqli_fetch_assoc($inc_res)['total_inc'] ?? 0;

$expense_query = "SELECT SUM(amount) as total_exp FROM expense_records 
                  WHERE user_id = '$user_id' 
                  AND MONTH(date) = MONTH(CURRENT_DATE()) 
                  AND YEAR(date) = YEAR(CURRENT_DATE())";
$exp_res = mysqli_query($conn, $expense_query);
$live_expense = mysqli_fetch_assoc($exp_res)['total_exp'] ?? 0;

$live_balance = $live_income - $live_expense;

// Budget Logic
$budget_limit = 50000; 
$budget_percent = ($live_income > 0) ? round(($live_expense / $live_income) * 100) : 0;

// --- 2. PAYDAY PLAN & LOAN FETCHING ---
$plan_query = "SELECT * FROM payday_plans WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 1";
$plan_res = mysqli_query($conn, $plan_query);
$latest_plan = mysqli_fetch_assoc($plan_res);

$active_loans_q = mysqli_query($conn, "SELECT COUNT(*) as count FROM loans WHERE user_id = '$user_id' AND status = 'Active'");
$loan_count = mysqli_fetch_assoc($active_loans_q)['count'] ?? 0;

// --- 3. HEALTH SCORE LOGIC ---
$health_score = 0;
if ($live_income > 0) {
    $savings_rate = ($live_balance / $live_income) * 100;
    if ($savings_rate >= 20) $health_score = 90;
    elseif ($savings_rate >= 10) $health_score = 70;
    elseif ($savings_rate > 0) $health_score = 50;
    else $health_score = 30;
}
$score_color = ($health_score >= 75) ? '#10b981' : (($health_score >= 50) ? '#f59e0b' : '#ef4444');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Dashboard</title>
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
        /* Loader Wrapper - මුළු screen එකම වැහෙන සේ */
#loader-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #ffffff; /* පසුබිම සුදු පාට */
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* හැමදේටම උඩින් තියෙන්න */
    transition: opacity 0.5s ease;
}

/* Spinner එකේ හැඩය */
.loader {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--primary); /* ඔබේ primary color එක (Indigo) */
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

#loader-wrapper p {
    margin-top: 15px;
    font-size: 0.9rem;
    color: var(--text-sub);
    font-weight: 600;
}

/* කැරකෙන Animation එක */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Loader එක අයින් කරන විට භාවිතා වන class එක */
.fade-out {
    opacity: 0;
    pointer-events: none;
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
        }

        .sidebar-brand {
            font-size: 1.4rem;
            font-weight: 800;
            margin-bottom: 40px;
            text-align: center;
            letter-spacing: 1px;
            color: #fff;
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

        .logout-link {
            padding: 15px;
            color: #fca5a5;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .live-status {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .stat-card h4 {
            font-size: 0.8rem;
            color: var(--text-sub);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .amount { font-size: 1.6rem; font-weight: 800; }

        .features-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr 1fr;
            gap: 25px;
        }

        .action-btn {
            background: #f8fafc;
            display: block;
            padding: 15px;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 10px;
            border: 1px solid var(--border);
            transition: 0.2s;
        }

        .action-btn:hover { background: #f1f5f9; transform: translateY(-2px); }
        .action-btn h5 { color: var(--text-main); margin-bottom: 4px; }

        .score-circle {
            width: 110px;
            height: 110px;
            border: 8px solid;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
        }

        /* Scrollbar fix for sidebar */
        .nav-links::-webkit-scrollbar { width: 4px; }
        .nav-links::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>

<div id="loader-wrapper">
    <div class="loader"></div>
    <p>Loading Smart Budget...</p>
</div>

<div class="sidebar">
    <div class="sidebar-brand">SMART BUDGET</div>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-item active">
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
        <a href="health_score.php" class="nav-item">
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
        <a href="profile.php" class="nav-item">
            <i class="fas fa-chart-pie"></i> <span>My Profile</span>
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
            <h2 style="font-weight: 700;">Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Nuwara Eliya Regional Model Active</p>
        </div>
        <div style="color: var(--success); font-weight: 600; font-size: 0.85rem;">
            <i class="fas fa-sync-alt fa-spin"></i> Live System Sync
        </div>
    </div>

    <div class="live-status">
        <div class="stat-card">
            <h4>Current Month Income</h4>
            <div class="amount" style="color: var(--success);">LKR <?php echo number_format($live_income); ?></div>
        </div>
        <div class="stat-card">
            <h4>Monthly Expenses</h4>
            <div class="amount" style="color: var(--danger);">LKR <?php echo number_format($live_expense); ?></div>
        </div>
        <div class="stat-card">
            <h4>Remaining Balance</h4>
            <div class="amount" style="color: var(--primary);">LKR <?php echo number_format($live_balance); ?></div>
        </div>
    </div>

    <div class="features-grid">
        <div class="stat-card">
            <h3 style="font-size: 1rem;"><i class="fas fa-bolt" style="color: var(--warning);"></i> Quick Actions</h3>
            <div style="margin-top: 20px;">
                <a href="income.php" class="action-btn">
                    <h5>Add New Income</h5>
                    <p style="color: var(--text-sub); font-size: 0.75rem;">Record salary, OT, or bonuses.</p>
                </a>
                <a href="expenses.php" class="action-btn">
                    <h5>Log Expense</h5>
                    <p style="color: var(--text-sub); font-size: 0.75rem;">Track bills, food, or transport.</p>
                </a>
            </div>
        </div>

        <div class="stat-card" style="text-align: center;">
            <h4 style="font-size: 0.8rem;">Financial Health</h4>
            <div class="score-circle" style="border-color: <?php echo $score_color; ?>; color: <?php echo $score_color; ?>;">
                <span style="font-size: 1.8rem; font-weight: 800;"><?php echo $health_score; ?></span>
            </div>
            <p style="font-weight: 800; font-size: 0.8rem; color: <?php echo $score_color; ?>;">
                <?php echo ($health_score >= 75) ? "EXCELLENT" : (($health_score >= 50) ? "STABLE" : "CRITICAL"); ?>
            </p>
        </div>

        <div class="stat-card">
            <h3 style="font-size: 1rem;"><i class="fas fa-calendar-alt" style="color: var(--primary);"></i> PayDay Plan</h3>
            <?php if($latest_plan): ?>
                <div style="margin-top: 15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <small style="color:var(--text-sub);">Savings Target:</small>
                        <small style="color:var(--success); font-weight:600;">LKR <?php echo number_format($latest_plan['savings_target']); ?></small>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                        <small style="color:var(--text-sub);">Fixed Debts:</small>
                        <small style="color:var(--danger); font-weight:600;">LKR <?php echo number_format($latest_plan['fixed_debts']); ?></small>
                    </div>
                    <a href="payday.php" style="color:var(--primary); font-size:0.8rem; font-weight:600; text-decoration:none;">View Full Plan <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php else: ?>
                <p style="color:var(--text-sub); font-size:0.8rem; margin-top:20px;">No plan set for this month.</p>
                <a href="payday.php" class="action-btn" style="margin-top:10px; text-align:center; background: var(--primary); color: white;">Create Plan</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card" style="margin-top: 25px;">
        <h3 style="font-size: 1rem;"><i class="fas fa-chart-line" style="color: var(--primary);"></i> Weekly Expense Analytics</h3>
        <canvas id="liveChart" height="80"></canvas>
    </div>
</div>

<script>

    window.addEventListener("load", function() {
    const loader = document.getElementById("loader-wrapper");
    
    // තත්පර 0.5ක පමාවකින් පසු loader එක අයින් කරන්න (Smooth වෙන්න)
    setTimeout(() => {
        loader.classList.add("fade-out");
        // සම්පූර්ණයෙන්ම hide කරන්න
        setTimeout(() => {
            loader.style.display = "none";
        }, 500);
    }, 300); 
});
    const ctx = document.getElementById('liveChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Current'],
            datasets: [{
                label: 'Expenses (LKR)',
                data: [12000, 15000, 9000, <?php echo (int)$live_expense; ?>],
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: '#e2e8f0' }, ticks: { color: '#64748b' } },
                x: { grid: { display: false }, ticks: { color: '#64748b' } }
            }
        }
    });
</script>
</body>
</html>