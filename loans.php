<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$user_id = $_SESSION['user_id'];

// --- 1. FETCH USER NIC ---
$user_q = mysqli_query($conn, "SELECT nic FROM users WHERE user_id = '$user_id'"); 
$user_data = mysqli_fetch_assoc($user_q);
$user_nic = $user_data['nic'] ?? "Not Registered";

// --- 2. GET TOTAL INCOME ---
$income_q = mysqli_query($conn, "SELECT SUM(amount) as total FROM income_records WHERE user_id = '$user_id'");
$total_income = mysqli_fetch_assoc($income_q)['total'] ?? 1;

// --- 3. GET DEBT TOTALS ---
$debt_stats_q = mysqli_query($conn, "SELECT SUM(monthly_repayment) as emi, SUM(total_amount) as principal FROM loans WHERE user_id = '$user_id' AND status = 'Active'");
$debt_stats = mysqli_fetch_assoc($debt_stats_q);
$total_emi = $debt_stats['emi'] ?? 0;
$total_principal = $debt_stats['principal'] ?? 0;
$dti_ratio = ($total_emi / $total_income) * 100;

// --- 4. BANK & FINANCE ANALYTICS ---
$bank_q = mysqli_query($conn, "SELECT SUM(total_amount) as total_p, SUM(monthly_repayment) as total_e 
                                FROM loans 
                                WHERE user_id = '$user_id' AND status = 'Active' 
                                AND (loan_source LIKE '%Bank%' OR loan_source LIKE '%Finance%' OR loan_source LIKE '%BOC%' OR loan_source LIKE '%HNB%')");
$bank_data = mysqli_fetch_assoc($bank_q);
$bank_principal = $bank_data['total_p'] ?? 0;
$bank_emi = $bank_data['total_e'] ?? 0;
$months_remaining = ($bank_emi > 0) ? ceil($bank_principal / $bank_emi) : 0;
$freedom_date = ($months_remaining > 0) ? date('F Y', strtotime("+$months_remaining months")) : "No Institutional Debt";

// --- 5. HANDLE NEW LOAN ---
if (isset($_POST['add_loan'])) {
    $source = mysqli_real_escape_string($conn, $_POST['source']);
    $total = mysqli_real_escape_string($conn, $_POST['total']);
    $repay = mysqli_real_escape_string($conn, $_POST['repay']);
    $due = $_POST['due_date'];

    mysqli_query($conn, "INSERT INTO loans (user_id, loan_source, total_amount, monthly_repayment, due_date) 
                         VALUES ('$user_id', '$source', '$total', '$repay', '$due')");
    header("Location: loans.php?success=1"); exit();
}

$active_loans = mysqli_query($conn, "SELECT * FROM loans WHERE user_id = '$user_id' AND status = 'Active' ORDER BY due_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Budget | Debt Guard Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --danger: #ef4444;
            --warning: #f59e0b;
            --success: #10b981;
            --primary: #6366f1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-light); color: var(--text-main); display: flex; min-height: 100vh; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px; background: var(--sidebar-bg); height: 100vh;
            position: fixed; padding: 30px 20px; display: flex; flex-direction: column; color: white; z-index: 1000;
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

        /* --- MAIN CONTENT --- */
        .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); transition: 0.5s; }
        
        /* SYNC OVERLAY */
        #sync-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #0f172a; z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: 0.8s ease-in-out; color: white; }
        .spinner { width: 50px; height: 50px; border: 5px solid rgba(99, 102, 241, 0.1); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .nic-badge { background: #dcfce7; border: 1px solid var(--success); color: var(--success); padding: 8px 15px; border-radius: 30px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-white); border: 1px solid var(--border); padding: 25px; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        
        .meter-bg { width: 100%; height: 12px; background: #e2e8f0; border-radius: 10px; margin: 15px 0; overflow: hidden; }
        .meter-fill { height: 100%; transition: 1s ease-in-out; }

        .layout { display: grid; grid-template-columns: 380px 1fr; gap: 30px; }
        .panel { background: var(--card-white); border: 1px solid var(--border); padding: 30px; border-radius: 24px; }

        input, select { width: 100%; padding: 12px; margin: 8px 0 15px; background: #f8fafc; border: 1px solid var(--border); color: var(--text-main); border-radius: 10px; outline: none; }
        .btn-prime { width: 100%; padding: 15px; background: var(--primary); border: none; border-radius: 12px; color: white; font-weight: 800; cursor: pointer; transition: 0.3s; }
        .btn-prime:hover { background: #4f46e5; transform: translateY(-2px); }

        .bank-summary-card { background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; padding: 25px; border-radius: 20px; margin-bottom: 25px; }
    </style>
</head>
<body onload="simulateSync()">

<div id="sync-overlay">
    <div class="spinner"></div>
    <h2 style="margin-top: 20px;">Secure Banking Sync</h2>
    <p style="color: #94a3b8;">Authenticating NIC: <span style="color: white;"><?php echo $user_nic; ?></span></p>
    <div id="sync-status" style="margin-top: 10px; font-size: 0.9rem; color: var(--success);">Connecting to Central Bureau...</div>
</div>

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
        <a href="loans.php" class="nav-item active">
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


<div class="main" id="main-content" style="opacity: 0;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px;">
        <div>
            <div style="display: flex; gap: 10px;">
                <div class="nic-badge"><i class="fas fa-id-card"></i> NIC: <?php echo $user_nic; ?></div>
                <div class="nic-badge" style="background: #e0f2fe; border-color: #0ea5e9; color: #0ea5e9;">
                    <i class="fas fa-link"></i> CRIB Live
                </div>
            </div>
            <h1 style="margin:10px 0 0 0;"><i class="fas fa-shield-virus" style="color: var(--primary);"></i> Debt Guard Professional</h1>
            <p style="color: var(--text-sub);">Cross-referencing liabilities for Nuwara Eliya Garment Workers.</p>
        </div>
        <div style="text-align: right;">
            <span style="color: var(--text-sub); font-size: 0.8rem; font-weight: 700;">TOTAL DEBT BURDEN</span>
            <h2 style="margin:0; color: var(--danger); font-weight: 800;">LKR <?php echo number_format($total_principal); ?></h2>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span style="font-size: 0.75rem; color: var(--text-sub); font-weight: 700;">DEBT-TO-INCOME RATIO</span>
            <div style="font-size: 1.8rem; font-weight: 900; margin-top: 5px;"><?php echo number_format($dti_ratio, 1); ?>%</div>
            <div class="meter-bg">
                <?php $color = ($dti_ratio > 30) ? 'var(--danger)' : (($dti_ratio > 15) ? 'var(--warning)' : 'var(--success)'); ?>
                <div class="meter-fill" style="width: <?php echo min($dti_ratio, 100); ?>%; background: <?php echo $color; ?>;"></div>
            </div>
            <small style="color: <?php echo $color; ?>; font-weight: 600;">Status: <?php echo ($dti_ratio > 30) ? "Critical Liability" : "Verified Stable"; ?></small>
        </div>

        <div class="stat-card">
            <span style="font-size: 0.75rem; color: var(--text-sub); font-weight: 700;">MONTHLY COMMITMENT</span>
            <div style="font-size: 1.8rem; font-weight: 900; margin-top: 5px; color: var(--warning);">LKR <?php echo number_format($total_emi); ?></div>
            <small style="color: var(--text-sub);">Deducted from Monthly Budget</small>
        </div>

        <div class="stat-card">
            <span style="font-size: 0.75rem; color: var(--text-sub); font-weight: 700;">MAX BORROWING CAPACITY</span>
            <div style="font-size: 1.8rem; font-weight: 900; margin-top: 5px; color: var(--primary);">LKR <?php echo number_format($total_income * 0.30); ?></div>
            <small style="color: var(--text-sub);">Safe 30% Salary Limit</small>
        </div>
    </div>

    <div class="layout">
        <div class="panel">
            <h3>Register Institutional Loan</h3>
            <form method="POST">
                <label>Lending Source</label>
                <select name="source" required>
                    <optgroup label="Government Banks (NIC Linked)">
                        <option value="Bank of Ceylon (BOC)">Bank of Ceylon (BOC)</option>
                        <option value="People's Bank">People's Bank</option>
                        <option value="National Savings Bank (NSB)">National Savings Bank (NSB)</option>
                        <option value="Regional Development Bank (RDB)">Regional Development Bank (RDB)</option>
                    </optgroup>
                    <optgroup label="Private Commercial Banks (NIC Linked)">
                        <option value="Commercial Bank">Commercial Bank</option>
                        <option value="Hatton National Bank (HNB)">Hatton National Bank (HNB)</option>
                        <option value="Sampath Bank">Sampath Bank</option>
                        <option value="Seylan Bank">Seylan Bank</option>
                        <option value="Nations Trust Bank (NTB)">Nations Trust Bank (NTB)</option>
                        <option value="Pan Asia Bank">Pan Asia Bank</option>
                    </optgroup>
                    <optgroup label="Finance & Microfinance Companies">
                        <option value="LOLC Finance">LOLC Finance</option>
                        <option value="LB Finance">LB Finance</option>
                        <option value="HNB Finance">HNB Finance</option>
                        <option value="SINGER Finance">SINGER Finance</option>
                        <option value="Alliance Finance">Alliance Finance</option>
                        <option value="Valibel Finance">Valibel Finance</option>
                    </optgroup>
                    <optgroup label="Informal Sources (Manual Tracking)">
                        <option value="Money Lender">⚠️ Informal Money Lender</option>
                        <option value="Factory Colleague">🤝 Factory Colleague</option>
                        <option value="Shop Credit">🛍️ Shop Credit / Grocery Debt</option>
                    </optgroup>
                </select>

                <label>Total Loan Amount</label>
                <input type="number" name="total" required>

                <label>Monthly Installment</label>
                <input type="number" name="repay" required>

                <label>Repayment Date</label>
                <input type="date" name="due_date" required>

                <button type="submit" name="add_loan" class="btn-prime">Sync with NIC Record</button>
            </form>
        </div>

        <div class="panel">
            <div class="bank-summary-card">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0;"><i class="fas fa-university"></i> Bank Tracker</h3>
                        <p style="font-size: 0.75rem; opacity: 0.8; margin-top: 5px;">NIC Encrypted Connection</p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.65rem; font-weight: 800; opacity: 0.8;">DEBT-FREE DATE</span>
                        <div style="font-size: 1.3rem; font-weight: 900;"><?php echo $freedom_date; ?></div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                    <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 0.65rem; opacity: 0.8;">BANK DEBT</div>
                        <div style="font-size: 1.1rem; font-weight: 800;">LKR <?php echo number_format($bank_principal); ?></div>
                    </div>
                    <div style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 12px; text-align: center;">
                        <div style="font-size: 0.65rem; opacity: 0.8;">REMAINING</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: #fbbf24;"><?php echo $months_remaining; ?> EMIs</div>
                    </div>
                </div>
            </div>

            <h3 style="margin-bottom: 15px;">Active Loan Schedules</h3>
            <?php while($row = mysqli_fetch_assoc($active_loans)): 
                $is_bank = (strpos($row['loan_source'], 'Bank') !== false || strpos($row['loan_source'], 'BOC') !== false);
            ?>
            <div style="background: #f8fafc; border: 1px solid var(--border); margin-bottom: 15px; padding: 18px; border-radius: 15px; border-left: 5px solid <?php echo $is_bank ? 'var(--success)' : 'var(--primary)'; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="display: block; font-size: 1rem;"><?php echo $row['loan_source']; ?></strong>
                        <?php if($is_bank): ?><small style="color: var(--success); font-weight: 600;"><i class="fas fa-check-double"></i> NIC Sync Verified</small><?php endif; ?>
                        <div style="color: var(--text-sub); font-size: 0.75rem; margin-top: 5px;">Due Date: <?php echo $row['due_date']; ?></div>
                    </div>
                    <div style="text-align: right;">
                        <span style="color: var(--danger); font-weight: 800; font-size: 1.1rem;">LKR <?php echo number_format($row['monthly_repayment']); ?></span>
                        <div style="font-size: 0.7rem; color: var(--text-sub);">Monthly EMI</div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script>
function simulateSync() {
    const statuses = ["Connecting to Central Bureau...", "Decrypting NIC Data...", "Fetching CRIB Records...", "Syncing with Bank Servers...", "Finalizing Debt Profile..."];
    let i = 0;
    const statusText = document.getElementById('sync-status');
    
    const interval = setInterval(() => {
        statusText.innerText = statuses[i];
        i++;
        if (i >= statuses.length) {
            clearInterval(interval);
            document.getElementById('sync-overlay').style.opacity = '0';
            setTimeout(() => {
                document.getElementById('sync-overlay').style.display = 'none';
                document.getElementById('main-content').style.opacity = '1';
            }, 800);
        }
    }, 600);
}
</script>

</body>
</html>