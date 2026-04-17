<?php 
require_once 'lang.php';

if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Budget - <?php echo $text['title'] ?? 'Digital Financial Hub'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;700&family=Noto+Sans+Tamil:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #2563eb;
            --accent-glow: rgba(59, 130, 246, 0.4);
            /* --- THAWATH LIGHT COLOR THEME EKAK --- */
            --dark-bg: #111827; /* Kalin thibbe #030712 */
            --glass: rgba(255, 255, 255, 0.08); /* Contrasting clarity wadi kala */
            --glass-border: rgba(255, 255, 255, 0.15); /* Clarity wadi kala */
            --accent-gold: #fbbf24;
            --success-green: #10b981;
            --text-main: #f8fafc;
            --text-dim: #cbd5e1; /* Lights thawa wenas kala */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: var(--dark-bg);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(251, 191, 36, 0.05) 0%, transparent 40%);
            color: var(--text-main);
            font-family: 'Poppins', 'Noto Sans Sinhala', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Loader */
        #otp-loader {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(17, 24, 39, 0.98); z-index: 10001; justify-content: center; align-items: center; flex-direction: column;
        }
        .spinner { 
            width: 60px; height: 60px; border: 3px solid transparent; border-top-color: var(--primary-blue); 
            border-radius: 50%; animation: spin 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite; 
            box-shadow: 0 0 15px var(--accent-glow);
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Navbar Enhancement */
        nav {
            display: flex; justify-content: space-between; align-items: center; padding: 20px 8%;
            background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(20px);
            position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid var(--glass-border);
        }
        .logo-badge { 
            font-size: 1.6rem; font-weight: 800; letter-spacing: -1px;
            background: linear-gradient(to right, #fff, var(--primary-blue));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .lang-switch a { font-size: 0.85rem; color: var(--text-dim); text-decoration: none; margin: 0 12px; transition: 0.3s; padding: 5px 10px; border-radius: 8px; }
        .lang-switch a:hover { background: var(--glass); color: white; }

        .hero-container { display: flex; align-items: center; justify-content: center; padding: 80px 8%; gap: 60px; flex-wrap: wrap; }
        
        .hero-text { flex: 1; max-width: 600px; }
        .topic-highlight {
            background: linear-gradient(90deg, rgba(37, 99, 235, 0.15), transparent);
            border-left: 4px solid var(--primary-blue);
            padding: 20px; margin-bottom: 30px; border-radius: 0 16px 16px 0;
            backdrop-filter: blur(10px);
        }
        .topic-highlight span { text-transform: uppercase; letter-spacing: 3px; font-size: 0.7rem; color: var(--primary-blue); font-weight: 700; margin-bottom: 8px; display: block; }
        .topic-highlight h1 { font-size: 1.4rem; font-weight: 600; color: var(--text-main); }

        .hero-text h2 { font-size: 3.5rem; margin-bottom: 25px; line-height: 1.1; font-weight: 800; letter-spacing: -2px; }
        .hero-text p { color: var(--text-dim); font-size: 1.1rem; margin-bottom: 30px; }

        /* Auth Card Premium Styling */
        .auth-card {
            background: var(--glass); backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border); border-radius: 35px;
            width: 100%; max-width: 450px; padding: 45px 35px;
            box-shadow: 0 30px 80px -15px rgba(0,0,0,0.6);
            position: relative; overflow: hidden;
        }
        .auth-card::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.03) 0%, transparent 70%);
            pointer-events: none;
        }

        .tabs { display: flex; margin-bottom: 30px; background: rgba(0,0,0,0.2); border-radius: 18px; padding: 6px; }
        .tab { flex: 1; text-align: center; padding: 14px; cursor: pointer; color: var(--text-dim); font-weight: 600; border-radius: 14px; transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-size: 0.9rem; }
        .tab.active { background: var(--primary-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }

        .form-section { display: none; }
        .form-section.active { display: block; animation: slideUp 0.5s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        input, select {
            width: 100%; padding: 16px; margin-bottom: 15px; border-radius: 15px;
            border: 1px solid var(--glass-border); background: rgba(255,255,255,0.03);
            color: white; outline: none; transition: 0.3s; font-family: 'Poppins', sans-serif;
        }
        input:focus { border-color: var(--primary-blue); box-shadow: 0 0 10px rgba(37, 99, 235, 0.1); background: rgba(255,255,255,0.06); }

        .btn-auth {
            width: 100%; padding: 18px; background: var(--primary-blue); color: white; border: none;
            border-radius: 15px; font-weight: 700; cursor: pointer; text-transform: uppercase; 
            letter-spacing: 2px; font-size: 0.9rem; transition: 0.4s;
        }
        .btn-auth:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4); }

        .forgot-pw {
            display: block; text-align: right; font-size: 0.8rem; color: var(--text-dim); 
            text-decoration: none; margin-bottom: 20px; transition: 0.3s;
        }
        .forgot-pw:hover { color: var(--primary-blue); }

        /* Stats Bar & Details */
        .details-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px; padding: 80px 8%; background: rgba(255,255,255,0.01);
        }
        .info-card {
            padding: 40px; border-radius: 30px; background: var(--glass);
            border: 1px solid var(--glass-border); transition: 0.4s;
        }
        .info-card:hover { border-color: var(--primary-blue); transform: translateY(-10px); }
        .info-card i { font-size: 2.5rem; color: var(--accent-gold); margin-bottom: 20px; }
        .info-card h4 { font-size: 1.3rem; margin-bottom: 15px; }
        .info-card p { color: var(--text-dim); font-size: 0.9rem; }

        .professional-bar { 
            display: flex; justify-content: space-around; flex-wrap: wrap;
            padding: 60px 8%; border-top: 1px solid var(--glass-border); gap: 40px;
        }
        .stat-box { text-align: center; }
        .stat-box h3 { font-size: 3rem; color: white; font-weight: 800; line-height: 1; }
        .stat-box p { color: var(--primary-blue); font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px; }

        footer { text-align: center; padding: 60px; border-top: 1px solid var(--glass-border); color: var(--text-dim); font-size: 0.85rem; letter-spacing: 1px; }
    </style>
