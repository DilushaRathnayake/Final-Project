<?php
// 1. Fixed Session Handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// 2. Access Control - Ensure user is logged in
if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 3. DATA AGGREGATION ---

// Total Income
$total_inc_res = mysqli_query($conn, "SELECT SUM(amount) as total FROM income_records WHERE user_id = '$user_id'");
$total_income = mysqli_fetch_assoc($total_inc_res)['total'] ?? 0;

// Total Expenses
$total_exp_res = mysqli_query($conn, "SELECT SUM(amount) as total FROM expense_records WHERE user_id = '$user_id'");
$total_expense = mysqli_fetch_assoc($total_exp_res)['total'] ?? 0;

// Pending Reminders
$rem_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM reminders WHERE user_id = '$user_id' AND status = 'Pending'");
$pending_reminders = mysqli_fetch_assoc($rem_res)['count'] ?? 0;

// Active Seettu
$seettu_res = mysqli_query($conn, "SELECT COUNT(*) as count FROM factory_seettu WHERE user_id = '$user_id' AND status = 'Active'");
$active_seettu = mysqli_fetch_assoc($seettu_res)['count'] ?? 0;

// Total Loans
$loans_q = mysqli_query($conn, "SELECT SUM(total_amount) as total FROM loans WHERE user_id = '$user_id'");
$total_loans = mysqli_fetch_assoc($loans_q)['total'] ?? 0;

