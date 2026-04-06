<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Translation Dictionary
$translations = [
    'en' => [
        'title' => 'Smart Budget | Nuwara Eliya Garment Sector',
        'hero_h2' => 'Master Your Money, <br><span style="color:#2563eb">Build Your Future.</span>',
        'hero_p' => 'Specifically designed for the hardworking garment sector. Securely track your daily expenses, manage Seettu groups, and monitor your EPF/ETF contributions.',
        'loan_guard' => 'Loan Guard',
        'loan_p' => 'Advanced alerts when total debt repayments exceed 30% of your income.',
        'seettu' => 'Seettu Tracker',
        'seettu_p' => 'Digital management for rotating savings groups common in factory floors.',
        'tab_login' => 'Sign In',
        'tab_register' => 'Register',
        'nic' => 'NIC Number',
        'password' => 'Password',
        'full_name' => 'Full Name',
        'factory' => 'Factory (e.g. Hirdaramani)',
        'dept' => 'Department',
        'role_select' => 'Choose Your Access Level',
        'role_user' => 'Employee (User)',
        'role_super' => 'Factory Supervisor',
        'role_admin' => 'System Administrator',
        'btn_login' => 'Access Dashboard',
        'btn_register' => 'Join System',
        'stats_tables' => 'Financial Tables',
        'stats_privacy' => 'Data Privacy',
        'stats_lang' => 'Supported Languages',
        'stats_access' => 'Access Anywhere',
        'feat_h3' => 'Engineered for Your Reality',
        'feat1_h4' => 'Pay-Day Planner',
        'feat1_p' => 'Automatically split your salary into rent, food, and savings.',
        'feat2_h4' => 'EPF/ETF Tracker',
        'feat2_p' => 'Visualize your compulsory retirement savings and long-term health.',
        'feat3_h4' => 'Financial Literacy',
        'feat3_p' => 'Daily personalized tips to help you avoid predatory lenders.'
    ],
    'si' => [
        'title' => 'Smart Budget | නුවරඑළිය ඇඟලුම් අංශය',
        'hero_h2' => 'ඔබේ මුදල් පාලනය කරන්න, <br><span style="color:#2563eb">අනාගතය ගොඩනඟන්න.</span>',
        'hero_p' => 'ඇඟලුම් ක්ෂේත්‍රය සඳහාම විශේෂයෙන් සකසා ඇත. ඔබේ දෛනික වියදම්, සීට්ටු කණ්ඩායම් සහ EPF/ETF දායකත්වයන් ආරක්ෂිතව නිරීක්ෂණය කරන්න.',
        'loan_guard' => 'ණය ආරක්ෂක',
        'loan_p' => 'ඔබේ ආදායමෙන් 30%කට වඩා ණය වාරික වැඩි වූ විට අනතුරු ඇඟවීම් ලබා දෙයි.',
        'seettu' => 'සීට්ටු ට්‍රැකර්',
        'seettu_p' => 'ඇඟලුම් කම්හල් වල බහුලව දක්නට ලැබෙන සීට්ටු ක්‍රමය ඩිජිටල් ලෙස කළමනාකරණය කරන්න.',
        'tab_login' => 'ඇතුල් වන්න',
        'tab_register' => 'ලියාපදිංචි වන්න',
        'nic' => 'හැඳුනුම්පත් අංකය',
        'password' => 'මුරපදය',
        'full_name' => 'සම්පූර්ණ නම',
        'factory' => 'ආයතනය (උදා: හිරදරාමනී)',
        'dept' => 'අංශය',
        'role_select' => 'ඔබේ තනතුර තෝරන්න',
        'role_user' => 'සේවක (පරිශීලක)',
        'role_super' => 'අංශ ප්‍රධානී (Supervisor)',
        'role_admin' => 'පද්ධති පරිපාලක',
        'btn_login' => 'පද්ධතියට පිවිසෙන්න',
        'btn_register' => 'ගිණුමක් සාදන්න',
        'stats_tables' => 'මූල්‍ය වගු',
        'stats_privacy' => 'දත්ත රහස්‍යතාව',
        'stats_lang' => 'භාෂා සහය',
        'stats_access' => 'ඕනෑම තැනක සිට',
        'feat_h3' => 'ඔබේ අවශ්‍යතාවයට ගැලපෙන ලෙස',
        'feat1_h4' => 'වැටුප් සැලසුම්කරු',
        'feat1_p' => 'ඔබේ වැටුප ලැබුණු සැනින් ආහාර, කුලී සහ ඉතිරිකිරීම් වලට වෙන් කරන්න.',
        'feat2_h4' => 'EPF/ETF ට්‍රැකර්',
        'feat2_p' => 'ඔබේ විශ්‍රාම ඉතිරිකිරීම් සහ දිගුකාලීන මූල්‍ය සෞඛ්‍යය නිරීක්ෂණය කරන්න.',
        'feat3_h4' => 'මූල්‍ය සාක්ෂරතාවය',
        'feat3_p' => 'අනවශ්‍ය ණය උගුල් වලින් බේරීමට දිනපතා උපදෙස් ලබා ගන්න.'
    ],
    'ta' => [
        'title' => 'Smart Budget | நுவரெலியா ஆடைத் துறை',
        'hero_h2' => 'உங்கள் பணத்தை மேலாண்மை செய்யுங்கள், <br><span style="color:#2563eb">எதிர்காலத்தை உருவாக்குங்கள்.</span>',
        'hero_p' => 'ஆடைத் தொழிலாளர்களுக்காக பிரத்யேகமாக வடிவமைக்கப்பட்டது. உங்கள் செலவுகள் மற்றும் சீட்டு சேமிப்புகளை பாதுகாப்பாக கண்காணிக்கவும்.',
        'loan_guard' => 'கடன் பாதுகாப்பு',
        'loan_p' => 'உங்கள் வருமானத்தில் 30% க்கும் அதிகமான கடன்கள் இருந்தால் எச்சரிக்கை செய்யும்.',
        'seettu' => 'சீட்டு டிராக்கர்',
        'seettu_p' => 'தொழிற்சாலைகளில் உள்ள சீட்டு சேமிப்பு முறையை டிஜிட்டல் முறையில் நிர்வகிக்கவும்.',
        'tab_login' => 'உள்நுழைக',
        'tab_register' => 'பதிவு செய்க',
        'nic' => 'அடையாள அட்டை இலக்கம்',
        'password' => 'கடவுச்சொல்',
        'full_name' => 'முழு பெயர்',
        'factory' => 'தொழிற்சாலை பெயர்',
        'dept' => 'துறை',
        'role_select' => 'உங்கள் நிலையைத் தேர்ந்தெடுக்கவும்',
        'role_user' => 'ஊழியர் (பயனர்)',
        'role_super' => 'மேற்பார்வையாளர்',
        'role_admin' => 'நிர்வாகி',
        'btn_login' => 'உள்நுழையவும்',
        'btn_register' => 'கணக்கை உருவாக்குங்கள்',
        'stats_tables' => 'நிதி அட்டவணைகள்',
        'stats_privacy' => 'தரவு தனியுரிமை',
        'stats_lang' => 'ஆதரிக்கப்படும் மொழிகள்',
        'stats_access' => 'எங்கும் அணுகலாம்',
        'feat_h3' => 'உங்கள் தேவைகளுக்காக உருவாக்கப்பட்டது',
        'feat1_h4' => 'சம்பள திட்டமிடுபவர்',
        'feat1_p' => 'சம்பளம் கிடைத்தவுடன் வாடகை, உணவு மற்றும் சேமிப்பு என பிரிக்கவும்.',
        'feat2_h4' => 'EPF/ETF டிராக்கர்',
        'feat2_p' => 'உங்கள் ஓய்வூதிய சேமிப்பை இலகுவாகக் கண்காணிக்கலாம்.',
        'feat3_h4' => 'நிதி அறிவு',
        'feat3_p' => 'கடன் பொறிகளில் இருந்து தப்பிக்க தினசரி ஆலோசனைகளைப் பெறுங்கள்.'
    ]
];

// 2. Language Switch Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['lang'] ?? 'en';
$text = $translations[$current_lang];
?>