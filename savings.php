<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];
$current_month = date('Y-m');

// --- 1. DELETE LOGIC ---
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $delete_query = "DELETE FROM savings WHERE id = '$del_id' AND user_id = '$user_id'";
    if(mysqli_query($conn, $delete_query)) {
        header("Location: savings.php?msg=deleted");
        exit();
    }
}

// --- 2. LIVE DATA AGGREGATION ---
$loc_q = mysqli_query($conn, "SELECT location, SUM(amount) as total FROM savings WHERE user_id = '$user_id' GROUP BY location");
$totals = ['Bank' => 0, 'At Home' => 0, 'Seettu' => 0];
while($row = mysqli_fetch_assoc($loc_q)) {
    $totals[$row['location']] = $row['total'];
}
$grand_total = array_sum($totals);

// Psychological Metrics
$inflation_loss = $totals['At Home'] * 0.12; 
$potential_interest = $totals['At Home'] * 0.08; 

// --- 3. LIVE 5-YEAR PROJECTION LOGIC ---
$avg_q = mysqli_query($conn, "SELECT AVG(m_sum) as avg_val FROM (SELECT SUM(amount) as m_sum FROM savings WHERE user_id = '$user_id' GROUP BY DATE_FORMAT(date, '%Y-%m')) as monthly");
$monthly_avg = mysqli_fetch_assoc($avg_q)['avg_val'] ?? 0;

$projection_data = [];
$labels = [];
for ($i = 0; $i <= 5; $i++) {
    $labels[] = date('Y') + $i;
    $projection_data[] = $grand_total + ($monthly_avg * 12 * $i);
}

// --- 4. HANDLE TRANSACTIONS ---
if (isset($_POST['add_savings'])) {
    $amt = mysqli_real_escape_string($conn, $_POST['amount']);
    $goal = mysqli_real_escape_string($conn, $_POST['goal_name']);
    $loc = mysqli_real_escape_string($conn, $_POST['location']);
    $date = $_POST['date'];

    $sql = "INSERT INTO savings (user_id, amount, goal_name, location, date) VALUES ('$user_id', '$amt', '$goal', '$loc', '$date')";
    if(mysqli_query($conn, $sql)) {
        header("Location: savings.php?success=1");
        exit();
    }
}

