/**
 * Smart Water Tank Monitoring System - Application Controller
 * Handles Navigation, Real-Time Polling / SSE, State Updates,
 * Visualizer Synchronization, Charts, History, Alerts, and Settings.
 */

const AppState = {
    activePage: 'dashboard',
    latestReading: null,
    device: null,
    settings: null,
    isSimulating: false,
    pollTimer: null,
    sseSource: null
};

// Global visualizer and chart instances
let tankVisualizer = null;
let liveChart = null;

// ==============================================================================
// INITIALIZATION
// ==============================================================================
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Components
    initNavigation();
    initVisualizer();
    initChart();
    initSimulationDrawer();
    initHistoryFilters();
    initAlertsFilters();
    initSettingsForm();
    initLogs();
    initProfileDropdown();

    // 2. Fetch initial data and start real-time updates
    fetchInitialData();
    startRealtimeStream();

    // 3. Heartbeat timer for online/offline seconds counter
    setInterval(updateHeartbeatTime, 1000);
});

function initProfileDropdown() {
    const userBtn = document.getElementById('user-profile-btn');
    const userIconBtn = document.getElementById('header-user-icon-btn');
    const menu = document.getElementById('profile-dropdown-menu');

    function toggleMenu(e) {
        if (e) e.stopPropagation();
        if (menu) menu.classList.toggle('active');
    }

    if (userBtn) userBtn.addEventListener('click', toggleMenu);
    if (userIconBtn) userIconBtn.addEventListener('click', toggleMenu);

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (menu && menu.classList.contains('active')) {
            const isClickInside = menu.contains(e.target) || 
                                  (userBtn && userBtn.contains(e.target)) || 
                                  (userIconBtn && userIconBtn.contains(e.target));
            if (!isClickInside) {
                menu.classList.remove('active');
            }
        }
    });

    // Handle navigation links inside profile menu (Settings, Activity Logs)
    document.querySelectorAll('[data-profile-nav]').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const pageTarget = item.getAttribute('data-profile-nav');
            if (pageTarget) {
                switchPage(pageTarget);
            }
            if (menu) menu.classList.remove('active');
        });
    });
}

// ==============================================================================
// 1. NAVIGATION & PAGE SWITCHING
// ==============================================================================
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const targetPage = item.getAttribute('data-page');
            if (targetPage) {
                switchPage(targetPage);
            }
        });
    });

    // Bell notification icon switches to Alerts page
    const bellBtn = document.getElementById('header-bell-btn');
    if (bellBtn) {
        bellBtn.addEventListener('click', () => {
            switchPage('alerts');
        });
    }
}

function switchPage(pageId) {
    AppState.activePage = pageId;

    // Update navigation active states
    document.querySelectorAll('.nav-item').forEach(item => {
        if (item.getAttribute('data-page') === pageId) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });

    // Update visible view
    document.querySelectorAll('.page-view').forEach(view => {
        view.classList.remove('active');
    });

    const targetView = document.getElementById(`view-${pageId}`);
    if (targetView) {
        targetView.classList.add('active');
    }

    // Update header title
    const headerTitle = document.getElementById('header-page-title');
    if (headerTitle) {
        const titles = {
            'dashboard': 'DASHBOARD',
            'live-monitor': 'LIVE MONITOR',
            'history': 'HISTORY',
            'alerts': 'ALERTS',
            'logs': 'SYSTEM LOGS',
            'settings': 'SETTINGS'
        };
        headerTitle.textContent = titles[pageId] || pageId.toUpperCase();
    }

    // Trigger page-specific data loads
    if (pageId === 'history') {
        loadHistoryData();
    } else if (pageId === 'alerts') {
        loadAlertsData();
    } else if (pageId === 'logs') {
        loadLogsData();
    } else if (pageId === 'settings') {
        loadSettingsData();
    } else if (pageId === 'live-monitor' && liveChart) {
        // Redraw chart to fit dimensions cleanly
        setTimeout(() => {
            if (liveChart && liveChart.chart) {
                liveChart.chart.resize();
                liveChart.chart.update('none');
            }
        }, 80);
    }
}

// ==============================================================================
// 2. INITIALIZE VISUALIZERS & CHARTS
// ==============================================================================
function initVisualizer() {
    tankVisualizer = new TankVisualizer('tank-canvas-container');
}

function initChart() {
    liveChart = new LiveWaterChart('liveWaterChart');
}

// ==============================================================================
// 3. REAL-TIME DATA STREAM & SYNC
// ==============================================================================
function fetchInitialData() {
    fetch('api/latest.php')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                applyLatestData(res.data);
            }
        })
        .catch(err => console.error('Error fetching initial data:', err));

    // Also fetch initial chart trend data
    fetch('api/history.php?filter=today&limit=25')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.chart_data && liveChart) {
                liveChart.updateData(res.chart_data.labels, res.chart_data.percentages);
            }
        })
        .catch(err => console.error('Error fetching initial chart:', err));
}

