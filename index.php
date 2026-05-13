<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: vendor/dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDC Monitoring System</title>
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

        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
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

        .hero {
            position: relative;
            z-index: 1;
            text-align: center;
            width: 100%;
            max-width: 520px;
            padding: 3rem 2rem;
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            box-shadow: 0 16px 48px rgba(0,0,0,0.3);
        }

        .hero-logo {
            margin-bottom: 1rem;
        }

        .hero-logo img {
            max-width: 140px;
            height: auto;
            filter: drop-shadow(0 4px 16px rgba(0,0,0,0.3));
        }

        .hero h1 {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #fff;
            margin-bottom: 0.25rem;
        }

        .hero h1 .edc { color: #f87171; }
        .hero h1 .project { color: #34d399; }

        .hero .tagline {
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: linear-gradient(90deg, #ff0000, #ff8000, #ffff00, #00ff00, #0080ff, #8000ff, #ff0080, #ff0000);
            background-size: 400% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: rainbowShift 6s linear infinite;
            margin-bottom: 1.5rem;
        }

        @keyframes rainbowShift {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        .hero .description {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
            line-height: 1.6;
            margin-bottom: 2rem;
            font-weight: 400;
        }

        .hero .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 2rem;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            color: #fff;
            background: linear-gradient(135deg, #7c3aed, #2563eb);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(124,58,237,0.3);
        }

        .hero .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(124,58,237,0.45);
        }

        .hero .btn-login svg {
            transition: transform 0.2s;
        }

        .hero .btn-login:hover svg {
            transform: translateX(3px);
        }

        .hero .footer-text {
            margin-top: 2rem;
            font-size: 0.6rem;
            color: rgba(255,255,255,0.15);
            letter-spacing: 0.04em;
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-logo">
            <img src="assets/images/HM_logo.png" alt="HM">
        </div>
        <h1><span class="edc">EDC</span> <span class="project">Project</span></h1>
        <div class="tagline">HM Communication</div>
        <p class="description">
            ISP Monitoring System — real-time tracking of institutional<br>
            connectivity via MikroTik routers with live status and logging.
        </p>
        <a href="login.php" class="btn-login">
            Get Started
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
        </a>
        <div class="footer-text">EDC Monitoring System v2.0</div>
    </div>
</body>
</html>