$history = mysqli_query($conn, "SELECT * FROM savings WHERE user_id = '$user_id' ORDER BY date DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Budget | Wealth Accelerator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');

        :root {
            /* Sidebar Colors */
            --sidebar-bg: #1e293b;
            --sidebar-active: #3b82f6;
            
            /* Main Content Colors (Light) */
            --bg-light: #f1f5f9;
            --card-white: #ffffff;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --border: #e2e8f0;
            
            /* Action Colors */
            --gold: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --primary: #6366f1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-light); color: var(--text-main); display: flex; min-height: 100vh; }

        /* --- SIDEBAR (DASHBOARD STYLE) --- */
        .sidebar {
            width: 260px; background: var(--sidebar-bg); height: 100vh;
            position: fixed; padding: 30px 20px; display: flex; flex-direction: column; color: white;
        }
        .sidebar-brand { font-size: 1.4rem; font-weight: 800; margin-bottom: 40px; text-align: center; letter-spacing: 1px; }
        .nav-links { flex: 1; overflow-y: auto; }
        .nav-group-label { font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; margin: 25px 0 10px 10px; letter-spacing: 1px; }
        .nav-item {
            display: flex; align-items: center; padding: 12px 15px; color: #cbd5e1;
            text-decoration: none; border-radius: 10px; margin-bottom: 4px; transition: 0.3s; font-size: 0.9rem;
        }
        .nav-item i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-item.active { background: var(--sidebar-active); }
        .logout-link { padding: 15px; color: #fca5a5; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 10px; border-top: 1px solid rgba(255,255,255,0.1); margin-top: auto; }

        /* --- MAIN CONTENT (LIGHT) --- */
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        
        .card { 
            background: var(--card-white); border: 1px solid var(--border); 
            padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .pain-text { color: var(--danger); font-weight: 800; font-size: 1.4rem; }
        .gain-text { color: var(--success); font-weight: 800; font-size: 1.4rem; }

        input, select { 
            width: 100%; padding: 12px; margin: 8px 0 18px; background: #f8fafc; 
            border: 1px solid var(--border); color: var(--text-main); border-radius: 10px; outline: none;
        }
        input:focus { border-color: var(--primary); background: #fff; }

        .btn-gold { 
            width: 100%; padding: 16px; background: var(--gold); border: none; 
            border-radius: 12px; font-weight: 800; cursor: pointer; color: white; transition: 0.3s; 
        }
        .btn-gold:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }

        .badge-bin { display: flex; gap: 10px; margin-top: 15px; }
        .badge { 
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; background: #f1f5f9; color: #94a3b8; font-size: 1rem; 
        }
        .badge.active { background: var(--gold); color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; font-size: 0.75rem; color: var(--text-sub); padding: 12px; border-bottom: 2px solid var(--border); text-transform: uppercase; }
        td { padding: 15px 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        
        .delete-link { color: #94a3b8; transition: 0.3s; }
        .delete-link:hover { color: var(--danger); }
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
        <a href="savings.php" class="nav-item active">
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
    </div>

    <div style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </a>
    </div>
</div>


<div class="main">
    <div class="header-flex">
        <div>
            <h2 style="font-weight: 800;">Wealth Accelerator</h2>
            <p style="color: var(--text-sub); font-size: 0.9rem;">Nuwara Eliya Regional Model Active</p>
        </div>
        <div style="text-align: right;">
            <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700;">TOTAL SAVED</p>
            <h2 style="margin:0; color: var(--gold); font-weight: 800;">LKR <?php echo number_format($grand_total, 2); ?></h2>
        </div>
    </div>

    <div class="grid-3">
        <div class="card" style="border-left: 5px solid var(--danger);">
            <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700; margin-bottom: 5px;">INFLATION DAMAGE</p>
            <div class="pain-text">- LKR <?php echo number_format($inflation_loss, 2); ?></div>
            <small style="color: var(--text-sub);">Loss from "Home" storage.</small>
        </div>
        <div class="card" style="border-left: 5px solid var(--success);">
            <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700; margin-bottom: 5px;">BANK POTENTIAL</p>
            <div class="gain-text">+ LKR <?php echo number_format($potential_interest, 2); ?></div>
            <small style="color: var(--text-sub);">Interest growth potential.</small>
        </div>
        <div class="card" style="border-left: 5px solid var(--gold);">
            <p style="font-size: 0.7rem; color: var(--text-sub); font-weight: 700; margin-bottom: 5px;">MILESTONES (10K EACH)</p>
            <div class="badge-bin">
                <?php for($i=1; $i<=5; $i++): $active = ($grand_total >= $i*10000) ? 'active' : ''; ?>
                <div class="badge <?php echo $active; ?>"><i class="fas fa-trophy"></i></div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 30px;">
        <h3 style="font-size: 1rem; margin-bottom: 20px;"><i class="fas fa-chart-line" style="color: var(--primary);"></i> 5-Year Wealth Projection</h3>
        <div style="height: 250px;">
            <canvas id="wealthChart"></canvas>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 380px 1fr; gap: 30px;">
        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 20px;">Record Saving</h3>
            <form method="POST">
                <label>Amount (LKR)</label>
                <input type="number" name="amount" required>

                <label>Goal</label>
                <select name="goal_name">
                    <option value="House/Land">House / Land Purchase</option>
                    <option value="Education">Education Fund</option>
                    <option value="Emergency">Emergency Safety Net</option>
                    <option value="Seettu Investment">Seettu Investment</option>
                </select>

                <label>Storage Method</label>
                <select name="location">
                    <option value="Bank">🏦 Formal Bank (Safe)</option>
                    <option value="At Home">🏠 Cash at Home (Risk)</option>
                    <option value="Seettu">🤝 Seettu Cycle (Group Risk)</option>
                </select>

                <label>Date</label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>">

                <button type="submit" name="add_savings" class="btn-gold">Confirm Wealth Growth</button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size: 1rem; margin-bottom: 10px;">Recent Wealth Activity</h3>
            <table>
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>GOAL</th>
                        <th>LOCATION</th>
                        <th>AMOUNT</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($history)): ?>
                    <tr>
                        <td style="color: var(--text-sub);"><?php echo $row['date']; ?></td>
                        <td style="font-weight: 700;"><?php echo $row['goal_name']; ?></td>
                        <td>
                            <span style="color: <?php echo ($row['location']=='Bank') ? 'var(--success)' : 'var(--danger)'; ?>; font-size: 0.7rem; font-weight: 800;">
                                <?php echo strtoupper($row['location']); ?>
                            </span>
                        </td>
                        <td style="color: var(--success); font-weight: 800;">+ <?php echo number_format($row['amount'], 2); ?></td>
                        <td style="text-align: right;">
                            <a href="savings.php?delete_id=<?php echo $row['id']; ?>" class="delete-link" onclick="return confirm('Remove this wealth entry?')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('wealthChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            data: <?php echo json_encode($projection_data); ?>,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#f59e0b'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
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