function startRealtimeStream() {
    // Try Server-Sent Events first for real-time push
    if (typeof(EventSource) !== "undefined") {
        try {
            AppState.sseSource = new EventSource('api/stream.php');

            AppState.sseSource.addEventListener('tank_update', (e) => {
                const payload = JSON.parse(e.data);
                if (payload && payload.reading) {
                    // Update latest dashboard reading
                    updateDashboardFromStream(payload);
                }
            });

            AppState.sseSource.onerror = (err) => {
                // Fallback to short polling if SSE connection drops
                console.warn('SSE stream disconnected, falling back to polling.', err);
                if (AppState.sseSource) {
                    AppState.sseSource.close();
                    AppState.sseSource = null;
                }
                startShortPolling();
            };
            return;
        } catch (e) {
            console.warn('SSE failed to initialize, using polling.', e);
        }
    }
    startShortPolling();
}

function startShortPolling() {
    if (AppState.pollTimer) clearInterval(AppState.pollTimer);
    AppState.pollTimer = setInterval(() => {
        fetch('api/latest.php')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data) {
                    applyLatestData(res.data);
                }
            })
            .catch(err => console.error('Polling error:', err));
    }, 3000); // Poll every 3 seconds
}

// Helper function to safely set element text content
function setText(id, val) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = (val !== null && val !== undefined) ? val : '--';
    }
}

// ==============================================================================
// 4. APPLY TELEMETRY DATA TO UI
// ==============================================================================
function applyLatestData(data) {
    if (!data) return;

    const r = data.reading;
    const dev = data.device;
    const alerts = data.alerts;
    const summary = data.status_summary;
    const thresholds = data.thresholds;

    AppState.latestReading = r;
    AppState.device = dev;

    if (r) {
        const pct = r.water_percentage;
        const volume = r.water_volume_liters;
        const capacity = r.tank_capacity_liters || 1000;
        const dist = r.distance_cm;
        const status = r.tank_status;

        // 1. Synchronize Tank Canvas Visualizer
        if (tankVisualizer) {
            tankVisualizer.setLevel(pct);
        }

        // 2. Dashboard - Main Tank Card
        setText('dash-pct-huge', `${pct}%`);
        const statusInline = document.getElementById('dash-status-inline');
        if (statusInline) {
            statusInline.textContent = status;
            statusInline.className = `status-badge-inline ${status.toLowerCase()}`;
        }

        setText('dash-tank-subtitle', summary ? summary.description : `Water level is currently ${status}.`);
        setText('dash-volume-numbers', `${volume} / ${capacity} L`);

        const barFill = document.getElementById('dash-volume-bar-fill');
        if (barFill) {
            barFill.style.width = `${Math.min(100, Math.max(0, pct))}%`;
            // Color according to status
            if (pct <= 15) barFill.style.background = 'linear-gradient(90deg, #DC3C3C, #991B1B)';
            else if (pct < 30) barFill.style.background = 'linear-gradient(90deg, #F2A126, #C97E10)';
            else barFill.style.background = 'linear-gradient(90deg, #3C7EDA, #1E40AF)';
        }

        // 3. Dashboard - Side Column Cards
        setText('card-tank-status-title', summary ? summary.title : status);
        setText('card-tank-status-sub', summary ? summary.subtext : 'Operational');

        setText('card-last-updated-date', r.formatted_date || 'Today');
        setText('card-last-updated-time', r.formatted_time || 'Just now');

        setText('card-alert-status-title', alerts ? alerts.status_label : 'No Alerts');
        setText('card-alert-status-sub', alerts ? alerts.status_subtext : 'Zero system anomalies');

        // 4. Dashboard - Bottom Metric Cards
        const cmVal = (r.water_level_cm !== undefined && r.water_level_cm !== null) 
            ? Number(r.water_level_cm).toFixed(1) 
            : ((r.distance_cm !== undefined && r.distance_cm !== null) ? Number(r.distance_cm).toFixed(1) : '38.4');
        const sensorDisplay = `${cmVal} cm`;
        setText('dash-sensor-val', sensorDisplay);
        setText('dash-current-volume-bottom', `${volume} L`);

        // 5. Live Monitor Page Cards
        setText('live-pct-display', `${pct}%`);
        const liveStatusBadge = document.getElementById('live-status-badge');
        if (liveStatusBadge) {
            liveStatusBadge.textContent = status;
            liveStatusBadge.className = `status-badge-inline ${status.toLowerCase()}`;
        }
        setText('live-status-subtitle', summary ? summary.description : `Water Level status is optimal but nearing maximum capacity.`);

        setText('live-sensor-val', sensorDisplay);
        setText('live-capacity-l', `${capacity} L`);
        setText('live-volume-l', `${volume} L`);

        // Level Indicators on Live Monitor (High / Medium / Low)
        updateLevelIndicators(pct, thresholds);
    }

    // 6. Header Device Connectivity Badge
    updateDeviceBadge(dev);

    // 7. Header Notification Badge
    const unread = alerts ? alerts.unacknowledged_count : 0;
    const notifBadge = document.getElementById('header-notif-count');
    const navAlertsBadge = document.getElementById('nav-alerts-badge');

    if (notifBadge) {
        notifBadge.textContent = unread;
        notifBadge.style.display = unread > 0 ? 'flex' : 'none';
    }
    if (navAlertsBadge) {
        navAlertsBadge.textContent = unread;
        navAlertsBadge.style.display = unread > 0 ? 'inline-block' : 'none';
    }
}

