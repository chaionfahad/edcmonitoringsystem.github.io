<?php
session_start();

// Language functions
function __($key) {
    static $langData = [
        'login_title' => ['en' => 'Login', 'bn' => 'লগইন'],
        'app_subtitle' => ['en' => 'ISP Monitoring System', 'bn' => 'আইএসপি মনিটরিং সিস্টেম'],
        'invalid_credentials' => ['en' => 'Invalid username or password.', 'bn' => 'ভুল ব্যবহারকারীর নাম বা পাসওয়ার্ড।'],
        'username' => ['en' => 'Username', 'bn' => 'ব্যবহারকারীর নাম'],
        'password' => ['en' => 'Password', 'bn' => 'পাসওয়ার্ড'],
        'login' => ['en' => 'Sign In', 'bn' => 'সাইন ইন'],
        'default_creds' => ['en' => '', 'bn' => ''],
        'toggle_lang' => ['en' => 'বাংলা', 'bn' => 'English'],
    ];
    $currentLang = isset($_COOKIE['edc_lang']) && $_COOKIE['edc_lang'] === 'bn' ? 'bn' : 'en';
    return isset($langData[$key][$currentLang]) ? $langData[$key][$currentLang] : $key;
}

function currentLang() {
    return isset($_COOKIE['edc_lang']) && $_COOKIE['edc_lang'] === 'bn' ? 'bn' : 'en';
}

function currentTheme() {
    $valid = ['light', 'dark', 'purple'];
    $theme = isset($_COOKIE['edc_theme']) ? $_COOKIE['edc_theme'] : 'light';
    return in_array($theme, $valid) ? $theme : 'light';
}

// Handle theme toggle
if (isset($_GET['theme'])) {
    $valid = ['light', 'dark', 'purple'];
    $theme = in_array($_GET['theme'], $valid) ? $_GET['theme'] : 'light';
    setcookie('edc_theme', $theme, time() + 86400 * 365, '/');
    header('Location: login.php');
    exit;
}

// Handle language toggle
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'] === 'bn' ? 'bn' : 'en';
    setcookie('edc_lang', $lang, time() + 86400 * 365, '/');
    header('Location: login.php');
    exit;
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: vendor/dashboard.php');
    }
    exit;
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        require_once __DIR__ . '/includes/db.php';
        $db = Database::getInstance();
        $user = $db->fetch("SELECT * FROM users WHERE username = ? AND status = 1", [$username]);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_super_admin'] = !empty($user['is_super_admin']);

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: vendor/dashboard.php');
            }
            exit;
        } else {
            $error = __('invalid_credentials');
        }
    } catch (Exception $e) {
        $error = "Cannot connect to database. Please ensure MySQL is running.";
    }
}

// Theme backgrounds
$themeBg = [
    'light' => 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%)',
    'dark' => 'linear-gradient(135deg, #0a0a14 0%, #16162a 50%, #0a0a14 100%)',
    'purple' => 'linear-gradient(135deg, #3b0764 0%, #581c87 50%, #3b0764 100%)',
];
$bg = $themeBg[currentTheme()];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('login_title') ?> - EDC Monitoring System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1e0033 0%, #0f0a2e 25%, #1a0533 50%, #0d0a2a 75%, #1a0033 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Animated orbs */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
        }
        body::before {
            width: 500px; height: 500px;
            background: #7c3aed;
            top: -150px; right: -100px;
            animation: floatOrb 12s ease-in-out infinite;
        }
        body::after {
            width: 400px; height: 400px;
            background: #2563eb;
            bottom: -100px; left: -80px;
            animation: floatOrb 10s ease-in-out infinite reverse;
        }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(40px, -40px) scale(1.1); }
        }

        .top-corner {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            display: flex;
            gap: 0.5rem;
            z-index: 100;
        }

        .top-corner-inner {
            display: flex;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 9999px;
            border: 1px solid rgba(255,255,255,0.12);
        }

        .lang-btn {
            padding: 0;
            background: transparent;
            border: none;
            box-shadow: none;
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            cursor: pointer;
            transition: color 0.2s;
        }

        .lang-btn:hover { color: #fff; }

        /* ── Glass Card ── */
        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 360px;
            padding: 2rem 1.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.25), 0 4px 12px rgba(0,0,0,0.1);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .login-logo img {
            max-width: 160px;
            height: auto;
            display: inline-block;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
        }

        .login-title {
            text-align: center;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .login-title .edc { color: #f87171; }
        .login-title .project { color: #34d399; }

        .login-subtitle {
            text-align: center;
            margin: 0.15rem 0 1.25rem 0;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            background: linear-gradient(90deg, #ff0000, #ff8000, #ffff00, #00ff00, #0080ff, #8000ff, #ff0080, #ff0000);
            background-size: 400% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: rainbowShift 6s linear infinite;
        }

        @keyframes rainbowShift {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        /* ── Form ── */
        .form-group { margin-bottom: 1rem; }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-bottom: 0.35rem;
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            pointer-events: none;
            transition: color 0.2s;
        }

        .input-wrap input {
            width: 100%;
            padding: 0.65rem 0.75rem 0.65rem 2.2rem;
            font-size: 0.88rem;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 10px;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
        }

        .input-wrap input::placeholder {
            color: rgba(255,255,255,0.25);
            font-weight: 400;
        }

        .input-wrap input:focus {
            border-color: rgba(124, 58, 237, 0.6);
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15), 0 0 20px rgba(124, 58, 237, 0.08);
            background: rgba(255,255,255,0.08);
        }

        .input-wrap:focus-within .icon {
            color: rgba(124, 58, 237, 0.6);
        }

        /* ── Button ── */
        .btn {
            width: 100%;
            padding: 0.7rem;
            font-size: 0.9rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            color: #fff;
            background: linear-gradient(135deg, #7c3aed, #2563eb);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 16px rgba(124, 58, 237, 0.3);
            letter-spacing: 0.02em;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(124, 58, 237, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* ── Alert ── */
        .alert {
            padding: 0.55rem 0.85rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
            backdrop-filter: blur(8px);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* ── Developer ── */
        .dev-section {
            text-align: center;
            margin-top: 1.25rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .dev-label {
            font-size: 0.55rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255,255,255,0.2);
            margin-bottom: 0.3rem;
        }

        .dev-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.25);
            margin-bottom: 0.15rem;
        }

        .dev-row svg {
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="top-corner">
        <div class="top-corner-inner">
            <a href="?lang=<?= currentLang()=='bn'?'en':'bn' ?>" class="lang-btn"><?= __('toggle_lang') ?></a>
        </div>
    </div>

    <div class="login-card">
        <div class="login-logo">
            <img src="assets/images/HM_logo.png" alt="HM">
        </div>
        <h1 class="login-title"><span class="edc">EDC</span> <span class="project">Project</span></h1>
        <p class="login-subtitle">HM Communication</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label><?= __('username') ?></label>
                <div class="input-wrap">
                    <span class="icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" name="username" placeholder="<?= __('username') ?>" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label><?= __('password') ?></label>
                <div class="input-wrap">
                    <span class="icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" name="password" placeholder="<?= __('password') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn"><?= __('login') ?></button>
        </form>

        <div class="dev-section">
            <div class="dev-label">EDC Monitoring System</div>
            <div class="dev-row">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>ISP Monitoring System</span>
            </div>
        </div>
    </div>
</body>
</html>