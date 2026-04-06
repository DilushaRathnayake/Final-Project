<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id'])) {
    header("Location: factory_seettu.php");
    exit();
}

$seettu_id = mysqli_real_escape_string($conn, $_GET['id']);

// සීරට්ටු විස්තර ලබා ගැනීම
$seettu_res = mysqli_query($conn, "SELECT * FROM factory_seettu WHERE id = '$seettu_id'");
$seettu = mysqli_fetch_assoc($seettu_res);

// සාමාජිකයෙක් එකතු කිරීම
if (isset($_POST['add_member'])) {
    $u_id = $_POST['user_id'];
    mysqli_query($conn, "INSERT INTO seettu_members (seettu_id, user_id, joined_date) VALUES ('$seettu_id', '$u_id', CURDATE())");
    header("Location: view_seettu_members.php?id=$seettu_id");
}

// Winner Record කිරීම
if (isset($_POST['save_winner'])) {
    $winner_id = $_POST['winner_user_id'];
    $month = $_POST['draw_month'];
    $payout = $seettu['monthly_amount'] * $seettu['total_months'];

    mysqli_query($conn, "INSERT INTO seettu_winners (seettu_id, user_id, draw_month, payout_amount) 
                         VALUES ('$seettu_id', '$winner_id', '$month', '$payout')");
    
    // User ගේ savings වලට එකතු කිරීම
    mysqli_query($conn, "INSERT INTO savings (user_id, amount, date, description) 
                         VALUES ('$winner_id', '$payout', CURDATE(), 'Seettu Winner - $month')");
    
    $msg = "Winner Saved Successfully!";
}

// දැනට ඉන්න සාමාජිකයන් ලැයිස්තුව
$members_res = mysqli_query($conn, "SELECT u.user_id, u.full_name FROM seettu_members sm 
                                    JOIN users u ON sm.user_id = u.user_id WHERE sm.seettu_id = '$seettu_id'");
$members_array = [];
while($m = mysqli_fetch_assoc($members_res)) {
    $members_array[] = $m;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Members | Lucky Draw</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #6366f1; --accent: #10b981; --bg: #f8fafc; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); padding: 40px; }
        .card { background: white; padding: 25px; border-radius: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        /* Lucky Draw UI */
        .lucky-box { text-align: center; padding: 40px; border: 2px dashed var(--primary); border-radius: 20px; background: #eef2ff; }
        #winner-display { font-size: 2rem; font-weight: 800; color: var(--primary); margin: 20px 0; min-height: 40px; }
        
        .btn-draw { background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border: none; padding: 15px 35px; border-radius: 50px; font-weight: 700; cursor: pointer; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3); }
        .btn-save { background: var(--accent); color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>

<div style="max-width: 800px; margin: 0 auto;">
    <a href="factory_seettu.php" style="text-decoration:none; color:#64748b;"><i class="fas fa-arrow-left"></i> Back to Seettu List</a>
    <h1 style="margin-top:15px;"><?php echo $seettu['group_name']; ?></h1>

    <div class="card">
        <h3>Add New Member</h3>
        <form method="POST" style="display:flex; gap:10px; margin-top:15px;">
            <select name="user_id" style="flex:1; padding:12px; border-radius:10px; border:1px solid #ddd;">
                <?php 
                $u_list = mysqli_query($conn, "SELECT user_id, full_name FROM users");
                while($u = mysqli_fetch_assoc($u_list)) echo "<option value='{$u['user_id']}'>{$u['full_name']}</option>";
                ?>
            </select>
            <button type="submit" name="add_member" class="btn-save" style="background:var(--primary);">Add Member</button>
        </form>
    </div>

    <div class="card lucky-box">
        <h3><i class="fas fa-gift"></i> Monthly Lucky Draw</h3>
        <p style="color:#64748b;">Click the button to randomly select a winner from the members list.</p>
        
        <div id="winner-display">???</div>
        
        <button type="button" onclick="startLuckyDraw()" class="btn-draw">START LUCKY DRAW</button>

        <form method="POST" id="save-winner-form" style="display:none; margin-top:30px; border-top:1px solid #cbd5e1; padding-top:20px;">
            <input type="hidden" name="winner_user_id" id="hidden_winner_id">
            <div style="display:flex; justify-content:center; gap:15px; align-items:center;">
                <input type="text" name="draw_month" placeholder="Month (e.g. May 2026)" required style="padding:10px; border-radius:8px; border:1px solid #ddd;">
                <button type="submit" name="save_winner" class="btn-save">Confirm & Save Winner</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Current Members (<?php echo count($members_array); ?>)</h3>
        <table>
            <thead><tr><th>ID</th><th>Member Name</th></tr></thead>
            <tbody>
                <?php foreach($members_array as $m): ?>
                <tr><td>#<?php echo $m['user_id']; ?></td><td><?php echo $m['full_name']; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const members = <?php echo json_encode($members_array); ?>;

    function startLuckyDraw() {
        if (members.length === 0) {
            alert("No members in this group to draw!");
            return;
        }

        const display = document.getElementById('winner-display');
        const saveForm = document.getElementById('save-winner-form');
        let counter = 0;
        
        // සජීවීව නම් කැරකෙන Animation එක
        const interval = setInterval(() => {
            const randomMember = members[Math.floor(Math.random() * members.length)];
            display.innerText = randomMember.full_name;
            counter++;
            
            if (counter > 20) { // ලූපය 20 වතාවක් කැරකුණු පසු නතර වේ
                clearInterval(interval);
                display.style.color = "#10b981"; // කොළ පාටින් පෙන්වයි
                document.getElementById('hidden_winner_id').value = randomMember.user_id;
                saveForm.style.display = 'block'; // Save කරන්න Form එක පෙන්වයි
                
                // ඇනිමේෂන් එකක් එක්ක ජයග්‍රාහකයා පෙන්වමු
                display.innerHTML = "🎉 " + randomMember.full_name + " 🎉";
            }
        }, 100);
    }
</script>

</body>
</html>