function updateDashboardFromStream(payload) {
    if (!payload || !payload.reading) return;
    const r = payload.reading;
    const dev = payload.device;
    const unreadAlerts = payload.unread_alerts || 0;

    // Update canvas
    if (tankVisualizer) tankVisualizer.setLevel(r.water_percentage);

    const cmVal = (r.water_level_cm !== undefined && r.water_level_cm !== null) 
        ? Number(r.water_level_cm).toFixed(1) 
        : ((r.distance_cm !== undefined && r.distance_cm !== null) ? Number(r.distance_cm).toFixed(1) : '38.4');
    const sensorDisplay = `${cmVal} cm`;

    // Update progress bar
    const barFill = document.getElementById('dash-volume-bar-fill');
    if (barFill) {
        barFill.style.width = `${Math.min(100, Math.max(0, r.water_percentage))}%`;
        if (r.water_percentage <= 15) barFill.style.background = 'linear-gradient(90deg, #DC3C3C, #991B1B)';
        else if (r.water_percentage < 30) barFill.style.background = 'linear-gradient(90deg, #F2A126, #C97E10)';
        else barFill.style.background = 'linear-gradient(90deg, #3C7EDA, #1E40AF)';
    }

    // Update text
    setText('dash-pct-huge', `${r.water_percentage}%`);
    setText('live-pct-display', `${r.water_percentage}%`);
    setText('dash-volume-numbers', `${r.water_volume_liters} / 1000 L`);
    setText('dash-sensor-val', sensorDisplay);
    setText('dash-current-volume-bottom', `${r.water_volume_liters} L`);

    setText('card-last-updated-date', r.formatted_date);
    setText('card-last-updated-time', r.formatted_time);

    setText('live-sensor-val', sensorDisplay);
    setText('live-volume-l', `${r.water_volume_liters} L`);

    const statusInline = document.getElementById('dash-status-inline');
    if (statusInline) {
        statusInline.textContent = r.tank_status;
        statusInline.className = `status-badge-inline ${r.tank_status.toLowerCase()}`;
    }

    const liveStatusBadge = document.getElementById('live-status-badge');
    if (liveStatusBadge) {
        liveStatusBadge.textContent = r.tank_status;
        liveStatusBadge.className = `status-badge-inline ${r.tank_status.toLowerCase()}`;
    }

    updateDeviceBadge(dev);
    updateLevelIndicators(r.water_percentage, { high: 70, medium: 30, critical: 15 });

    // Add point to live chart
    if (liveChart && r.formatted_time) {
        const timeShort = r.formatted_time.substring(0, 5);
        liveChart.addPoint(timeShort, r.water_percentage);
    }
}

function updateDeviceBadge(dev) {
    const badge = document.getElementById('device-status-badge');
    const label = document.getElementById('device-status-label');
    const seenText = document.getElementById('device-last-seen');

    if (!badge || !dev) return;

    if (dev.is_online) {
        badge.className = 'device-status-badge online';
        if (label) label.textContent = 'ONLINE';
    } else {
        badge.className = 'device-status-badge offline';
        if (label) label.textContent = 'OFFLINE';
    }

    if (seenText) {
        seenText.textContent = `Last seen: ${dev.last_seen_human}`;
    }
}

function updateHeartbeatTime() {
    if (!AppState.device || !AppState.device.last_seen) return;
    const lastSeenTs = new Date(AppState.device.last_seen.replace(/-/g, '/')).getTime();
    if (isNaN(lastSeenTs)) return;

    const now = Date.now();
    const diffSec = Math.max(0, Math.floor((now - lastSeenTs) / 1000));
    AppState.device.seconds_ago = diffSec;

    const timeout = AppState.device.offline_timeout_sec || 30;
    const isOnline = diffSec <= timeout;
    AppState.device.is_online = isOnline;

    const human = diffSec < 60 ? `${diffSec}s ago` : `${Math.floor(diffSec / 60)}m ago`;
    AppState.device.last_seen_human = human;

    updateDeviceBadge(AppState.device);
}

