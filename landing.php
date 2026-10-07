<?php
// Smart Water Tank Monitoring System - Public Landing Page
// Displays public overview, hardware architecture, and telemetry data flow before login.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

start_secure_session();

// If user already has an active session, redirect straight to dashboard
if (is_authenticated()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Water Tank Monitoring System - IoT Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Landing Page Specific Polished Layout */
        .landing-body {
            background-color: #07172B;
            color: #FFFFFF;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .landing-nav {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 10;
        }

        .landing-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: #FFFFFF;
        }

        .landing-nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .landing-nav-link {
            color: #9AA0B4;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .landing-nav-link:hover {
            color: #FFFFFF;
        }

        .btn-nav-login {
            background: linear-gradient(135deg, #3C7EDA 0%, #1E5AB3 100%);
            color: #FFFFFF;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(60, 126, 218, 0.35);
        }

        .btn-nav-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(60, 126, 218, 0.5);
            color: #FFFFFF;
        }

        /* Hero Section */
        .landing-hero {
            position: relative;
            padding: 80px 32px 100px 32px;
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 60px;
            align-items: center;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 30px;
            background: rgba(60, 126, 218, 0.15);
            border: 1px solid rgba(60, 126, 218, 0.3);
            color: #72A9F5;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 24px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 900;
            line-height: 1.15;
            margin-bottom: 20px;
            letter-spacing: -0.5px;
            background: linear-gradient(180deg, #FFFFFF 0%, #CBD5E1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: 19px;
            color: #9AA0B4;
            line-height: 1.6;
            margin-bottom: 36px;
            max-width: 580px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #3C7EDA 0%, #1653AA 100%);
            color: #FFFFFF;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(60, 126, 218, 0.4);
            transition: all 0.25s ease;
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(60, 126, 218, 0.6);
            color: #FFFFFF;
        }

        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #CBD5E1;
            padding: 16px 28px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.12);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* Hero Preview Card */
        .hero-preview-container {
            background: linear-gradient(180deg, #0B233E 0%, #0E294B 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            position: relative;
        }

        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .preview-tank-box {
            background: #07172B;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 20px;
        }

        .preview-metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .preview-metric-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 14px;
        }

        .preview-metric-card .label {
            font-size: 11px;
            font-weight: 700;
            color: #9AA0B4;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .preview-metric-card .val {
            font-size: 20px;
            font-weight: 800;
            color: #FFFFFF;
        }

        /* In-Place Hero Login Card Styles */
        .hero-login-container {
            background: linear-gradient(180deg, #0B233E 0%, #0E294B 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            position: relative;
            animation: fadeInHeroCard 0.25s ease-in-out;
        }

        @keyframes fadeInHeroCard {
            from {
                opacity: 0;
                transform: translateY(6px) scale(0.99);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .login-card-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-close-login {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: #9AA0B4;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-close-login:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #FFFFFF;
        }

        .hero-form-group {
            margin-bottom: 16px;
        }

        .hero-form-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #CBD5E1;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .hero-login-input {
            width: 100%;
            padding: 12px 14px;
            background: #07172B;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .hero-login-input:focus {
            outline: none;
            border-color: #3C7EDA;
            box-shadow: 0 0 0 3px rgba(60, 126, 218, 0.25);
        }

        .btn-hero-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #3C7EDA 0%, #1653AA 100%);
            border: none;
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(60, 126, 218, 0.35);
            transition: all 0.2s ease;
            margin-top: 6px;
        }

        .btn-hero-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(60, 126, 218, 0.55);
        }

        .btn-hero-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .hero-login-alert {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-state-error {
            background: rgba(220, 60, 60, 0.15);
            border: 1px solid rgba(220, 60, 60, 0.35);
            color: #FFA4A4;
        }

        .alert-state-info {
            background: rgba(60, 126, 218, 0.15);
            border: 1px solid rgba(60, 126, 218, 0.35);
            color: #93C5FD;
        }

        .btn-toggle-back {
            background: none;
            border: none;
            color: #9AA0B4;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-toggle-back:hover {
            color: #FFFFFF;
        }

        /* Sections General */
        .landing-section {
            padding: 80px 32px;
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
        }

        .section-header {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 56px auto;
        }

        .section-header .section-tag {
            color: #3C7EDA;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .section-header h2 {
            font-size: 36px;
            font-weight: 800;
            color: #FFFFFF;
            margin-bottom: 16px;
        }

        .section-header p {
            color: #9AA0B4;
            font-size: 16px;
            line-height: 1.6;
        }

        /* System Flow Diagram */
        .flow-container {
            background: #0B233E;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.3);
        }

        .flow-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            flex-wrap: wrap;
            gap: 20px;
        }

        .flow-step-item {
            flex: 1;
            min-width: 160px;
            background: #07172B;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 24px 18px;
            text-align: center;
            position: relative;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .flow-step-item:hover {
            transform: translateY(-4px);
            border-color: rgba(60, 126, 218, 0.4);
        }

        .flow-step-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: rgba(60, 126, 218, 0.15);
            color: #3C7EDA;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px auto;
        }

        .flow-step-title {
            font-size: 15px;
            font-weight: 800;
            color: #FFFFFF;
            margin-bottom: 6px;
        }

        .flow-step-desc {
            font-size: 12px;
            color: #9AA0B4;
            line-height: 1.4;
        }

        .flow-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3C7EDA;
            font-size: 20px;
            opacity: 0.6;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background: #0B233E;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 18px;
            padding: 28px;
            transition: all 0.25s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(60, 126, 218, 0.35);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .feature-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: rgba(60, 126, 218, 0.12);
            color: #3C7EDA;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 17px;
            font-weight: 700;
            color: #FFFFFF;
            margin-bottom: 10px;
        }

        .feature-desc {
            font-size: 14px;
            color: #9AA0B4;
            line-height: 1.55;
        }

        /* Footer */
        .landing-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding: 40px 32px;
            text-align: center;
            margin-top: auto;
            background: #05101E;
        }

        .landing-footer p {
            color: #686C84;
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .landing-hero {
                grid-template-columns: 1fr;
                gap: 40px;
                padding-top: 40px;
            }
            .flow-arrow {
                display: none;
            }
        }
    </style>
