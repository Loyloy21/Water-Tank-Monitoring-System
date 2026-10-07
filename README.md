# IoT-Based Smart Water Tank Monitoring System Using ESP32

A complete, production-ready, end-to-end IoT system for real-time water level measurement using an **Analog Water Level Sensor**, automated volume calculation, multilevel LED indication, audible buzzer alarms, REST API data ingestion, MySQL database storage, and a responsive real-time web dashboard.

---

## 1. System Architecture & Data Flow

```text
              ┌────────────────────────┐
              │   Water Level Sensor   │
              │  (Analog Depth Probe)  │
              └───────────┬────────────┘
                          │ (Signal AOUT: ESP32 GPIO 34 ADC)
                          ↓
              ┌────────────────────────┐
              │  ESP32 Microcontroller │
              └─────┬────────────┬─────┘
                    │            │
    ┌───────────────┴────┐       │ (Wi-Fi 2.4GHz HTTP POST /api/readings)
    │ Hardware Actuators │       ↓
    ├────────────────────┤   ┌────────────────────────┐
    │ Green LED  (GPIO25)│   │  Backend REST API      │
    │ Yellow LED (GPIO26)│   │  (PHP 8.1 / Apache)    │
    │ Red LED    (GPIO27)│   └───────────┬────────────┘
    │ Buzzer     (GPIO14)│               ↓
    └────────────────────┘   ┌────────────────────────┐
                             │  MariaDB / MySQL DB    │
                             │  (water_tank_db)       │
                             └───────────┬────────────┘
                                         ↓
                             ┌────────────────────────┐
                             │  Real-Time Web UI      │
                             │  (Dashboard, Live,     │
                             │   History, Alerts)     │
                             └───────────┬────────────┘
                                         ↓
                                       USER
```

---

## 2. Technology Stack

| Layer | Technology | Details |
|---|---|---|
| **Microcontroller** | ESP32 (NodeMCU / DevKit V1) | Arduino C++ firmware, non-blocking `millis()`, 10-sample ADC noise filtering |
| **Water Level Sensor**| Analog Water Level Sensor | Submerged depth probe connected to ESP32 ADC1 (GPIO 34) |
| **Indicators** | 3x LEDs + 5V Active Buzzer | Green (High), Yellow (Medium), Red (Low/Critical), Buzzer (Critical <= 15%) |
| **Backend API** | PHP 8.1 (Native PDO) | RESTful API, payload validation, automated alert generation, SSE real-time stream |
| **Database** | MariaDB / MySQL (XAMPP) | 4 normalized tables: `devices`, `sensor_readings`, `alerts`, `system_settings` |
| **Frontend UI** | HTML5, CSS3, Vanilla JS | Wireframe matching design, 3D animated HTML5 Canvas tank visualizer, Chart.js live area chart |
| **Web Server** | Apache 2.4 (XAMPP) | URL rewriting via `.htaccess` |

---

## 3. Hardware Connections

### Pinout Table

| Component | Pin | ESP32 GPIO | Description / Notes |
|---|---|---|---|
| **Water Level Sensor** | VCC (+) | 3.3V / VIN | 3.3V recommended power supply |
| **Water Level Sensor** | GND (-) | GND | Common Ground |
| **Water Level Sensor** | SIGNAL (AOUT) | GPIO 34 | Analog ADC1 Input (Configurable in code) |
| **Green LED** | Anode (+) | GPIO 25 | High water level (>= 70%) with 220Ω resistor |
| **Yellow LED**| Anode (+) | GPIO 26 | Medium water level (30% - 69%) with 220Ω resistor |
| **Red LED** | Anode (+) | GPIO 27 | Low / Critical water level (< 30%) with 220Ω resistor |
| **Buzzer** | Positive (+) | GPIO 14 | Active buzzer audible alarm (<= 15%) |

---

## 4. Water Level Calculation & Calibration

The analog sensor outputs a voltage that increases with water immersion depth. The ESP32's 12-bit ADC converts this voltage into a digital integer from `0` to `4095`.

- **Calibration Points (Configurable)**:
  - **Dry Sensor (0% Level)**: `SENSOR_MIN_RAW` (default: `500`)
  - **Max Submerged (100% Level)**: `SENSOR_MAX_RAW` (default: `2800`)

- **Formulas**:
  - `waterPercentage = ((rawADC - SENSOR_MIN_RAW) / (SENSOR_MAX_RAW - SENSOR_MIN_RAW)) * 100` (clamped between `0%` and `100%`)
  - `waterVolumeLiters = (waterPercentage / 100) * tankCapacityLiters`

- **Operating Status Logic**:
  - **HIGH (70% - 100%)**: Green LED ON, Yellow OFF, Red OFF, Buzzer OFF.
  - **MEDIUM (30% - 69%)**: Green OFF, Yellow ON, Red OFF, Buzzer OFF.
  - **LOW (16% - 29%)**: Green OFF, Yellow OFF, Red ON, Buzzer OFF. Generates "LOW WATER LEVEL" alert.
  - **CRITICAL (<= 15%)**: Green OFF, Yellow OFF, Red ON, **Buzzer ON (pulsing audio alarm)**. Generates "CRITICAL WATER LEVEL" alert.