function updateLevelIndicators(pct, thresholds) {
    const highThresh = thresholds ? thresholds.high : 70;
    const medThresh = thresholds ? thresholds.medium : 30;

    const highCard = document.getElementById('indicator-card-high') || document.getElementById('pill-high');
    const medCard = document.getElementById('indicator-card-med') || document.getElementById('pill-medium');
    const lowCard = document.getElementById('indicator-card-low') || document.getElementById('pill-low');

    if (highCard) highCard.classList.remove('active');
    if (medCard) medCard.classList.remove('active');
    if (lowCard) lowCard.classList.remove('active');

    if (pct >= highThresh) {
        if (highCard) highCard.classList.add('active');
    } else if (pct >= medThresh) {
        if (medCard) medCard.classList.add('active');
    } else {
        if (lowCard) lowCard.classList.add('active');
    }
}

// ==============================================================================
// 5. DEVELOPER SIMULATION CONTROLS
// ==============================================================================
function initSimulationDrawer() {
    const toggleBtn = document.getElementById('toggle-sim-btn');
    const drawer = document.getElementById('sim-drawer');
    const slider = document.getElementById('sim-slider');
    const sliderVal = document.getElementById('sim-slider-val');

    if (toggleBtn && drawer) {
        toggleBtn.addEventListener('click', () => {
            drawer.classList.toggle('active');
        });
    }

    if (slider && sliderVal) {
        slider.addEventListener('input', (e) => {
            sliderVal.textContent = `${e.target.value}%`;
        });

        slider.addEventListener('change', (e) => {
            triggerSimulation(e.target.value);
        });
    }

    // Preset buttons (80%, 60%, 40%, 20%, 10%)
    document.querySelectorAll('.sim-preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const val = btn.getAttribute('data-value');
            if (slider) slider.value = val;
            if (sliderVal) sliderVal.textContent = `${val}%`;

            // Active button highlight
            document.querySelectorAll('.sim-preset-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            triggerSimulation(val);
        });
    });

    const applyBtn = document.getElementById('sim-apply-btn');
    const autoBtn = document.getElementById('sim-auto-btn');
    let simAutoInterval = null;

    if (applyBtn && slider) {
        applyBtn.addEventListener('click', () => {
            triggerSimulation(slider.value);
        });
    }

    if (autoBtn) {
        autoBtn.addEventListener('click', () => {
            if (simAutoInterval) {
                clearInterval(simAutoInterval);
                simAutoInterval = null;
                autoBtn.textContent = 'Start Auto Cycle';
                autoBtn.style.background = '';
                autoBtn.style.color = '';
                if (typeof showToast === 'function') showToast('Auto cycle stopped.', 'info');
            } else {
                if (typeof showToast === 'function') showToast('Auto cycle active! Sending telemetry every 5s.', 'success');
                autoBtn.textContent = 'Stop Auto Cycle';
                autoBtn.style.background = '#1BB55C';
                autoBtn.style.color = '#FFFFFF';
                triggerSimulation(slider ? slider.value : 60);
                simAutoInterval = setInterval(() => {
                    const currentVal = slider ? parseFloat(slider.value) : 60;
                    let nextVal = currentVal + (Math.random() > 0.4 ? 2 : -2);
                    if (nextVal > 95) nextVal = 40;
                    if (nextVal < 15) nextVal = 80;
                    const rounded = Math.round(nextVal);
                    if (slider) slider.value = rounded;
                    if (sliderVal) sliderVal.textContent = `${rounded}%`;
                    triggerSimulation(rounded);
                }, 5000);
            }
        });
    }
}

function triggerSimulation(percentage) {
    fetch('api/simulate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ percentage: parseFloat(percentage) })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            showToast(`[Demo/Simulation Mode] Water level set to ${percentage}%`, 'success');
            // Refresh latest data immediately
            fetchInitialData();
            if (AppState.activePage === 'history') loadHistoryData();
            if (AppState.activePage === 'alerts') loadAlertsData();
        } else {
            showToast(res.error || 'Simulation failed', 'error');
        }
    })
    .catch(err => {
        console.error('Simulation error:', err);
        showToast('Simulation request failed.', 'error');
    });
}

// ==============================================================================
// 6. HISTORY PAGE & FILTERING
// ==============================================================================
function initHistoryFilters() {
    const rangeSelect = document.getElementById('history-range-select');
    const refreshBtn = document.getElementById('history-refresh-btn');
    const exportBtn = document.getElementById('history-export-btn');

    if (rangeSelect) {
        rangeSelect.addEventListener('change', (e) => {
            loadHistoryData(e.target.value);
            if (exportBtn) {
                exportBtn.href = `api/history.php?filter=${encodeURIComponent(e.target.value)}&export=csv`;
            }
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            const filter = rangeSelect ? rangeSelect.value : 'today';
            loadHistoryData(filter);
        });
    }

    // Support button pills if present
    const filterBtns = document.querySelectorAll('.history-filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.getAttribute('data-filter');
            loadHistoryData(filter);
        });
    });
}