</head>
<body class="landing-body">

    <!-- Top Navigation Bar -->
    <header class="landing-nav">
        <a href="landing.php" class="landing-brand">
            <div class="brand-icon-wrapper">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
            </div>
            <div class="brand-text">
                <h2 style="font-size: 17px; font-weight: 800; letter-spacing: 0.5px;">SMART TANK</h2>
                <p style="font-size: 11px; color: #9AA0B4; font-weight: 600;">Water Monitoring System</p>
            </div>
        </a>

        <div class="landing-nav-links">
            <a href="#system-info" class="landing-nav-link">Overview</a>
            <a href="#system-flow" class="landing-nav-link">Architecture Flow</a>
            <a href="#key-features" class="landing-nav-link">Key Features</a>
            <button type="button" class="btn-nav-login" id="nav-login-btn" style="cursor: pointer; border: none;">
                <span>Login</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                    <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="landing-hero">
        <div class="hero-left">
            <div class="hero-badge">
                <span class="pulse-dot" style="background:#3C925E; width:8px; height:8px; border-radius:50%; display:inline-block;"></span>
                <span>ESP32 IoT Solution</span>
            </div>

            <h1 class="hero-title">Smart Water Tank Monitoring System</h1>
            <p class="hero-subtitle">Real-time water level monitoring and intelligent alerts using ESP32.</p>

            <div class="hero-actions">
                <button type="button" class="btn-hero-primary" id="btn-hero-login" style="cursor: pointer; border: none;">
                    <span>Login</span>
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                    </svg>
                </button>
                <a href="#system-info" class="btn-hero-secondary" id="btn-hero-info">
                    <span>View System Information</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Demonstration Preview Panel & In-Place Login Modal -->
        <div class="hero-right">
            <!-- 1. The Preview Card (shown by default, as shown in user's photo) -->
            <div class="hero-preview-container" id="hero-preview-card">
                <div class="preview-header">
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #72A9F5; text-transform: uppercase;">Node Status</div>
                        <div style="font-size: 15px; font-weight: 700;">Main Tank Node 1</div>
                    </div>
                    <div style="background: rgba(60, 146, 94, 0.15); border: 1px solid rgba(60, 146, 94, 0.3); color: #51CF66; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #51CF66;"></span>
                        <span>ONLINE</span>
                    </div>
                </div>

                <div class="preview-tank-box">
                    <div style="font-size: 42px; font-weight: 900; color: #3C7EDA; margin-bottom: 2px;">72%</div>
                    <div style="font-size: 12px; font-weight: 700; color: #51CF66; text-transform: uppercase; letter-spacing: 0.5px;">Status: HIGH</div>
                    <div style="margin-top: 14px; background: rgba(255,255,255,0.06); height: 8px; border-radius: 4px; overflow: hidden;">
                        <div style="background: linear-gradient(90deg, #3C7EDA, #51CF66); width: 72%; height: 100%;"></div>
                    </div>
                </div>

                <div class="preview-metrics-grid">
                    <div class="preview-metric-card">
                        <div class="label">Estimated Volume</div>
                        <div class="val">720 L</div>
                    </div>
                    <div class="preview-metric-card">
                        <div class="label">Tank Capacity</div>
                        <div class="val">1,000 L</div>
                    </div>
                    <div class="preview-metric-card">
                        <div class="label">Sensor Depth</div>
                        <div class="val">72.0 cm</div>
                    </div>
                    <div class="preview-metric-card">
                        <div class="label">Alert Status</div>
                        <div class="val" style="color: #51CF66;">NORMAL</div>
                    </div>
                </div>
            </div>

            <!-- 2. The Login Card (smoothly replaces preview card when user clicks Login) -->
            <div class="hero-login-container" id="hero-login-card" style="display: none;">
                <div class="login-card-header">
                    <div class="login-card-brand">
                        <div class="brand-icon-wrapper" style="width: 38px; height: 38px; border-radius: 10px;">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size: 16px; font-weight: 800; color: #FFFFFF; margin: 0;">Sign In</h3>
                            <p style="font-size: 11px; color: #9AA0B4; margin: 2px 0 0 0;">Monitoring System Access</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close-login" id="btn-close-login" title="Return to Preview">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </div>

                <div id="login-alert-box" class="hero-login-alert alert-state-error" style="display: none;">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="flex-shrink: 0;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                    <span id="login-alert-msg"></span>
                </div>

                <form id="hero-login-form" method="POST" action="login.php">
                    <div class="hero-form-group">
                        <label for="modal-username" class="hero-form-label">Username</label>
                        <input 
                            type="text" 
                            id="modal-username" 
                            name="username" 
                            class="hero-login-input" 
                            placeholder="Enter username" 
                            required 
                            autocomplete="username"
                        >
                    </div>

                    <div class="hero-form-group">
                        <label for="modal-password" class="hero-form-label">Password</label>
                        <input 
                            type="password" 
                            id="modal-password" 
                            name="password" 
                            class="hero-login-input" 
                            placeholder="Enter password" 
                            required 
                            autocomplete="current-password"
                        >
                    </div>

                    <button type="submit" class="btn-hero-submit" id="btn-modal-submit">
                        <span id="btn-modal-submit-text">Sign In to Dashboard</span>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" id="btn-modal-arrow">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                        </svg>
                    </button>
                </form>

                <div style="margin-top: 18px; text-align: center;">
                    <button type="button" class="btn-toggle-back" id="btn-toggle-back">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                        </svg>
                        <span>Back to System Status</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- System Explanation Section -->
    <section class="landing-section" id="system-info">
        <div class="section-header">
            <div class="section-tag">System Overview</div>
            <h2>Intelligent Water Reservoir Management</h2>
            <p>
                The system monitors the water level of a tank using a water-level sensor connected to an ESP32. The collected data is processed and displayed through a web dashboard with real-time status, estimated water volume, alerts, and device monitoring.
            </p>
        </div>

        <!-- System Architecture Flow -->
        <div class="flow-container" id="system-flow">
            <div style="text-align: center; margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.5px;">DATA PROCESSING & TELEMETRY FLOW</h3>
                <p style="font-size: 13px; color: #9AA0B4;">End-to-end signal acquisition from hardware to responsive dashboard</p>
            </div>

            <div class="flow-steps">
                <!-- Step 1 -->
                <div class="flow-step-item">
                    <div class="flow-step-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                        </svg>
                    </div>
                    <div class="flow-step-title">Water Level Sensor</div>
                    <div class="flow-step-desc">Submersible analog probe immersed in water tank</div>
                </div>

                <div class="flow-arrow">➔</div>

                <!-- Step 2 -->
                <div class="flow-step-item">
                    <div class="flow-step-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M4 6h16v12H4z M6 2v2 M18 2v2 M6 20v2 M18 20v2"/>
                        </svg>
                    </div>
                    <div class="flow-step-title">ESP32</div>
                    <div class="flow-step-desc">Samples ADC pin, calculates level, drives LEDs & buzzer</div>
                </div>

                <div class="flow-arrow">➔</div>

                <!-- Step 3 -->
                <div class="flow-step-item">
                    <div class="flow-step-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4z"/>
                        </svg>
                    </div>
                    <div class="flow-step-title">Wi-Fi</div>
                    <div class="flow-step-desc">Transmits encrypted telemetry JSON over 802.11 b/g/n</div>
                </div>

                <div class="flow-arrow">➔</div>

                <!-- Step 4 -->
                <div class="flow-step-item">
                    <div class="flow-step-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                        </svg>
                    </div>
                    <div class="flow-step-title">Backend / API</div>
                    <div class="flow-step-desc">PHP REST service validates API key and thresholds</div>
                </div>

                <div class="flow-arrow">➔</div>

                <!-- Step 5 -->
                <div class="flow-step-item">
                    <div class="flow-step-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 4.02 2 6.5s4.48 4.5 10 4.5 10-2.02 10-4.5S17.52 2 12 2zm0 6.5c-4.41 0-8-1.34-8-2.5s3.59-2.5 8-2.5 8 1.34 8 2.5-3.59 2.5-8 2.5z"/>
                        </svg>
                    </div>
                    <div class="flow-step-title">Database</div>
                    <div class="flow-step-desc">MySQL records sensor readings, alerts, & logs</div>
                </div>

                <div class="flow-arrow">➔</div>

                <!-- Step 6 -->
                <div class="flow-step-item">
                    <div class="flow-step-icon">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                        </svg>
                    </div>
                    <div class="flow-step-title">Web Dashboard</div>
                    <div class="flow-step-desc">Real-time live monitoring, analytics, and control</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Key Features Section -->
    <section class="landing-section" id="key-features" style="padding-top: 0;">
        <div class="section-header">
            <div class="section-tag">Capabilities</div>
            <h2>Key System Features</h2>
            <p>Comprehensive monitoring engineered for reliability, safety, and operational insight.</p>
        </div>

        <div class="features-grid">
            <!-- 1. Real-Time Water Level Monitoring -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Real-Time Water Level Monitoring</h3>
                <p class="feature-desc">Continuous immersion sensing streams real-time liquid heights directly to the operations console.</p>
            </div>

            <!-- 2. Water Level Percentage -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M7.5 11C9.43 11 11 9.43 11 7.5S9.43 4 7.5 4 4 5.57 4 7.5 5.57 11 7.5 11zm9 2c-1.93 0-3.5 1.57-3.5 3.5s1.57 3.5 3.5 3.5 3.5-1.57 3.5-3.5-1.57-3.5-3.5-3.5zM19.07 4.93L4.93 19.07l1.41 1.41L20.48 6.34l-1.41-1.41z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Water Level Percentage</h3>
                <p class="feature-desc">Accurate 0% to 100% full-scale percentage calculation calibrated dynamically across min/max ADC thresholds.</p>
            </div>

            <!-- 3. Estimated Water Volume -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Estimated Water Volume</h3>
                <p class="feature-desc">Converts percentage to actual liters based on configured tank capacity for instantaneous consumption insight.</p>
            </div>

            <!-- 4. Status Monitoring -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Status Monitoring</h3>
                <p class="feature-desc">Multi-tier threshold detection categorizes levels into HIGH, MEDIUM, LOW, and CRITICAL states automatically.</p>
            </div>

            <!-- 5. Critical Water Level Alerts -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Critical Water Level Alerts</h3>
                <p class="feature-desc">Triggers on-board active piezobuzzer alarm, blinking RED LED, and logs incident alerts to the database.</p>
            </div>

            <!-- 6. Device Monitoring -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Device Monitoring</h3>
                <p class="feature-desc">Watches ESP32 Wi-Fi telemetry with an automated heartbeat timer to instantly flag node offline events.</p>
            </div>

            <!-- 7. Sensor History -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                    </svg>
                </div>
                <h3 class="feature-title">Sensor History</h3>
                <p class="feature-desc">Interactive historical logs and Chart.js trend curves for analytical tracking across hourly and daily windows.</p>
            </div>

            <!-- 8. System Activity Logs -->
            <div class="feature-card">
                <div class="feature-icon-box">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </div>
                <h3 class="feature-title">System Activity Logs</h3>
                <p class="feature-desc">Comprehensive audit logging recording logins, sensor updates, alerts, settings adjustments, and node events.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <p>&copy; <?php echo date('Y'); ?> Smart Water Tank Monitoring System. Real-Time IoT Platform with ESP32.</p>
    </footer>

    <!-- Interactive In-Place Login Modal Script -->
    <script>
        const previewCard   = document.getElementById('hero-preview-card');
        const loginCard     = document.getElementById('hero-login-card');
        const navLoginBtn   = document.getElementById('nav-login-btn');
        const heroLoginBtn  = document.getElementById('hero-login-btn');
        const btnCloseLogin = document.getElementById('btn-close-login');
        const btnToggleBack = document.getElementById('btn-toggle-back');
        const loginForm     = document.getElementById('hero-login-form');
        const usernameInput = document.getElementById('modal-username');
        const passwordInput = document.getElementById('modal-password');
        const alertBox      = document.getElementById('login-alert-box');
        const alertMsg      = document.getElementById('login-alert-msg');
        const submitBtn     = document.getElementById('btn-modal-submit');
        const submitText    = document.getElementById('btn-modal-submit-text');

        function showLoginCard(errorText = null, isInfo = false) {
            if (previewCard) previewCard.style.display = 'none';
            if (loginCard) loginCard.style.display = 'block';

            if (errorText && alertBox && alertMsg) {
                alertBox.style.display = 'flex';
                alertBox.className = 'hero-login-alert ' + (isInfo ? 'alert-state-info' : 'alert-state-error');
                alertMsg.textContent = errorText;
            } else if (alertBox) {
                alertBox.style.display = 'none';
            }

            // Smooth scroll into view on small screens
            if (window.innerWidth <= 992 && loginCard) {
                loginCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            setTimeout(() => {
                if (usernameInput) usernameInput.focus();
            }, 100);
        }

        function hideLoginCard() {
            if (loginCard) loginCard.style.display = 'none';
            if (previewCard) previewCard.style.display = 'block';
            if (alertBox) alertBox.style.display = 'none';
            if (loginForm) loginForm.reset();
        }

        if (navLoginBtn) {
            navLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                showLoginCard();
            });
        }

        if (heroLoginBtn) {
            heroLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                showLoginCard();
            });
        }

        if (btnCloseLogin) {
            btnCloseLogin.addEventListener('click', hideLoginCard);
        }

        if (btnToggleBack) {
            btnToggleBack.addEventListener('click', hideLoginCard);
        }

        // Real Database Authentication via AJAX
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const username = usernameInput.value.trim();
                const password = passwordInput.value;

                if (!username || !password) {
                    showLoginCard('Please enter both username and password.');
                    return;
                }

                submitBtn.disabled = true;
                submitText.textContent = 'Authenticating...';

                fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ username, password })
                })
                .then(res => res.json())
                .then(res => {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Sign In to Dashboard';

                    if (res.success) {
                        submitText.textContent = 'Access Granted! Redirecting...';
                        window.location.href = res.redirect || 'dashboard.php';
                    } else {
                        showLoginCard(res.error || 'Invalid username or password.');
                        if (passwordInput) {
                            passwordInput.value = '';
                            passwordInput.focus();
                        }
                    }
                })
                .catch(() => {
                    submitBtn.disabled = false;
                    submitText.textContent = 'Sign In to Dashboard';
                    showLoginCard('Connection error. Please try again.');
                });
            });
        }

        // Check URL parameters on page load (e.g. ?login=1, ?expired=1)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('login') === '1' || window.location.hash === '#login') {
            const errorParam = urlParams.get('error');
            const expiredParam = urlParams.get('expired');
            if (errorParam) {
                showLoginCard(decodeURIComponent(errorParam));
            } else if (expiredParam === '1') {
                showLoginCard('Your session has expired. Please log in again.', true);
            } else {
                showLoginCard();
            }
        }
    </script>
</body>
</html>
