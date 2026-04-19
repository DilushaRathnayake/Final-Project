<?php 
require_once 'lang.php';

// --- Improved Alert Logic ---
$msg = "";
$type = "";

if (isset($_GET['status'])) {
    if ($_GET['status'] == 'otp_sent') {
        $msg = "Security OTP has been sent to your email!";
        $type = "info";
    }
} elseif (isset($_GET['success'])) {
    $msg = "Registration Successful! You can login now.";
    $type = "success";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'wrong_password') $msg = "Invalid Password. Please try again.";
    elseif ($_GET['error'] == 'user_not_found') $msg = "NIC not found in our records.";
    elseif ($_GET['error'] == 'user_exists') $msg = "NIC or Email already registered.";
    elseif ($_GET['error'] == 'password_mismatch') $msg = "Passwords do not match!";
    elseif ($_GET['error'] == 'registration_failed') $msg = "System error during registration.";
    $type = "error";
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget | Role-Based Financial System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3b82f6;
            --bg-dark: #0f172a;
            --card-glass: rgba(30, 41, 59, 0.7);
            --border-glass: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --admin-color: #ef4444;
            --supervisor-color: #fbbf24;
            --user-color: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Noto Sans Sinhala', sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        /* --- MODERN TOAST STYLING --- */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 10005; }
        .modern-toast {
            background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(15px);
            border: 1px solid var(--border-glass); color: white; padding: 18px 25px;
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            display: flex; align-items: center; gap: 15px; transform: translateX(120%);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        .modern-toast.show { transform: translateX(0); }
        .toast-icon { width: 35px; height: 35px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
        .toast-success .toast-icon { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .toast-error .toast-icon { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .toast-info .toast-icon { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }

        /* Existing Styles */
        .glow { position: fixed; width: 400px; height: 400px; border-radius: 50%; filter: blur(120px); z-index: -1; opacity: 0.15; }
        .glow-1 { top: -100px; left: -100px; background: var(--primary); }
        .glow-2 { bottom: -100px; right: -100px; background: var(--admin-color); }

        nav {
            display: flex; justify-content: space-between; align-items: center; padding: 25px 8%;
            background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(15px);
            position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid var(--border-glass);
        }
        .logo { font-size: 1.6rem; font-weight: 800; text-decoration: none; color: white; }
        .logo span { color: var(--primary); }

        .hero-grid { display: grid; grid-template-columns: 1fr 450px; gap: 60px; padding: 60px 8%; align-items: start; }

        .auth-card {
            background: var(--card-glass); backdrop-filter: blur(25px);
            border: 1px solid var(--border-glass); border-radius: 32px;
            padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; background: rgba(0,0,0,0.3); padding: 6px; border-radius: 16px; }
        .tab { flex: 1; text-align: center; padding: 12px; cursor: pointer; border-radius: 12px; font-weight: 600; color: var(--text-dim); transition: 0.3s; }
        .tab.active { background: var(--primary); color: white; }

        .form-section { display: none; }
        .form-section.active { display: block; animation: slideUp 0.4s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-size: 0.75rem; color: var(--text-dim); margin: 0 0 5px 5px; text-transform: uppercase; letter-spacing: 1px; }
        
        input, select {
            width: 100%; padding: 14px 18px; border-radius: 14px;
            border: 1px solid var(--border-glass); background: rgba(255, 255, 255, 0.03);
            color: white; outline: none; transition: 0.3s;
        }
        input:focus, select:focus { border-color: var(--primary); background: rgba(255, 255, 255, 0.08); }
        select option { background: #1e293b; }

        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; margin-bottom: 10px; }
        .badge-admin { background: rgba(239, 68, 68, 0.2); color: var(--admin-color); }
        .badge-super { background: rgba(251, 191, 36, 0.2); color: var(--supervisor-color); }
        .badge-user { background: rgba(16, 185, 129, 0.2); color: var(--user-color); }

        .btn-submit {
            width: 100%; padding: 16px; background: var(--primary); color: white; border: none;
            border-radius: 14px; font-weight: 700; cursor: pointer; margin-top: 10px;
            transition: 0.3s; text-transform: uppercase;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4); }

        .role-features { padding: 40px 8% 80px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .feature-box { padding: 30px; border-radius: 24px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); }
        .feature-box i { font-size: 2rem; margin-bottom: 20px; }

        #loader {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.95); z-index: 10001; justify-content: center; align-items: center; flex-direction: column;
        }
        .spinner { width: 50px; height: 50px; border: 4px solid var(--border-glass); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 1024px) { .hero-grid, .role-features { grid-template-columns: 1fr; } .auth-card { max-width: 100%; } }
    </style>
</head>
<body>

<div class="toast-container">
    <?php if ($msg): ?>
    <div class="modern-toast toast-<?php echo $type; ?>" id="mainToast">
        <div class="toast-icon">
            <i class="fas <?php echo ($type == 'success') ? 'fa-check-circle' : (($type == 'error') ? 'fa-times-circle' : 'fa-paper-plane'); ?>"></i>
        </div>
        <div class="toast-content">
            <p style="margin:0; font-weight:600; font-size:0.9rem;"><?php echo $msg; ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="glow glow-1"></div>
<div class="glow glow-2"></div>

<div id="loader">
    <div class="spinner"></div>
    <p style="margin-top: 20px; font-weight: 600; letter-spacing: 2px;">SYNCHRONIZING SECURE ACCESS...</p>
</div>

<nav>
    <a href="#" class="logo"><i class="fas fa-fingerprint"></i> SMART<span>BUDGET</span></a>
    <div class="lang-switch">
        <a href="?lang=si" style="color: var(--text-dim); text-decoration: none; margin-right: 15px;">සිංහල</a>
        <a href="?lang=en" style="color: white; text-decoration: none; font-weight: 700;">English</a>
    </div>
</nav>

<div class="hero-grid">
    <div class="hero-text">
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <span class="role-badge badge-admin">ADMIN</span>
            <span class="role-badge badge-super">SUPERVISOR</span>
            <span class="role-badge badge-user">EMPLOYEE</span>
        </div>
        <h1 style="font-size: 2rem; margin-bottom: 25px; line-height: 1.1; font-style: italic;">Personal Finance and Expense Tracking System For Garment Employees In Nuwaraeliya District.</h1>
        <h1 style="font-size: 3.0rem; margin-bottom: 25px; line-height: 1.1;">Unified <span style="color: var(--primary);">Financial</span> Management.</h1>
        <p style="color: var(--text-dim); font-size: 1.2rem; margin-bottom: 35px;">A multi-tier research platform for garment professionals. Manage payroll, oversee floor-level finances, and optimize individual savings with role-specific tools.</p>
        
        <div style="background: rgba(255,255,255,0.03); padding: 25px; border-radius: 20px; border: 1px solid var(--border-glass);">
            <h4 style="margin-bottom: 10px;"><i class="fas fa-shield-halved" style="color: var(--primary); margin-right: 10px;"></i> System Integrity</h4>
            <p style="font-size: 0.9rem; color: var(--text-dim);">Access levels are strictly enforced based on your organizational role. Administrators oversee system health while Supervisors manage group financial trends.</p>
        </div>
    </div>

    <div class="auth-card">
        <div class="tabs">
            <div class="tab active" id="tab-login" onclick="switchTab('login')">Login</div>
            <div class="tab" id="tab-register" onclick="switchTab('register')">Register</div>
        </div>

        <div id="login" class="form-section active">
            <form action="auth_logic.php" method="POST" onsubmit="showLoader()">
                <div class="input-group">
                    <label>NIC Number</label>
                    <input type="text" name="nic" placeholder="Ex: 199XXXXXXXXV" required>
                </div>
                <div class="input-group">
                    <label>Access Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Sign In</button>
            </form>
        </div>

        <div id="register" class="form-section">
            <form action="auth_logic.php" method="POST" onsubmit="validateAndSubmit(event)">
                <div class="input-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-group">
                        <label>NIC Number</label>
                        <input type="text" name="nic" required>
                    </div>
                    <div class="input-group">
                        <label>System Role</label>
                        <select name="role" required>
                            <option value="user">Employee</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Factory Name</label>
                    <input type="text" name="factory" required>
                </div>

                <div class="input-group">
                    <label>Work Department</label>
                    <select name="dept" required>
    <option value="" disabled selected>Select Department</option>
    <option value="Sewigning">Sewigning</option>
    <option value="Design & Sampling">Design & Sampling</option>
    <option value="Production">Production / Sewing</option>
    <option value="Cutting">Cutting</option>
    <option value="Quality Control">Quality Control (QC)</option>
    <option value="Finishing">Finishing</option>
    <option value="Planning">Planning</option>
    <option value="HR">Human Resources (HR)</option>
    <option value="Finance">Finance & Accounting</option>
    <option value="Stores">Stores / Inventory</option>
    <option value="Maintenance">Maintenance</option>
    <option value="Procurement">Procurement / Purchasing</option>
    <option value="Logistics">Shipping / Logistics</option>
</select>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" id="reg-pw" name="password" required>
                    </div>
                    <div class="input-group">
                        <label>Confirm</label>
                        <input type="password" id="reg-cpw" name="confirm_password" required>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-submit">Create Account</button>
            </form>
        </div>
    </div>
</div>

<div class="role-features">
    
        <div class="feature-box">
            <i class="fas fa-wallet"></i>
            <h4>Income & Expense</h4>
            <p>Log your daily transactions effortlessly. Visualize where your money goes with categorized expense charts and income logs.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-hand-holding-dollar"></i>
            <h4>Loan Management</h4>
            <p>Track your active loans and repayment schedules. Get reminders for installments to maintain a healthy credit score.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-piggy-bank"></i>
            <h4>Savings & Seettu</h4>
            <p>Digitally manage your Seettu cycles and personal savings. Track your contribution progress and withdrawal dates in one place.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-calendar-alt"></i>
            <h4>Payday Planner</h4>
            <p>Organize your monthly salary the moment it arrives. Allocate funds for bills, savings, and essentials automatically.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-bullseye"></i>
            <h4>Budget Planner</h4>
            <p>Set monthly financial goals and spending limits. Receive alerts when you are nearing your budget threshold.</p>
        </div>
        <div class="feature-box">
            <i class="fas fa-bell"></i>
            <h4>Smart Notifications</h4>
            <p>Never miss a payment. Receive real-time alerts for loan dues, budget updates, and system-wide financial announcements.</p>
        </div>
    
</div>

<footer>
    <div style="text-align: center; padding: 40px; border-top: 1px solid var(--border-glass); color: var(--text-dim); font-size: 0.8rem;">
        &copy; 2026 SMART BUDGET | RESEARCH PROJECT | NUWARA ELIYA INDUSTRIAL SECTOR
    </div>
</footer>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        document.getElementById('tab-' + (tabId === 'forgot' ? 'login' : tabId)).classList.add('active');
    }

    function showLoader() { document.getElementById('loader').style.display = 'flex'; }

    function validateAndSubmit(e) {
        const pw = document.getElementById('reg-pw').value;
        const cpw = document.getElementById('reg-cpw').value;
        if (pw !== cpw) {
            alert("Passwords do not match!");
            e.preventDefault();
            return false;
        }
        showLoader();
    }

    // --- Toast Handler ---
    window.onload = function() {
        const toast = document.getElementById('mainToast');
        if (toast) {
            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 4000);
        }
    };
</script>
</body>
</html>