function loadHistoryData(filter = 'today', start = '', end = '') {
    const tbody = document.getElementById('history-table-body');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:35px; color:#686C84; font-weight:600;">Loading historical telemetry records...</td></tr>`;

    let url = `api/history.php?filter=${encodeURIComponent(filter)}&limit=100`;
    if (filter === 'custom' && start && end) {
        url += `&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
    }

    fetch(url)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.readings) {
                renderHistoryTable(res.readings);
            } else {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:35px; color:#686C84; font-weight:600;">No sensor readings found.</td></tr>`;
            }
        })
        .catch(err => {
            console.error('History load error:', err);
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:35px; color:#DC3C3C; font-weight:600;">Failed to load history data.</td></tr>`;
        });
}

function renderHistoryTable(readings) {
    const tbody = document.getElementById('history-table-body');
    if (!tbody) return;

    if (!readings || readings.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:35px; color:#686C84; font-weight:600;">No readings recorded for this period.</td></tr>`;
        return;
    }

    tbody.innerHTML = readings.map(r => {
        const statusClass = r.tank_status ? r.tank_status.toLowerCase() : 'normal';
        const simBadge = r.is_simulated ? `<span class="sim-source-tag">SIM</span>` : '';
        const timestamp = (r.date && r.time) ? `${r.date} ${r.time}` : (r.recorded_at || '--');
        const cmVal = (r.water_level_cm !== undefined && r.water_level_cm !== null) 
            ? Number(r.water_level_cm).toFixed(1) 
            : ((r.distance_cm !== undefined && r.distance_cm !== null) ? Number(r.distance_cm).toFixed(1) : '--');
        const cmDisplay = cmVal !== '--' ? `${cmVal} cm` : '--';
        const adcDisplay = (r.sensor_value !== undefined && r.sensor_value !== null) ? `${r.sensor_value}` : '--';
        let deviceStr = r.device_id || 'SMART-TANK-001';
        if (deviceStr.startsWith('SMART-')) deviceStr = deviceStr.substring(6);

        return `
            <tr>
                <td style="font-size:12px; color:#5B6B7C; font-weight:600; white-space:nowrap;">${escapeHtml(timestamp)}</td>
                <td>
                    <div style="display:inline-flex; align-items:center; gap:8px;">
                        <strong style="min-width:32px; font-weight:800; color:#0B233E;">${r.water_percentage}%</strong>
                        <div style="width:50px; height:8px; background:#E5EDF6; border-radius:4px; overflow:hidden;">
                            <div style="width:${Math.min(100, Math.max(0, r.water_percentage))}%; height:100%; background:linear-gradient(90deg, #3C7EDA, #1E40AF); border-radius:4px;"></div>
                        </div>
                    </div>
                </td>
                <td style="font-weight:800; color:#0B233E;">${r.water_volume_liters} L</td>
                <td style="font-weight:700; color:#0B233E;">${cmDisplay}</td>
                <td style="font-weight:700; color:#0B233E;">${adcDisplay}</td>
                <td>
                    <span class="status-tag ${statusClass}">${escapeHtml(r.tank_status || 'NORMAL')}</span>
                    ${simBadge}
                </td>
                <td style="font-weight:600; color:#686C84;">${escapeHtml(deviceStr)}</td>
            </tr>
        `;
    }).join('');
}

// ==============================================================================
// 7. ALERTS PAGE & FILTERING
// ==============================================================================
let currentAlertsFilter = 'all';

function initAlertsFilters() {
    const tabBtns = document.querySelectorAll('.alerts-tab-btn');
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentAlertsFilter = btn.getAttribute('data-filter') || 'all';
            loadAlertsData(currentAlertsFilter);
        });
    });

    const ackAllBtn = document.getElementById('alerts-ack-all-btn') || document.getElementById('ack-all-alerts-btn');
    if (ackAllBtn) {
        ackAllBtn.addEventListener('click', () => {
            acknowledgeAllAlerts();
        });
    }
}

