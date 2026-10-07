# ESP32 Firmware Setup & Flashing Guide

This guide walks you through compiling and uploading the firmware to your ESP32 board for the **IoT-Based Smart Water Tank Monitoring System** using an **Analog Water Level Sensor**.

---

## 1. Required Hardware Components
- 1x **ESP32 DevKit V1** (30-pin or 38-pin)
- 1x **Analog Water Level Sensor** (Submerged Probe / Water Depth Sensor)
- 3x **LEDs** (1x Green, 1x Yellow, 1x Red)
- 3x **220Ω – 330Ω Resistors** (for LEDs)
- 1x **5V Active Buzzer**
- 1x Breadboard & Jumper wires
- 1x Micro-USB or USB-C Data Cable

---

## 2. Wiring Summary
Refer to [wiring_diagram.txt](file:///c:/xampp/htdocs/WatertankMonitoringSystem/firmware/wiring_diagram.txt) for the detailed schematic.

| Sensor / Actuator | Pin | ESP32 GPIO | Resistor / Protection |
|---|---|---|---|
| **Water Level Sensor** | VCC (+) | 3.3V / VIN | 3.3V direct |
| **Water Level Sensor** | GND (-) | GND | Common Ground |
| **Water Level Sensor** | SIGNAL (AOUT) | GPIO 34 | Analog ADC1 Input (Configurable) |
| **Green LED** | Anode (+) | GPIO 25 | 220Ω resistor to GPIO |
| **Yellow LED** | Anode (+) | GPIO 26 | 220Ω resistor to GPIO |
| **Red LED** | Anode (+) | GPIO 27 | 220Ω resistor to GPIO |
| **Active Buzzer** | Positive (+) | GPIO 14 | Direct connection |
| **All Grounds** | Cathode / - | GND | Common Breadboard Ground Rail |

---

## 3. Sensor Calibration Guide
The analog sensor outputs a voltage proportional to water depth:
- **0% (Empty Tank)**: When the sensor is completely dry, the raw ADC reading is around `500` (`SENSOR_MIN_RAW`).
- **100% (Full Tank)**: When the sensor is submerged to max line, the raw ADC reading is around `2800` (`SENSOR_MAX_RAW`).

You can adjust these in [esp32_firmware.ino](file:///c:/xampp/htdocs/WatertankMonitoringSystem/firmware/esp32_firmware.ino):
```cpp
const int SENSOR_MIN_RAW = 500;   // Lowest sensor value = 0% water level
const int SENSOR_MAX_RAW = 2800;  // Highest sensor value = 100% water level
```

---

## 4. Arduino IDE Setup Instructions

### Step 1: Install ESP32 Board Package
1. Open **Arduino IDE**.
2. Go to **File → Preferences**.
3. In **Additional Boards Manager URLs**, paste:
   ```text
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
4. Click **OK**.
5. Go to **Tools → Board → Boards Manager**, search for `esp32` by Espressif Systems, and click **Install**.

### Step 2: Install ArduinoJson Library
1. Go to **Sketch → Include Library → Manage Libraries...**.
2. Search for `ArduinoJson` (by Benoit Blanchon).
3. Click **Install** (Version 6.x or 7.x are both supported).

### Step 3: Configure Sketch
Open [esp32_firmware.ino](file:///c:/xampp/htdocs/WatertankMonitoringSystem/firmware/esp32_firmware.ino) in Arduino IDE.
Update the following lines with your local Wi-Fi settings:

```cpp
const char* WIFI_SSID     = "YOUR_WIFI_NAME";        // Your 2.4GHz Wi-Fi SSID
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";    // Your Wi-Fi Password

// Replace 192.168.1.239 with your computer's local LAN IP:
const char* API_ENDPOINT  = "http://192.168.1.239/WatertankMonitoringSystem/api/readings.php";
```

### Step 4: Upload Firmware
1. Connect your ESP32 to the PC via USB.
2. Under **Tools → Board**, select **DOIT ESP32 DEVKIT V1**.
3. Under **Tools → Port**, select the COM port for your ESP32 (e.g. `COM3` or `COM4`).
4. Click **Upload** (Arrow icon).
5. Once upload completes, open **Tools → Serial Monitor** at `115200` baud.
6. Dip the sensor into water to observe real-time ADC readings, percentage updates, LED toggles, and HTTP transmission!
