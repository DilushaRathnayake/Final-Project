<?php
session_start();
require_once 'config.php';

// Security: Ensure only logged-in users can access
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? 'User';

// --- 1. FETCH CALCULATED DATA ---
$income_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM income_records WHERE user_id = '$user_id'");
$salary = mysqli_fetch_assoc($income_q)['total'] ?? 0;

$debt_q = mysqli_query($conn, "SELECT SUM(monthly_repayment) as emi FROM loans WHERE user_id = '$user_id' AND status = 'Active'");
$fixed_debt = mysqli_fetch_assoc($debt_q)['emi'] ?? 0;

// Logic for 50/30/20 Rule
$needs = $salary * 0.50;
$wants = $salary * 0.30;
$savings = $salary * 0.20;
$remaining = $salary - $fixed_debt;

$current_month = date('F Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | PayDay Planner</title>
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

        /* SIDEBAR (Dashboard එකට සමානයි) */
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

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .chart-container { 
            position: relative; 
            height: 250px; 
            width: 100%; 
            display: flex; 
            justify-content: center; 
            margin-top: 15px;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: var(--text-sub); font-size: 0.75rem; text-transform: uppercase; padding: 12px; border-bottom: 1px solid var(--border); }
        td { padding: 15px 12px; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; }

        .alert-box { 
            background: #fff1f2; 
            border: 1px solid #fecdd3; 
            padding: 15px; 
            border-radius: 15px; 
            color: var(--danger); 
            font-size: 0.85rem; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            margin-top: 20px; 
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .export-btn {
            background: white;
            border: 1px solid var(--border);
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-main);
            transition: 0.2s;
        }
        .export-btn:hover { background: #f8fafc; }

        @media print { .sidebar { display: none; } .main-content { margin-left: 0; width: 100%; } }
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
        <a href="payday.php" class="nav-item active">
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
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>


    <a href="logout.php" class="logout-link">
        <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h2 style="font-weight: 700;">Strategic PayDay Planner</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Month: <b><?php echo $current_month; ?></b> | Nuwara Eliya Region</p>
        </div>
        <button onclick="window.print()" class="export-btn">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
    </div>

    <div class="grid-3">
        <div class="card">
            <h3 style="font-size: 1rem; text-align: center;">50/30/20 Structure</h3>
            <div class="chart-container">
                <canvas id="budgetChart"></canvas>
            </div>
            <div style="margin-top: 20px; font-size: 0.8rem; color: var(--text-sub);">
                <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                    <span><i class="fas fa-circle" style="color:#6366f1;"></i> Needs (50%)</span>
                    <b>LKR <?php echo number_format($needs); ?></b>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 5px;">
                    <span><i class="fas fa-circle" style="color:#f59e0b;"></i> Wants (30%)</span>
                    <b>LKR <?php echo number_format($wants); ?></b>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span><i class="fas fa-circle" style="color:#10b981;"></i> Savings (20%)</span>
                    <b>LKR <?php echo number_format($savings); ?></b>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="font-size: 1rem;"><i class="fas fa-list-check" style="color: var(--primary);"></i> Cash Flow Priorities</h3>
            <table>
                <thead>
                    <tr>
                        <th>Expense Category</th>
                        <th style="text-align: right;">Planned Amount</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-university" style="color: var(--primary);"></i> Bank Loan/EMI</td>
                        <td style="text-align: right; font-weight: 700;">LKR <?php echo number_format($fixed_debt); ?></td>
                        <td style="text-align: center;">
                            <span class="status-badge" style="background: #fee2e2; color: #ef4444;">MANDATORY</span>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-home" style="color: var(--warning);"></i> Boarding & Food</td>
                        <td style="text-align: right; font-weight: 700;">LKR 5,500</td>
                        <td style="text-align: center;">
                            <span class="status-badge" style="background: #fef3c7; color: #d97706;">DUE SOON</span>
                        </td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-piggy-bank" style="color: var(--success);"></i> Monthly Savings</td>
                        <td style="text-align: right; font-weight: 700;">LKR <?php echo number_format($savings); ?></td>
                        <td style="text-align: center;">
                            <span class="status-badge" style="background: #dcfce7; color: #16a34a;">GOAL</span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if($fixed_debt > ($salary * 0.40)): ?>
            <div class="alert-box">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <b>High Debt Alert:</b> Loans consume over 40% of income. 
                    <br><small>We suggest reducing 'Wants' to balance your budget.</small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3 style="font-size: 1rem; margin-bottom: 10px;"><i class="fas fa-rocket" style="color: var(--primary);"></i> Financial Growth Projection</h3>
        <p style="color: var(--text-sub); font-size: 0.9rem;">
            By saving <b>LKR <?php echo number_format($savings); ?></b> monthly, your 1-year total will be 
            <span style="color: var(--success); font-weight: 700;">LKR <?php echo number_format($savings * 12); ?></span>.
        </p>
        <div style="height: 12px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin-top: 20px; border: 1px solid var(--border);">
            <div style="width: 45%; height: 100%; background: linear-gradient(90deg, var(--primary), var(--success)); border-radius: 10px;"></div>
        </div>
        <small style="color: var(--text-sub); display: block; margin-top: 8px;">Progress towards your annual safety net goal</small>
    </div>
</div>

<script>
    // --- BUDGET DONUT CHART ---
    const ctx = document.getElementById('budgetChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Needs', 'Wants', 'Savings'],
            datasets: [{
                data: [<?php echo $needs; ?>, <?php echo $wants; ?>, <?php echo $savings; ?>],
                backgroundColor: ['#6366f1', '#f59e0b', '#10b981'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 15
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            cutout: '75%',
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>

</body>
</html>