function loadAlertsData(filter = currentAlertsFilter) {
    currentAlertsFilter = filter;
    const list = document.getElementById('alerts-container');
    if (!list) return;

    list.innerHTML = `<div style="text-align:center; padding:40px; color:#686C84; font-weight:600;">Loading system alerts...</div>`;

    fetch(`api/alerts.php?filter=${encodeURIComponent(filter)}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.alerts) {
                renderAlerts(res.alerts);
                if (res.unacknowledged_count !== undefined) {
                    const notifBadge = document.getElementById('header-notif-count');
                    const navAlertsBadge = document.getElementById('nav-alerts-badge');
                    const unread = res.unacknowledged_count;
                    if (notifBadge) {
                        notifBadge.textContent = unread;
                        notifBadge.style.display = unread > 0 ? 'flex' : 'none';
                    }
                    if (navAlertsBadge) {
                        navAlertsBadge.textContent = unread;
                        navAlertsBadge.style.display = unread > 0 ? 'inline-block' : 'none';
                    }
                }
            } else {
                renderAlerts([]);
            }
        })
        .catch(err => {
            console.error('Alerts load error:', err);
            list.innerHTML = `<div style="text-align:center; padding:40px; color:#DC3C3C; font-weight:600;">Failed to load alerts.</div>`;
        });
}

function renderAlerts(alerts) {
    const list = document.getElementById('alerts-container');
    if (!list) return;

    if (!alerts || alerts.length === 0) {
        list.innerHTML = `
            <div class="wf-status-card status-blue" style="text-align:center; padding:40px; background:#FFFFFF; border:1px solid #E6EDF5;">
                <h4 style="font-size:16px; margin-bottom:6px; color:#0B233E; font-weight:800;">No Alerts Registered</h4>
                <p style="color:#686C84; font-size:13px; font-weight:500;">Zero system alerts found for this filter category.</p>
            </div>
        `;
        return;
    }

    list.innerHTML = alerts.map(a => {
        let cardType = 'low';
        let iconColor = 'yellow';
        if (a.alert_type.includes('CRITICAL')) {
            cardType = 'critical';
            iconColor = 'red';
        } else if (a.alert_type.includes('OFFLINE')) {
            cardType = 'offline';
            iconColor = 'gray';
        }

        const ackBtn = a.acknowledged ? 
            `<button class="ack-btn acknowledged" disabled>✓ Acknowledged</button>` :
            `<button class="ack-btn" onclick="acknowledgeAlert(${a.id})">Acknowledge</button>`;

        return `
            <div class="alert-item-card ${cardType}" id="alert-card-${a.id}">
                <div class="alert-left-content">
                    <div class="alert-icon-box ${iconColor}">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                    </div>
                    <div class="alert-details">
                        <h4>${escapeHtml(a.alert_type)}</h4>
                        <p>${escapeHtml(a.message)}</p>
                        <div class="alert-meta">
                            <span>📅 ${escapeHtml(a.date)} at ${escapeHtml(a.time)}</span>
                            <span>⏱️ ${escapeHtml(a.time_ago)}</span>
                            <span>📟 Device: ${escapeHtml(a.device_id)}</span>
                        </div>
                    </div>
                </div>
                <div>
                    ${ackBtn}
                </div>
            </div>
        `;
    }).join('');
}

window.acknowledgeAlert = function(alertId) {
    fetch('api/alerts.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'acknowledge', alert_id: alertId })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            if (typeof showToast === 'function') showToast('Alert acknowledged.', 'success');
            loadAlertsData(currentAlertsFilter);
        }
    })
    .catch(err => console.error('Ack error:', err));
};

window.acknowledgeAllAlerts = function() {
    fetch('api/alerts.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'acknowledge_all' })
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            if (typeof showToast === 'function') showToast('All alerts acknowledged.', 'success');
            loadAlertsData(currentAlertsFilter);
        }
    })
    .catch(err => console.error('Ack all error:', err));
};


// ==============================================================================
// 8. SETTINGS CONFIGURATION
// ==============================================================================
function initSettingsForm() {
    const form = document.getElementById('settings-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const payload = {
            tank_height_cm: parseFloat(document.getElementById('set-tank-height').value),
            tank_capacity_liters: parseFloat(document.getElementById('set-tank-capacity').value),
            sensor_min_raw: parseInt(document.getElementById('set-sensor-min').value),
            sensor_max_raw: parseInt(document.getElementById('set-sensor-max').value),
            high_threshold: parseFloat(document.getElementById('set-high-thresh').value),
            medium_threshold: parseFloat(document.getElementById('set-med-thresh').value),
            critical_threshold: parseFloat(document.getElementById('set-crit-thresh').value),
            device_name: document.getElementById('set-device-name').value,
            reading_interval_sec: parseInt(document.getElementById('set-interval').value),
            offline_timeout_sec: parseInt(document.getElementById('set-offline-timeout').value),
            api_key: document.getElementById('set-api-key').value
        };

        fetch('api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showToast('Tank settings updated successfully!', 'success');
                fetchInitialData();
            } else {
                showToast(res.error || 'Failed to update settings.', 'error');
            }
        })
        .catch(err => {
            console.error('Settings error:', err);
            showToast('Failed to save settings.', 'error');
        });
    });
}

function loadSettingsData() {
    fetch('api/settings.php')
        .then(res => res.json())
        .then(res => {
            if (res.success && res.settings) {
                const s = res.settings;
                setValue('set-tank-height', s.tank_height_cm);
                setValue('set-tank-capacity', s.tank_capacity_liters);
                setValue('set-sensor-min', s.sensor_min_raw || 500);
                setValue('set-sensor-max', s.sensor_max_raw || 2800);
                setValue('set-high-thresh', s.high_threshold);
                setValue('set-med-thresh', s.medium_threshold);
                setValue('set-crit-thresh', s.critical_threshold);
                setValue('set-device-name', s.device_name);
                setValue('set-interval', s.reading_interval_sec);
                setValue('set-offline-timeout', s.offline_timeout_sec);
                setValue('set-api-key', s.api_key);
            }
        })
        .catch(err => console.error('Settings fetch error:', err));
}

// ==============================================================================
// UTILITY FUNCTIONS
// ==============================================================================
function setText(id, text) {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
}

function setValue(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val;
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ==============================================================================
// 9. SYSTEM & ACTIVITY LOGS CONTROLLER
// ==============================================================================
const LogsState = {
    page: 1,
    limit: 20,
    search: '',
    eventType: 'ALL',
    action: 'ALL',
    user: 'ALL',
    date: '',
    searchDebounceTimer: null
};

function initLogs() {
    // 1. Event Type Category Pills
    const pills = document.querySelectorAll('#logs-type-pills .type-pill');
    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            LogsState.eventType = pill.getAttribute('data-type') || 'ALL';
            LogsState.page = 1;
            loadLogsData();
        });
    });

    // 2. Search Input with 300ms Debounce
    const searchInput = document.getElementById('logs-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(LogsState.searchDebounceTimer);
            LogsState.searchDebounceTimer = setTimeout(() => {
                LogsState.search = e.target.value.trim();
                LogsState.page = 1;
                loadLogsData();
            }, 300);
        });
    }

    // 3. Action Filter Select
    const actionSelect = document.getElementById('logs-filter-action');
    if (actionSelect) {
        actionSelect.addEventListener('change', (e) => {
            LogsState.action = e.target.value;
            LogsState.page = 1;
            loadLogsData();
        });
    }

    // 4. User Filter Select
    const userSelect = document.getElementById('logs-filter-user');
    if (userSelect) {
        userSelect.addEventListener('change', (e) => {
            LogsState.user = e.target.value;
            LogsState.page = 1;
            loadLogsData();
        });
    }

    // 5. Date Filter Input
    const dateInput = document.getElementById('logs-filter-date');
    if (dateInput) {
        dateInput.addEventListener('change', (e) => {
            LogsState.date = e.target.value;
            LogsState.page = 1;
            loadLogsData();
        });
    }

    // 6. Reset Filters Button
    const resetBtn = document.getElementById('logs-btn-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            LogsState.search = '';
            LogsState.eventType = 'ALL';
            LogsState.action = 'ALL';
            LogsState.user = 'ALL';
            LogsState.date = '';
            LogsState.page = 1;

            if (searchInput) searchInput.value = '';
            if (actionSelect) actionSelect.value = 'ALL';
            if (userSelect) userSelect.value = 'ALL';
            if (dateInput) dateInput.value = '';

            pills.forEach(p => {
                if (p.getAttribute('data-type') === 'ALL') {
                    p.classList.add('active');
                } else {
                    p.classList.remove('active');
                }
            });

            loadLogsData();
        });
    }

    // 7. Refresh Logs Button
    const refreshBtn = document.getElementById('btn-refresh-logs');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            loadLogsData();
            showToast('Logs refreshed.', 'info');
        });
    }
}

function loadLogsData(page = null) {
    if (page !== null) {
        LogsState.page = page;
    }

    const tbody = document.getElementById('logs-table-body');
    const loadingEl = document.getElementById('logs-loading');
    const emptyEl = document.getElementById('logs-empty');
    const infoEl = document.getElementById('logs-pagination-info');

    if (loadingEl) loadingEl.style.display = 'flex';
    if (emptyEl) emptyEl.style.display = 'none';
    if (tbody) tbody.innerHTML = '';

    const queryParams = new URLSearchParams({
        page: LogsState.page,
        limit: LogsState.limit,
        search: LogsState.search,
        event_type: LogsState.eventType,
        action: LogsState.action,
        user: LogsState.user,
        date: LogsState.date
    });

    fetch(`api/logs.php?${queryParams.toString()}`)
        .then(res => {
            if (res.status === 401) {
                // Session expired or unauthorized
                window.location.href = 'login.php?expired=1';
                return null;
            }
            return res.json();
        })
        .then(res => {
            if (loadingEl) loadingEl.style.display = 'none';
            if (!res || !res.success) {
                if (emptyEl) {
                    emptyEl.style.display = 'flex';
                    const span = emptyEl.querySelector('span');
                    if (span) span.textContent = res?.error || 'Failed to load system logs.';
                }
                return;
            }

            // Populate Action and User options dynamically if present
            if (res.filters) {
                populateLogsFilterOptions(res.filters);
            }

            // Render Rows
            if (res.logs && res.logs.length > 0) {
                renderLogsTable(res.logs);
                if (emptyEl) emptyEl.style.display = 'none';
            } else {
                if (emptyEl) {
                    emptyEl.style.display = 'flex';
                    const span = emptyEl.querySelector('span');
                    if (span) span.textContent = 'No logs found matching the selected filters.';
                }
            }

            // Render Pagination
            if (res.pagination) {
                renderLogsPagination(res.pagination);
            }
        })
        .catch(err => {
            if (loadingEl) loadingEl.style.display = 'none';
            if (emptyEl) {
                emptyEl.style.display = 'flex';
                const span = emptyEl.querySelector('span');
                if (span) span.textContent = 'Network error while fetching system logs.';
            }
        });
}

function populateLogsFilterOptions(filters) {
    const actionSelect = document.getElementById('logs-filter-action');
    if (actionSelect && filters.actions) {
        const curVal = actionSelect.value;
        const opts = ['<option value="ALL">All Actions</option>'];
        filters.actions.forEach(act => {
            opts.push(`<option value="${escapeHtml(act)}" ${act === curVal ? 'selected' : ''}>${escapeHtml(act)}</option>`);
        });
        actionSelect.innerHTML = opts.join('');
    }

    const userSelect = document.getElementById('logs-filter-user');
    if (userSelect && filters.users) {
        const curUser = userSelect.value;
        const uopts = ['<option value="ALL">All Users</option>'];
        filters.users.forEach(u => {
            uopts.push(`<option value="${escapeHtml(u)}" ${u === curUser ? 'selected' : ''}>${escapeHtml(u)}</option>`);
        });
        userSelect.innerHTML = uopts.join('');
    }
}

function renderLogsTable(logs) {
    const tbody = document.getElementById('logs-table-body');
    if (!tbody) return;

    const badgeClasses = {
        'AUTHENTICATION': 'badge-auth',
        'SENSOR': 'badge-sensor',
        'ALERT': 'badge-alert',
        'DEVICE': 'badge-device',
        'SETTINGS': 'badge-settings',
        'SYSTEM': 'badge-system',
        'ERROR': 'badge-error'
    };

    const rows = logs.map(log => {
        const etype = (log.event_type || 'SYSTEM').toUpperCase();
        const badgeClass = badgeClasses[etype] || 'badge-system';
        const actor = log.username || (log.user_id ? `User #${log.user_id}` : 'System');
        const deviceOrIp = log.device_id || log.ip_address || '--';

        return `
            <tr>
                <td class="logs-id-cell">#${log.id}</td>
                <td class="logs-time-cell">${escapeHtml(log.created_at)}</td>
                <td>
                    <span class="badge-event ${badgeClass}">
                        ${escapeHtml(etype)}
                    </span>
                </td>
                <td class="logs-action-cell">${escapeHtml(log.action)}</td>
                <td class="logs-desc-cell">${escapeHtml(log.description)}</td>
                <td><strong>${escapeHtml(actor)}</strong></td>
                <td style="font-size:12px; color:var(--text-secondary);">${escapeHtml(deviceOrIp)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = rows.join('');
}

function renderLogsPagination(pagination) {
    const infoEl = document.getElementById('logs-pagination-info');
    const controlsEl = document.getElementById('logs-pagination-controls');

    const { page, limit, total, total_pages } = pagination;
    const start = total === 0 ? 0 : (page - 1) * limit + 1;
    const end = Math.min(page * limit, total);

    if (infoEl) {
        infoEl.textContent = `Showing ${start} to ${end} of ${total} logs`;
    }

    if (controlsEl) {
        let html = '';

        // Previous button
        html += `<button class="btn-page" ${page <= 1 ? 'disabled' : ''} onclick="loadLogsData(${page - 1})">‹ Prev</button>`;

        // Page buttons (window of 5 around current page)
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(total_pages, startPage + 4);

        if (startPage > 1) {
            html += `<button class="btn-page" onclick="loadLogsData(1)">1</button>`;
            if (startPage > 2) html += `<span style="padding:0 4px; color:var(--text-muted);">...</span>`;
        }

        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="btn-page ${i === page ? 'active' : ''}" onclick="loadLogsData(${i})">${i}</button>`;
        }

        if (endPage < total_pages) {
            if (endPage < total_pages - 1) html += `<span style="padding:0 4px; color:var(--text-muted);">...</span>`;
            html += `<button class="btn-page" onclick="loadLogsData(${total_pages})">${total_pages}</button>`;
        }

        // Next button
        html += `<button class="btn-page" ${page >= total_pages ? 'disabled' : ''} onclick="loadLogsData(${page + 1})">Next ›</button>`;

        controlsEl.innerHTML = html;
    }
}

