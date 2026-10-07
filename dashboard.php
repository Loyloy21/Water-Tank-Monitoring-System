<?php
// Smart Water Tank Monitoring System - Authenticated Operations Console
// Protected Dashboard: Requires authenticated session and prevents browser caching on back button.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// 1. Enforce authentication guard
require_auth(false);

// 2. Anti-caching headers so browser Back button after logout cannot expose dashboard
prevent_client_caching();

$currentUser = get_current_user_profile();

// 3. Log dashboard access once per session
if (empty($_SESSION['dashboard_logged'])) {
    log_activity('DASHBOARD_ACCESS', "User '{$currentUser['username']}' accessed the monitoring dashboard", 'AUTHENTICATION');
    $_SESSION['dashboard_logged'] = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Tank - Operations Console</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Chart.js for Live Graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- Core Application CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- ====================================================================
         1. SIDEBAR NAVIGATION
         ==================================================================== -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon-wrapper">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                </svg>
            </div>
            <div class="brand-text">
                <h2>SMART TANK</h2>
                <p>Water Monitoring System</p>
            </div>
        </div>

        <!-- Wireframe Separator Dots -->
        <div class="sidebar-dots">
            <span></span><span></span><span></span>
        </div>

        <ul class="nav-menu">
            <li class="nav-item active" data-page="dashboard">
                <a href="#dashboard">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    <span>DASHBOARD</span>
                </a>
            </li>
            <li class="nav-item" data-page="live-monitor">
                <a href="#live-monitor">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 4C7.31 4 3.07 5.9 0 8.98L12 21 24 8.98C20.93 5.9 16.69 4 12 4zm0 3.5c3.27 0 6.27 1.25 8.54 3.29L12 19.34 3.46 10.79C5.73 8.75 8.73 7.5 12 7.5z"/>
                    </svg>
                    <span>LIVE MONITOR</span>
                </a>
            </li>
            <li class="nav-item" data-page="history">
                <a href="#history">
                    <svg viewBox="0 0 24 24">
                        <path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                    </svg>
                    <span>HISTORY</span>
                </a>
            </li>
            <li class="nav-item" data-page="alerts">
                <a href="#alerts">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                    <span>ALERTS</span>
                    <span class="nav-badge" id="nav-alerts-badge" style="display:none;">0</span>
                </a>
            </li>
            <li class="nav-item" data-page="logs">
                <a href="#logs">
                    <svg viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    <span>SYSTEM LOGS</span>
                </a>
            </li>
            <li class="nav-item" data-page="settings">
                <a href="#settings">
                    <svg viewBox="0 0 24 24">
                        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                    </svg>
                    <span>SETTINGS</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn" style="text-decoration: none; display: flex; align-items: center; justify-content: center; width: 100%;">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                </svg>
                <span>LOGOUT</span>
            </a>
        </div>
    </aside>

    <!-- ====================================================================
         2. MAIN CONTENT WRAPPER
         ==================================================================== -->
    <main class="main-wrapper">

        <!-- Top Header Navigation -->
        <header class="top-header">
            <div class="header-left">
                <h1 class="header-title" id="header-page-title">DASHBOARD</h1>

                <!-- ESP32 Online/Offline Live Status Pill -->
                <div class="device-status-badge offline" id="device-status-badge">
                    <span class="pulse-dot"></span>
                    <span id="device-status-label">OFFLINE</span>
                    <span class="last-seen-text" id="device-last-seen">Last seen: --</span>
                </div>
            </div>

            <div class="header-actions">
                <!-- Developer Simulation Drawer Toggle -->
                <button class="sim-toggle-btn" id="toggle-sim-btn" title="Toggle Simulation Toolbar">
                    <span>🧪</span>
                    <span>Test Simulator</span>
                </button>

                <!-- Notification Bell -->
                <button class="header-icon-btn" id="header-bell-btn" title="View Alerts">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                    <span class="notification-count" id="header-notif-count" style="display:none;">0</span>
                </button>

                <!-- Interactive Profile Menu Container -->
                <div class="profile-dropdown-wrapper" style="position: relative; display: flex; align-items: center; gap: 10px;">
                    <!-- User Silhouette Button -->
                    <button class="header-icon-btn" id="header-user-icon-btn" title="Account: <?php echo htmlspecialchars($currentUser['username'] ?? 'admin'); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </button>

                    <!-- Wireframe Profile Pill (Solid Black/Navy Pill) -->
                    <div class="wf-profile-pill" id="user-profile-btn" style="cursor: pointer;" title="User Options">
                        <span>PROFILE ▾</span>
                    </div>

                    <!-- Floating Profile Dropdown Menu -->
                    <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                        <div class="profile-menu-header">
                            <div class="profile-avatar-icon">
                                <?php echo strtoupper(substr($currentUser['username'] ?? 'A', 0, 1)); ?>
                            </div>
                            <div class="profile-user-info">
                                <span class="profile-user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Administrator'); ?></span>
                                <span class="profile-user-role"><?php echo htmlspecialchars($currentUser['role'] ?? 'ADMIN'); ?></span>
                            </div>
                        </div>

                        <div class="profile-menu-divider"></div>

                        <div class="profile-menu-items">
                            <a href="#" class="profile-menu-item" data-profile-nav="settings">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
                                </svg>
                                <span>Settings</span>
                            </a>
                            <a href="#" class="profile-menu-item" data-profile-nav="logs">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                                </svg>
                                <span>Activity Logs</span>
                            </a>
                            <div class="profile-menu-divider"></div>
                            <a href="logout.php" class="profile-menu-item logout">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                                </svg>
                                <span>Logout Session</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- ================================================================
             DEVELOPER SIMULATION DRAWER
             ================================================================ -->
        <div class="sim-drawer" id="sim-drawer">
            <div class="sim-drawer-header">
                <div class="sim-drawer-title">
                    <span>⚡ DEVELOPER HARDWARE SIMULATOR</span>
                    <span style="font-size:11px; opacity:0.8; font-weight:500;">(Inject test telemetry without physical ESP32)</span>
                </div>
                <span style="font-size:12px; color:#A87FFB; font-weight:700;">Simulated readings post directly to API & DB</span>
            </div>

            <div class="sim-presets">
                <span style="font-size:12px; font-weight:700; color:#D9C3FD; margin-right:6px;">Demo Presets:</span>
                <button class="sim-btn sim-preset-btn" data-value="80">80% (High)</button>
                <button class="sim-btn sim-preset-btn" data-value="60">60% (Medium)</button>
                <button class="sim-btn sim-preset-btn" data-value="40">40% (Medium)</button>
                <button class="sim-btn sim-preset-btn" data-value="20">20% (Low Alert)</button>
                <button class="sim-btn sim-preset-btn" data-value="10">10% (Critical Alarm / Buzzer)</button>
            </div>

            <div class="sim-slider-row">
                <label for="sim-slider" style="font-size:13px; font-weight:700; color:#FFFFFF;">Water Level (%):</label>
                <input type="range" id="sim-slider" min="0" max="100" value="50" style="flex:1;">
                <span id="sim-slider-val" style="font-size:16px; font-weight:800; color:#53B1FD; min-width:45px;">50%</span>
                <button class="sim-btn" id="sim-apply-btn" style="background:#53B1FD; color:#0B233E; font-weight:800;">Send Telemetry</button>
                <button class="sim-btn" id="sim-auto-btn">Start Auto Cycle</button>
            </div>
        </div>

        <!-- ================================================================
             PAGE 1: DASHBOARD VIEW (Wireframe - 1)
             ================================================================ -->
        <section class="page-view active" id="view-dashboard">
            <div class="wf-section-subtitle">CURRENT WATER LEVEL</div>

            <!-- Wireframe 1 Grid Layout: Left Column & Right Column -->
            <div class="wf-dashboard-layout">
                
                <!-- Left Column: Big Tank Card + 2 Bottom Pale Blue Cards -->
                <div class="wf-left-col">
                    
                    <!-- Large White Card with Tank Render & Telemetry Details -->
                    <div class="wf-tank-card">
                        <div class="wf-tank-img-col">
                            <img src="assets/images/water_tank_model.jpg" alt="Smart Water Tank Model" class="wf-tank-image">
                        </div>

                        <div class="wf-tank-details-col">
                            <div class="wf-level-headline">
                                <span class="wf-pct-huge" id="dash-pct-huge">72%</span>
                                <span class="wf-status-badge high" id="dash-status-inline">HIGH</span>
                            </div>

                            <p class="wf-tank-subtitle" id="dash-tank-subtitle">
                                Water Level status is optimal but nearing maximum capacity.
                            </p>

                            <div class="wf-volume-section">
                                <span class="wf-volume-label">WATER VOLUME</span>
                                <div class="wf-volume-val">
                                    <span id="dash-volume-numbers">720 / 1000 L</span>
                                </div>
                                <div class="wf-progress-bar-track">
                                    <div class="wf-progress-bar-fill" id="dash-volume-bar-fill" style="width: 72%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row: 2 Pale Blue Metric Cards -->
                    <div class="wf-bottom-metrics-row">
                        <div class="wf-bottom-card">
                            <div class="wf-bottom-card-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="#3C7EDA">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                </svg>
                            </div>
                            <div class="wf-bottom-card-info">
                                <span class="wf-bottom-label">WATER LEVEL SENSOR / DISTANCE</span>
                                <strong class="wf-bottom-val" id="dash-sensor-val">38.4 cm</strong>
                            </div>
                        </div>

                        <div class="wf-bottom-card">
                            <div class="wf-bottom-card-icon">
                                <svg viewBox="0 0 24 24" width="22" height="22" fill="#3C7EDA">
                                    <path d="M15 13V5c0-1.66-1.34-3-3-3S9 3.34 9 5v8c-1.21.91-2 2.37-2 4 0 2.76 2.24 5 5 5s5-2.24 5-5c0-1.63-.79-3.09-2-4zm-4-8c0-.55.45-1 1-1s1 .45 1 1h-1v1h1v2h-1v1h1v2h-2V5z"/>
                                </svg>
                            </div>
                            <div class="wf-bottom-card-info">
                                <span class="wf-bottom-label">CURRENT VOLUME</span>
                                <strong class="wf-bottom-val" id="dash-current-volume-bottom">720 L</strong>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: 3 Stacked Status Cards -->
                <div class="wf-right-col">
                    
                    <!-- Card 1: TANK STATUS (Mint Green) -->
                    <div class="wf-status-card status-green">
                        <div class="wf-status-card-header">TANK STATUS</div>
                        <div class="wf-status-card-body">
                            <div class="wf-card-circle-icon icon-green">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                                </svg>
                            </div>
                            <div class="wf-status-card-text">
                                <div class="wf-card-title" id="card-tank-status-title">Normal</div>
                                <div class="wf-card-sub" id="card-tank-status-sub">All systems good</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: LAST UPDATED (Sky Blue) -->
                    <div class="wf-status-card status-blue">
                        <div class="wf-status-card-header">LAST UPDATED</div>
                        <div class="wf-status-card-body">
                            <div class="wf-card-circle-icon icon-blue">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/>
                                </svg>
                            </div>
                            <div class="wf-status-card-text">
                                <div class="wf-card-title" id="card-last-updated-date">May 28, 2025</div>
                                <div class="wf-card-sub" id="card-last-updated-time">11:20:45 AM</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: ALERT STATUS (Blush Red) -->
                    <div class="wf-status-card status-red">
                        <div class="wf-status-card-header">ALERT STATUS</div>
                        <div class="wf-status-card-body">
                            <div class="wf-card-circle-icon icon-red">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                                </svg>
                            </div>
                            <div class="wf-status-card-text">
                                <div class="wf-card-title" id="card-alert-status-title">No Alerts</div>
                                <div class="wf-card-sub" id="card-alert-status-sub">Zero system anomalies</div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================
             PAGE 2: LIVE MONITOR VIEW (Wireframe - 2)
             ================================================================ -->
        <section class="page-view" id="view-live-monitor">
            <div class="wf-section-subtitle">LIVE WATER LEVEL</div>

            <!-- Wireframe 2 Grid Layout -->
            <div class="wf-dashboard-layout">

                <!-- Left Column: Big Chart Card + Level Indicators -->
                <div class="wf-left-col">

                    <!-- Large White Chart Card -->
                    <div class="wf-live-chart-card">
                        
                        <!-- Top Row: Big % and Subtitle -->
                        <div class="wf-live-header-row">
                            <div class="wf-level-headline">
                                <span class="wf-pct-huge" id="live-pct-display">72%</span>
                                <span class="wf-status-badge high" id="live-status-badge">HIGH</span>
                            </div>
                            <p class="wf-tank-subtitle right-align" id="live-status-subtitle">
                                Water Level status is optimal but nearing maximum capacity.
                            </p>
                        </div>

                        <!-- Sub-header with droplet icon -->
                        <div class="wf-chart-meta-row">
                            <div class="wf-chart-meta-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="#FFFFFF">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                </svg>
                            </div>
                            <div class="wf-chart-meta-text">
                                <h4>Live Water Level</h4>
                                <p>Smart Water Tank Monitoring System</p>
                            </div>
                        </div>

                        <!-- Chart Canvas -->
                        <div class="wf-chart-wrapper">
                            <canvas id="liveWaterChart"></canvas>
                        </div>

                        <!-- Chart Legend Footer -->
                        <div class="wf-chart-footer">
                            <div class="wf-legend-item">
                                <span class="wf-legend-dot blue"></span>
                                <span>Data updated every 5 seconds</span>
                            </div>
                            <div class="wf-legend-item">
                                <span class="wf-legend-line blue"></span>
                                <span>Water level trend</span>
                            </div>
                        </div>

                    </div>

                    <!-- LEVEL INDICATORS Section -->
                    <div class="wf-indicators-section">
                        <div class="wf-section-subtitle" style="margin-top: 22px; margin-bottom: 12px;">LEVEL INDICATORS</div>
                        
                        <div class="wf-indicators-grid">
                            <!-- HIGH Card (Green) -->
                            <div class="wf-indicator-card high" id="indicator-card-high">
                                <div class="wf-indicator-icon-circle">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                    </svg>
                                </div>
                                <div class="wf-indicator-content">
                                    <span class="wf-indicator-title">HIGH</span>
                                    <span class="wf-indicator-range">70% - 100%</span>
                                </div>
                            </div>

                            <!-- MEDIUM Card (Yellow) -->
                            <div class="wf-indicator-card medium" id="indicator-card-med">
                                <div class="wf-indicator-icon-circle">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                    </svg>
                                </div>
                                <div class="wf-indicator-content">
                                    <span class="wf-indicator-title">MEDIUM</span>
                                    <span class="wf-indicator-range">30% - 69%</span>
                                </div>
                            </div>

                            <!-- LOW Card (Red) -->
                            <div class="wf-indicator-card low" id="indicator-card-low">
                                <div class="wf-indicator-icon-circle">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                    </svg>
                                </div>
                                <div class="wf-indicator-content">
                                    <span class="wf-indicator-title">LOW</span>
                                    <span class="wf-indicator-range">0% - 28%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column: 3 Stacked Status Cards for Live Monitor -->
                <div class="wf-right-col">

                    <!-- Card 1: ULTRASONIC DISTANCE (Mint Green) -->
                    <div class="wf-status-card status-green">
                        <div class="wf-status-card-header">ULTRASONIC DISTANCE</div>
                        <div class="wf-status-card-body">
                            <div class="wf-card-circle-icon icon-green">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                                </svg>
                            </div>
                            <div class="wf-status-card-text">
                                <div class="wf-card-title" id="live-sensor-val">8.4 cm</div>
                                <div class="wf-card-sub">Distance from water sensor to water surface</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: TANK CAPACITY (Sky Blue) -->
                    <div class="wf-status-card status-blue">
                        <div class="wf-status-card-header">TANK CAPACITY</div>
                        <div class="wf-status-card-body">
                            <div class="wf-card-circle-icon icon-blue">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                </svg>
                            </div>
                            <div class="wf-status-card-text">
                                <div class="wf-card-title" id="live-capacity-l">1000 L</div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: CURRENT VOLUME (Blush Red) -->
                    <div class="wf-status-card status-red">
                        <div class="wf-status-card-header">CURRENT VOLUME</div>
                        <div class="wf-status-card-body">
                            <div class="wf-card-circle-icon icon-red">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                                    <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                                </svg>
                            </div>
                            <div class="wf-status-card-text">
                                <div class="wf-card-title" id="live-volume-l">720 L</div>
                                <div class="wf-card-sub">Zero system anomalies</div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ================================================================
             PAGE 3: HISTORY VIEW
             ================================================================ -->
        <section class="page-view" id="view-history">
            <div class="history-filter-card">
                <div class="history-filter-row">
                    <div class="filter-group">
                        <label for="history-range-select">Time Range</label>
                        <select id="history-range-select" class="form-control">
                            <option value="today">Today</option>
                            <option value="week">Last 7 Days</option>
                            <option value="month">Last 30 Days</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button class="btn-secondary" id="history-refresh-btn">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                            </svg>
                            <span>Refresh</span>
                        </button>
                        <a href="api/history.php?filter=today&export=csv" id="history-export-btn" class="btn-primary" download>
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                            </svg>
                            <span>Export CSV</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- History Table -->
            <div class="table-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>DATE & TIME</th>
                                <th>LEVEL (%)</th>
                                <th>VOLUME (L)</th>
                                <th>DIST. (CM)</th>
                                <th>ADC</th>
                                <th>STATUS</th>
                                <th>NODE ID</th>
                            </tr>
                        </thead>
                        <tbody id="history-table-body">
                            <!-- Injected via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ================================================================
             PAGE 4: ALERTS VIEW
             ================================================================ -->
        <section class="page-view" id="view-alerts">
            <div class="alerts-header-card">
                <div class="alerts-header-row">
                    <div class="alerts-tab-group">
                        <button class="alerts-tab-btn active" data-filter="all">All Alerts</button>
                        <button class="alerts-tab-btn" data-filter="unacknowledged">Active</button>
                        <button class="alerts-tab-btn" data-filter="acknowledged">Acknowledged</button>
                    </div>

                    <button class="btn-secondary" id="alerts-ack-all-btn">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                            <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                        </svg>
                        <span>Acknowledge All</span>
                    </button>
                </div>
            </div>

            <div class="alerts-list" id="alerts-container">
                <!-- Injected via JavaScript -->
            </div>
        </section>

        <!-- ================================================================
             PAGE 5: SYSTEM & ACTIVITY LOGS VIEW
             ================================================================ -->
        <section class="page-view" id="view-logs">
            <div class="logs-header-card">
                <div class="logs-header-top">
                    <div>
                        <h2 class="logs-title">System & Activity Logs</h2>
                        <p class="logs-subtitle">Real-time audit trail of user sessions, sensor triggers, thresholds, and IoT hardware events.</p>
                    </div>
                    <button class="btn-refresh-logs" id="btn-refresh-logs" title="Refresh Logs">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                        </svg>
                        <span>Refresh Logs</span>
                    </button>
                </div>

                <!-- Event Category Filter Pills -->
                <div class="logs-type-pills" id="logs-type-pills">
                    <button class="type-pill active" data-type="ALL">All Events</button>
                    <button class="type-pill" data-type="AUTHENTICATION">Authentication</button>
                    <button class="type-pill" data-type="SENSOR">Sensor</button>
                    <button class="type-pill" data-type="ALERT">Alert</button>
                    <button class="type-pill" data-type="DEVICE">Device</button>
                    <button class="type-pill" data-type="SETTINGS">Settings</button>
                    <button class="type-pill" data-type="SYSTEM">System</button>
                    <button class="type-pill" data-type="ERROR">Error</button>
                </div>

                <!-- Search and Secondary Filters Toolbar -->
                <div class="logs-toolbar">
                    <div class="logs-search-wrapper">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" class="search-icon">
                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                        </svg>
                        <input type="text" id="logs-search-input" class="logs-search-input" placeholder="Search by action, description, user, device, or IP...">
                    </div>

                    <div class="logs-filters-group">
                        <select id="logs-filter-action" class="logs-select" title="Filter by Action">
                            <option value="ALL">All Actions</option>
                        </select>

                        <select id="logs-filter-user" class="logs-select" title="Filter by User">
                            <option value="ALL">All Users</option>
                        </select>

                        <input type="date" id="logs-filter-date" class="logs-date-input" title="Filter by Date">

                        <button id="logs-btn-reset" class="btn-reset-filters" title="Reset Filters">Reset</button>
                    </div>
                </div>
            </div>

            <!-- Logs Table Card -->
            <div class="logs-table-card">
                <div class="logs-table-responsive">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th style="width: 165px;">TIMESTAMP</th>
                                <th style="width: 145px;">EVENT TYPE</th>
                                <th style="width: 195px;">ACTION</th>
                                <th>DESCRIPTION</th>
                                <th style="width: 120px;">USER</th>
                                <th style="width: 140px;">DEVICE / IP</th>
                            </tr>
                        </thead>
                        <tbody id="logs-table-body">
                            <!-- Injected dynamically via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty / Loading State Containers -->
                <div id="logs-loading" class="logs-state-message" style="display:none;">
                    <div class="logs-spinner"></div>
                    <span>Loading system logs...</span>
                </div>
                <div id="logs-empty" class="logs-state-message" style="display:none;">
                    <span>No logs found matching the selected filters.</span>
                </div>

                <!-- Pagination Footer -->
                <div class="logs-pagination" id="logs-pagination">
                    <div class="logs-pagination-info" id="logs-pagination-info">
                        Showing 0 to 0 of 0 logs
                    </div>
                    <div class="logs-pagination-controls" id="logs-pagination-controls">
                        <!-- Injected via JavaScript -->
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             PAGE 6: SETTINGS VIEW
             ================================================================ -->
        <section class="page-view" id="view-settings">
            <div class="settings-grid">
                <!-- Settings Form -->
                <div class="settings-card">
                    <div class="card-title">TANK DIMENSIONS & SENSOR CALIBRATION</div>

                    <form id="settings-form">
                        <div class="settings-form-row">
                            <div class="form-group">
                                <label for="set-tank-height">Tank Height (cm)</label>
                                <input type="number" step="0.1" class="form-control" id="set-tank-height" required>
                            </div>
                            <div class="form-group">
                                <label for="set-tank-capacity">Tank Capacity (Liters)</label>
                                <input type="number" step="1" class="form-control" id="set-tank-capacity" required>
                            </div>
                        </div>

                        <div class="settings-form-row">
                            <div class="form-group">
                                <label for="set-sensor-min">Sensor Min ADC (0% Level)</label>
                                <input type="number" class="form-control" id="set-sensor-min" placeholder="500" required>
                            </div>
                            <div class="form-group">
                                <label for="set-sensor-max">Sensor Max ADC (100% Level)</label>
                                <input type="number" class="form-control" id="set-sensor-max" placeholder="2800" required>
                            </div>
                        </div>

                        <div class="settings-form-row">
                            <div class="form-group">
                                <label for="set-high-thresh">High Threshold (%)</label>
                                <input type="number" step="1" class="form-control" id="set-high-thresh" required>
                            </div>
                            <div class="form-group">
                                <label for="set-med-thresh">Medium Threshold (%)</label>
                                <input type="number" step="1" class="form-control" id="set-med-thresh" required>
                            </div>
                        </div>

                        <div class="settings-form-row">
                            <div class="form-group">
                                <label for="set-crit-thresh">Critical Threshold (%)</label>
                                <input type="number" step="1" class="form-control" id="set-crit-thresh" required>
                            </div>
                            <div class="form-group">
                                <label for="set-device-name">Device Name</label>
                                <input type="text" class="form-control" id="set-device-name" required>
                            </div>
                        </div>

                        <div class="settings-form-row">
                            <div class="form-group">
                                <label for="set-interval">ESP32 Interval (Seconds)</label>
                                <input type="number" class="form-control" id="set-interval" required>
                            </div>
                            <div class="form-group">
                                <label for="set-offline-timeout">Offline Timeout (Seconds)</label>
                                <input type="number" class="form-control" id="set-offline-timeout" required>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label for="set-api-key">API Secret Key (ESP32 Auth)</label>
                            <input type="text" class="form-control" id="set-api-key" required>
                        </div>

                        <button type="submit" class="btn-primary">
                            <span>Save Configuration</span>
                        </button>
                    </form>
                </div>

                <!-- Calibration & Calculation Guide -->
                <div class="guide-card">
                    <div class="card-title">CALIBRATION FORMULA</div>

                    <div class="guide-step">
                        <div class="step-num">1</div>
                        <div class="step-content">
                            <h5>Water Level Sensor</h5>
                            <p>Analog probe generates variable voltage on ESP32 ADC pin proportional to water immersion.</p>
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-num">2</div>
                        <div class="step-content">
                            <h5>Calibration Mapping</h5>
                            <p><code>Lowest Sensor Value = 0%</code> (Dry), <code>Highest Sensor Value = 100%</code> (Submerged).</p>
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-num">3</div>
                        <div class="step-content">
                            <h5>Water Percentage (%)</h5>
                            <p><code>waterPercentage = ((rawADC - min) / (max - min)) × 100</code></p>
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-num">4</div>
                        <div class="step-content">
                            <h5>Estimated Volume (Liters)</h5>
                            <p><code>waterVolume = (waterPercentage / 100) × tankCapacityLiters</code></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Visualizer, Chart & App Scripts -->
    <script src="assets/js/tank-visualizer.js"></script>
    <script src="assets/js/live-chart.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