// --- 4. TREND DATA (Last 6 Months) ---
$months = []; $incomes = []; $expenses = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[] = date('M', strtotime("-$i months"));
    
    $mi = mysqli_query($conn, "SELECT SUM(amount) as t FROM income_records WHERE user_id = '$user_id' AND DATE_FORMAT(date, '%Y-%m') = '$m'");
    $incomes[] = mysqli_fetch_assoc($mi)['t'] ?? 0;
    
    $me = mysqli_query($conn, "SELECT SUM(amount) as t FROM expense_records WHERE user_id = '$user_id' AND DATE_FORMAT(date, '%Y-%m') = '$m'");
    $expenses[] = mysqli_fetch_assoc($me)['t'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Analytics | Smart Budget</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        
        :root {
            --bg: #f1f5f9; --sidebar-bg: #1e293b; --card-bg: #ffffff;
            --primary: #6366f1; --success: #10b981; --danger: #ef4444;
            --warning: #f59e0b; --text-main: #0f172a; --text-sub: #64748b; --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; min-height: 100vh; }

        /* SIDEBAR (Match Dashboard) */
        .sidebar {
            width: 260px; background: var(--sidebar-bg); height: 100vh; position: fixed;
            padding: 30px 20px; display: flex; flex-direction: column; color: white;
        }
        .sidebar-brand { font-size: 1.4rem; font-weight: 800; margin-bottom: 40px; text-align: center; color: #fff; letter-spacing: 1px; }
        
        .nav-links { flex: 1; overflow-y: auto; }
        .nav-links::-webkit-scrollbar { width: 4px; }
        .nav-links::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }

        .nav-group-label { font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; margin: 25px 0 10px 10px; letter-spacing: 1px; }

        .nav-item {
            display: flex; align-items: center; padding: 12px 15px; color: #cbd5e1; text-decoration: none;
            border-radius: 10px; margin-bottom: 4px; transition: 0.3s; font-size: 0.9rem;
        }
        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: var(--primary); }

        .logout-link { padding: 15px; color: #fca5a5; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 10px; }

        /* MAIN CONTENT */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: var(--card-bg); padding: 25px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .kpi-card small { color: var(--text-sub); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
        .kpi-card .value { font-size: 1.3rem; font-weight: 800; margin-top: 8px; }

        .report-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px; }
        .card { background: var(--card-bg); border: 1px solid var(--border); padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 15px; background: #f8fafc; color: var(--text-sub); font-size: 0.75rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
        .btn-action { background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; transition: 0.2s; }
        .btn-action:hover { opacity: 0.9; transform: translateY(-1px); }
        
        @media print { .sidebar, .btn-action { display: none; } .main-content { margin: 0; width: 100%; } }
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
        <a href="health_score.php" class="nav-item">
            <i class="fas fa-heartbeat"></i> <span>Health Score</span>
        </a>
        <a href="seettu.php" class="nav-item">
            <i class="fas fa-users-cog"></i> <span>Seettu Groups</span>
        </a> 
        <a href="reminders.php" class="nav-item">
            <i class="fas fa-bell"></i> <span>Reminders</span>
        </a>
        <a href="reports.php" class="nav-item active">
            <i class="fas fa-chart-pie"></i> <span>Reports & Charts</span>
        </a>
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>

<main class="main-content">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
        <div>
            <h2 style="font-weight: 700;">Financial Master Report</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Comprehensive data analysis for <?php echo htmlspecialchars($full_name); ?></p>
        </div>
        <button class="btn-action" onclick="window.print()"><i class="fas fa-file-pdf"></i> Export to PDF</button>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <small>Net Balance</small>
            <div class="value">LKR <?php echo number_format($total_income - $total_expense); ?></div>
        </div>
        <div class="kpi-card">
            <small>Pending Bills</small>
            <div class="value" style="color: var(--warning);"><?php echo $pending_reminders; ?> Tasks</div>
        </div>
        <div class="kpi-card">
            <small>Active Seettu</small>
            <div class="value" style="color: var(--success);"><?php echo $active_seettu; ?> Groups</div>
        </div>
        <div class="kpi-card">
            <small>Outstanding Loans</small>
            <div class="value" style="color: var(--danger);">LKR <?php echo number_format($total_loans); ?></div>
        </div>
    </div>

    <div class="report-layout">
        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 20px;"><i class="fas fa-chart-line" style="color: var(--primary);"></i> 6-Month Trend</h3>
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="masterChart"></canvas>
            </div>
        </div>
        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 20px;"><i class="fas fa-info-circle" style="color: var(--primary);"></i> Insight</h3>
            <p style="font-size: 0.9rem; color: var(--text-sub); line-height: 1.6;">
                This automated report consolidates all modules of the <strong>Smart Budget</strong> system, including income, expenses, active seettu groups, and pending loan obligations. Data is synced in real-time.
            </p>
        </div>
    </div>

    <div class="card" style="margin-top: 10px;">
        <h3 style="font-size: 1rem; margin-bottom: 15px;"><i class="fas fa-history" style="color: var(--primary);"></i> Performance History</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Inflow (LKR)</th>
                    <th>Outflow (LKR)</th>
                    <th>Net Savings</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($months as $idx => $m_name): 
                    $diff = $incomes[$idx] - $expenses[$idx];
                ?>
                <tr>
                    <td><strong><?php echo $m_name; ?></strong></td>
                    <td><?php echo number_format($incomes[$idx]); ?></td>
                    <td><?php echo number_format($expenses[$idx]); ?></td>
                    <td style="font-weight:700; color: <?php echo $diff >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <?php echo number_format($diff); ?>
                    </td>
                    <td>
                        <span class="badge" style="background: <?php echo $diff >= 0 ? '#ecfdf5' : '#fef2f2'; ?>; color: <?php echo $diff >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                            <?php echo $diff >= 0 ? 'STABLE' : 'CRITICAL'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('masterChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Income',
                data: <?php echo json_encode($incomes); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }, {
                label: 'Expenses',
                data: <?php echo json_encode($expenses); ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Prevents infinite height expansion
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { padding: 20, font: { family: 'Inter', size: 12 } }
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: '#f1f5f9' },
                    ticks: { font: { family: 'Inter' } }
                },
                x: { 
                    grid: { display: false },
                    ticks: { font: { family: 'Inter' } }
                }
            }
        }
    });
});
</script>

</body>
</html>