---

## 5. Web Dashboard Pages & Features

1. **Dashboard Page**:
   - **Current Water Level**: Realistic 3D-styled animated water tank with interactive fluid waves and submerged sensor probe, level percentage (e.g. `72%`), inline status badge (`HIGH`), descriptive subtitle, and volume bar (`720 / 1000 L`).
   - **Tank Status Card**: "Normal" with green checkmark icon.
   - **Last Updated Card**: Real-time date & timestamp of the latest reading.
   - **Alert Status Card**: System anomalies count and alert status.
   - **Sensor Reading & Estimated Volume**: Raw analog ADC sensor reading and calculated water volume in Liters.
2. **Live Monitor Page**:
   - **Live Water Level & Dynamic Trend Graph**: Responsive area chart powered by Chart.js displaying water level trends over time with timestamps.
   - **Sensor Metric Cards**: Live sensor ADC reading, calibrated tank capacity, and current volume.
   - **Level Indicators**: 3 visual cards for HIGH, MEDIUM, and LOW with glowing state highlighting the active level.
3. **History Page**:
   - Filter by: **Today**, **This Week**, **This Month**, or **Custom Date Range**.
   - Data table displaying: Date, Time, Water Level (%), Water Volume (L), Sensor Reading (ADC), Status, and Hardware/Simulation source.
   - **Export to CSV**: Direct download of historical sensor data.
4. **Alerts Page**:
   - Chronological list of real system alerts (`CRITICAL WATER LEVEL`, `LOW WATER LEVEL`, `DEVICE OFFLINE`).
   - Individual "Acknowledge" buttons and "Acknowledge All Alerts" button.
5. **Settings Page**:
   - Configurable Tank Height (cm), Tank Capacity (Liters), Sensor Min ADC (0%), Sensor Max ADC (100%), High, Medium, and Critical thresholds, Device Name, Reading Interval (sec), Offline Timeout (sec), and API Secret Key.
6. **Demo / Simulation Toolbar**:
   - Test mode drawer for development and demonstrations without physical hardware.
   - Demo preset buttons: `10% (Critical Alarm)`, `20% (Low Alert)`, `40% (Medium)`, `60% (Medium)`, `80% (High)`.
   - Continuous slider (0% to 100%).
   - Posts directly to the backend API so developer can verify end-to-end data flow.
7. **Device Online / Offline Status**:
   - Header badge indicates live ESP32 connectivity:
     - **ONLINE** (green with pulsing dot) + "Last seen: X seconds ago"
     - **OFFLINE** (red) if no reading received within configured timeout (e.g. 30 seconds).

---

## 6. Backend API Endpoints

All endpoints support JSON and are located under `/api/`:

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/readings` | Receives sensor readings from ESP32 or Simulator |
| `GET` | `/api/readings/latest` | Returns latest water level, sensor reading, and device health |
| `GET` | `/api/readings/history` | Returns historical readings with date filtering and chart data |
| `GET` | `/api/readings/history?export=csv` | Exports readings directly as a CSV file |
| `GET` | `/api/alerts` | Lists system alerts |
| `POST` | `/api/alerts` | Acknowledges an alert (`action: acknowledge`, `alert_id: X`) |
| `GET` | `/api/device/status` | Returns online/offline status and heartbeat |
| `GET` | `/api/settings` | Returns tank configuration, thresholds, and calibration |
| `POST` | `/api/settings` | Updates tank configuration and sensor calibration |
| `GET` | `/api/stream` | Server-Sent Events (SSE) stream for real-time dashboard updates |
| `POST` | `/api/simulate` | Developer demo test reading injector |

### Sample ESP32 Payload to `POST /api/readings`:
```json
{
  "device_id": "SMART-TANK-001",
  "water_level": 72.0,
  "water_percentage": 72.0,
  "sensor_value": 2156,
  "water_volume_liters": 720.0,
  "tank_capacity_liters": 1000.0,
  "status": "HIGH",
  "api_key": "smarttank_secret_key_2026"
}
```

---

## 7. How to Run Locally

### 1. Database & Apache Setup (XAMPP)
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Run database migration/setup:
   ```bash
   php database/setup_db.php
   ```
3. Open your browser and navigate to:
   ```text
   http://localhost/WatertankMonitoringSystem/
   ```

### 2. ESP32 Firmware Flashing
1. Open [firmware/esp32_firmware.ino](file:///c:/xampp/htdocs/WatertankMonitoringSystem/firmware/esp32_firmware.ino) in Arduino IDE.
2. Update `WIFI_SSID` and `WIFI_PASSWORD`.
3. Set `API_ENDPOINT` to your computer's local LAN IP:
   ```cpp
   const char* API_ENDPOINT = "http://192.168.1.239/WatertankMonitoringSystem/api/readings.php";
   ```
4. Connect the ESP32 via USB and upload the firmware.
5. Open Serial Monitor at `115200` baud to view real-time telemetry!