</head>
<body>

<div id="otp-loader">
    <div class="spinner"></div>
    <p style="margin-top: 25px; font-weight: 600; letter-spacing: 2px; color: var(--primary-blue);">ENCRYPTING SESSION</p>
</div>

<nav>
    <div class="logo-badge">SMART BUDGET.</div>
    <div class="lang-switch">
        <a href="?lang=si">සිංහල</a>
        <a href="?lang=en" style="color: white; font-weight: 700;">English</a>
    </div>
</nav>

<div class="hero-container">
    <div class="hero-text">
        <div class="topic-highlight">
            <span>The Future of Industrial Savings</span>
            <h1>Economic Empowerment System for Apparel Professionals</h1>
        </div>
        <h2>Master Your <br><span style="color: var(--primary-blue);">Earnings.</span></h2>
        <p>A data-driven financial ecosystem tailored for the garment sector of Nuwara Eliya. Track every cent, manage micro-loans, and grow your wealth with advanced AI insights.</p>
        
        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <div style="padding: 15px 25px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success-green);">
                <span style="display:block; font-size:0.7rem; color:var(--success-green); font-weight:700;">SECURED BY</span>
                <span style="font-weight:700;">AES-256 BIT</span>
            </div>
            <div style="padding: 15px 25px; border-radius: 12px; background: rgba(251, 191, 36, 0.1); border: 1px solid var(--accent-gold);">
                <span style="display:block; font-size:0.7rem; color:var(--accent-gold); font-weight:700;">SUPPORTING</span>
                <span style="font-weight:700;">TRILINGUAL</span>
            </div>
        </div>
    </div>

    <div class="auth-card">
        <div class="tabs">
            <div class="tab active" id="tab-login" onclick="switchTab('login')">Account Login</div>
            <div class="tab" id="tab-register" onclick="switchTab('register')">New Member</div>
        </div>

        <div id="login" class="form-section active">
            <form action="auth_logic.php" method="POST" onsubmit="showLoader()">
                <label style="font-size: 0.75rem; color: var(--text-dim); margin-left: 5px; margin-bottom: 8px; display: block;">National ID Card</label>
                <input type="text" name="nic" placeholder="Ex: 199XXXXXXXXV" required>
                
                <label style="font-size: 0.75rem; color: var(--text-dim); margin-left: 5px; margin-bottom: 8px; display: block;">Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
                
                <a href="#" class="forgot-pw" onclick="switchTab('forgot')">Recover Password?</a>
                
                <button type="submit" name="login" class="btn-auth">Access Dashboard</button>
            </form>
        </div>

        <div id="register" class="form-section">
            <form action="auth_logic.php" method="POST" onsubmit="showLoader()">
                <input type="text" name="full_name" placeholder="Full Name (Official)" required>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <input type="text" name="nic" placeholder="NIC Number" required>
                    <input type="text" name="factory" placeholder="Factory Name">
                </div>
                <select name="dept" required>
                    <option value="" disabled selected>Work Department</option>
                    <option value="Production">Production / Sewing</option>
                    <option value="QC">Quality Control (QC)</option>
                    <option value="HR">HR & Admin</option>
                    <option value="Cutting">Cutting Section</option>
                </select>
                <input type="email" name="email" placeholder="Email (For OTP Security)" required>
                <select name="role" required>
                    <option value="user">Individual Member</option>
                    <option value="supervisor">Floor Supervisor</option>
                </select>
                <input type="password" name="password" placeholder="Set Secure Password" required>
                <button type="submit" name="register" class="btn-auth">Initialize Account</button>
            </form>
        </div>

        <div id="forgot" class="form-section">
            <h3 style="margin-bottom: 10px; font-size: 1.4rem; text-align: center;">Reset Key</h3>
            <p style="font-size: 0.85rem; color: var(--text-dim); margin-bottom: 25px; text-align: center;">Enter your email to receive a secure recovery OTP.</p>
            <form action="auth_logic.php" method="POST" onsubmit="showLoader()">
                <input type="email" name="reset_email" placeholder="Registered Email Address" required>
                <button type="submit" name="forgot_password" class="btn-auth">Send Recovery OTP</button>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="#" style="color: var(--text-dim); font-size: 0.85rem; text-decoration: none;" onclick="switchTab('login')">Return to Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="professional-bar">
    <div class="stat-box"><h3>500+</h3><p>Research Cases</p></div>
    <div class="stat-box"><h3>24/7</h3><p>Live Monitoring</p></div>
    <div class="stat-box"><h3>100%</h3><p>Privacy Shield</p></div>
</div>

<div class="details-grid">
    <div class="info-card">
        <i class="fas fa-microchip"></i>
        <h4>Smart Analysis</h4>
        <p>Our algorithm analyzes Nuwara Eliya district cost of living to give you tailored saving advice.</p>
    </div>
    <div class="info-card">
        <i class="fas fa-vault"></i>
        <h4>Loan Mitigation</h4>
        <p>Special tools to track 'Seettu' and shop credits to help you stay debt-free.</p>
    </div>
    <div class="info-card">
        <i class="fas fa-shield-halved"></i>
        <h4>Research Ethics</h4>
        <p>Strictly anonymized data collection ensuring 100% employee confidentiality.</p>
    </div>
</div>

<footer>
    &copy; 2026 SMART BUDGET | RESEARCH IMPLEMENTATION | NUWARA ELIYA APPAREL SECTOR
</footer>

<script>
    function switchTab(tabId) {
        document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        
        if(tabId === 'login' || tabId === 'forgot') {
            document.getElementById('tab-login').classList.add('active');
        } else if(tabId === 'register') {
            document.getElementById('tab-register').classList.add('active');
        }
    }
    function showLoader() { document.getElementById('otp-loader').style.display = 'flex'; }
</script>
</body>
</html>
