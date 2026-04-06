<?php
session_start();
require_once 'config.php';

$msg = "";

// --- 1. පඩි වාර්තාවක් මැකීම (Delete Record) ---
if (isset($_GET['del_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['del_id']);
    // Record එක මැකීම
    mysqli_query($conn, "DELETE FROM salary_records WHERE salary_id = '$del_id'");
    $msg = "<div class='alert' style='background:#fee2e2; color:#b91c1c;'><i class='fas fa-trash'></i> Record removed from payroll history.</div>";
}

// --- 2. අංශය අනුව පඩි ගෙවීම (Department Bulk Process) ---
if (isset($_POST['process_dept'])) {
    $dept = mysqli_real_escape_string($conn, $_POST['dept_name']);
    $gross = mysqli_real_escape_string($conn, $_POST['gross_amount']);
    $date = $_POST['pay_date_dept'];

    // එම අංශයේ සියලුම සේවකයින් (Admin නොවන) ලබා ගැනීම
    $query = mysqli_query($conn, "SELECT user_id FROM users WHERE department = '$dept' AND role != 'admin'");
    $count = 0;
    
    if(mysqli_num_rows($query) > 0) {
        while ($user = mysqli_fetch_assoc($query)) {
            processSalary($conn, $user['user_id'], $gross, $date);
            $count++;
        }
        $msg = "<div class='alert success'><i class='fas fa-check-circle'></i> Success! $count employees in <b>$dept</b> processed successfully.</div>";
    } else {
        $msg = "<div class='alert' style='background:#fef3c7; color:#92400e;'><i class='fas fa-exclamation-triangle'></i> No employees found in $dept department.</div>";
    }
}

// --- පඩි ගණනය කිරීමේ Function එක ---
function processSalary($conn, $u_id, $gross, $date) {
    $epf_8 = $gross * 0.08;
    $epf_12 = $gross * 0.12;
    $etf_3 = $gross * 0.03;
    $net_salary = $gross - $epf_8;
    $desc = "Monthly Salary (Net after EPF)";

    // salary_records වගුවට දත්ත දැමීම
    mysqli_query($conn, "INSERT INTO salary_records (user_id, gross_salary, epf_employee, epf_employer, etf_employer, net_salary, pay_date) 
                         VALUES ('$u_id', '$gross', '$epf_8', '$epf_12', '$etf_3', '$net_salary', '$date')");
    
    // User ගේ income_records වගුවට දත්ත දැමීම
    mysqli_query($conn, "INSERT INTO income_records (user_id, amount, type, date, source) 
                         VALUES ('$u_id', '$net_salary', 'Salary', '$date', '$desc')");
}

// --- 3. Summary Stats ලබා ගැනීම ---
$current_month = date('m');
$current_year = date('Y');
$stats_res = mysqli_query($conn, "SELECT SUM(gross_salary) as total_gross, SUM(epf_employee + epf_employer) as total_epf, COUNT(salary_id) as total_slips 
                                 FROM salary_records 
                                 WHERE MONTH(pay_date) = '$current_month' AND YEAR(pay_date) = '$current_year'");
$stats = mysqli_fetch_assoc($stats_res);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management | Smart Budget Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        :root { --primary: #6366f1; --accent: #10b981; --dark: #0f172a; --bg: #f8fafc; --white: #ffffff; --text: #334155; }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); display: flex; color: var(--text); min-height: 100vh; }

        /* Sidebar - Adjusted for bottom logout */
        .sidebar { 
            width: 260px; 
            background: var(--dark); 
            color: white; 
            position: fixed; 
            height: 100vh; 
            padding: 30px 20px; 
            display: flex; 
            flex-direction: column; 
        }
        .sidebar h2 { color: var(--accent); margin-bottom: 40px; font-size: 1.4rem; font-weight: 800; }
        .nav-link { display: flex; align-items: center; padding: 12px 15px; color: #94a3b8; text-decoration: none; border-radius: 12px; margin-bottom: 8px; transition: 0.3s; }
        .nav-link i { margin-right: 12px; font-size: 1.1rem; width: 20px; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: white; }
        .nav-link.active { border-left: 4px solid var(--accent); }

        /* Push Logout to bottom */
        .logout-section {
            margin-top: auto;
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .logout-section a {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            transition: 0.3s;
        }

        /* Content */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 40px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        /* Stats Cards */
        .stats-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; margin-right: 15px; }
        .stat-val { font-size: 1.2rem; font-weight: 700; color: #1e293b; }
        .stat-label { font-size: 0.8rem; color: #64748b; }

        /* Grid */
        .action-grid { display: grid; grid-template-columns: 1fr 1.6fr; gap: 30px; }
        .card { background: var(--white); border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .card h3 { margin-bottom: 20px; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; color: #1e293b; }

        /* Form Elements */
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 5px; }
        input, select { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; }
        .btn { padding: 12px; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; width: 100%; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-export { background: #1e293b; color: white; text-decoration: none; padding: 10px 20px; border-radius: 10px; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .emp-name { font-weight: 600; color: #1e293b; display: block; }
        .emp-dept { font-size: 0.75rem; color: #94a3b8; }
        .badge-paid { background: #ecfdf5; color: #059669; padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 700; }
        
        .btn-action { padding: 8px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-view { background: #e0e7ff; color: #4338ca; }
        .btn-del { background: #fee2e2; color: #ef4444; }
        .btn-action:hover { transform: scale(1.05); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>SMART BUDGET</h2>
    <div style="margin-top:20px">
        <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
        <a href="manage_employees.php" class="nav-link"><i class="fas fa-users-cog"></i> Manage Employees</a>
        <a href="salary_process.php" class="nav-link active"><i class="fas fa-wallet"></i> Payroll Center</a>
        <a href="factory_seettu.php" class="nav-link"><i class="fas fa-layer-group"></i> Factory Seettu</a>
    </div>

    <div class="logout-section">
        <a href="logout.php" style="color: #fca5a5;">
            <i class="fas fa-sign-out-alt"></i> <span>Log Out</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="page-header">
        <div>
            <h1>Payroll Management</h1>
            <p style="color: #64748b;">Process salaries and manage EPF/ETF contributions.</p>
        </div>
        <a href="export_salary.php?month=<?php echo date('Y-m'); ?>" class="btn-export">
            <i class="fas fa-file-csv"></i> Download CSV Report
        </a>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon" style="background: #eef2ff; color: #6366f1;"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <p class="stat-label">Total Monthly Gross</p>
                <p class="stat-val">LKR <?php echo number_format($stats['total_gross'] ?? 0, 2); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-piggy-bank"></i></div>
            <div>
                <p class="stat-label">Total EPF (20%)</p>
                <p class="stat-val">LKR <?php echo number_format($stats['total_epf'] ?? 0, 2); ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #fff7ed; color: #f59e0b;"><i class="fas fa-users"></i></div>
            <div>
                <p class="stat-label">Salaries Processed</p>
                <p class="stat-val"><?php echo $stats['total_slips'] ?? 0; ?> Persons</p>
            </div>
        </div>
    </div>

    <?php if($msg) echo $msg; ?>

    <div class="action-grid">
        <div class="card">
            <h3><i class="fas fa-cog" style="color: var(--primary);"></i> Process Salaries</h3>
            <form method="POST">
                <label>Target Department</label>
                <select name="dept_name" required>
                    <option value="Sewing">Sewing Section</option>
                    <option value="Cutting">Cutting Section</option>
                    <option value="Packing">Packing & Quality</option>
                    <option value="Office">Office Staff</option>
                </select>
                
                <label>Standard Gross Amount (LKR)</label>
                <input type="number" name="gross_amount" placeholder="e.g. 50000" step="0.01" required>
                
                <label>Payment Date</label>
                <input type="date" name="pay_date_dept" value="<?php echo date('Y-m-d'); ?>" required>
                
                <button type="submit" name="process_dept" class="btn btn-primary">Process Department Now</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-history"></i> Recent Transactions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Net Paid</th>
                        <th>Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = mysqli_query($conn, "SELECT s.*, u.full_name, u.department FROM salary_records s 
                                               JOIN users u ON s.user_id = u.user_id 
                                               ORDER BY s.salary_id DESC LIMIT 10");
                    if(mysqli_num_rows($res) > 0) {
                        while($row = mysqli_fetch_assoc($res)) {
                            ?>
                            <tr>
                                <td>
                                    <span class="emp-name"><?php echo $row['full_name']; ?></span>
                                    <span class="emp-dept"><?php echo $row['department']; ?></span>
                                </td>
                                <td>
                                    <span style="font-weight:700;">LKR <?php echo number_format($row['net_salary'], 2); ?></span><br>
                                    <span class="badge-paid">Paid</span>
                                </td>
                                <td><?php echo date('M d', strtotime($row['pay_date'])); ?></td>
                                <td style="text-align: right;">
                                    <a href="view_slip.php?id=<?php echo $row['salary_id']; ?>" target="_blank" class="btn-action btn-view">
                                        <i class="fas fa-print"></i> Slip
                                    </a>
                                    <a href="salary_process.php?del_id=<?php echo $row['salary_id']; ?>" class="btn-action btn-del" onclick="return confirm('Permanently delete this record?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding: 20px;'>No records found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>