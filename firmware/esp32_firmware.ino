/*
 * ==============================================================================
 * Project: IoT-Based Smart Water Tank Monitoring System Using ESP32
 * Sensor: Analog Water Level Sensor (Submerged / Probe / Depth Sensor)
 * Features:
 *   - Real-Time Analog Water Level Sensing via ESP32 ADC Pin
 *   - Configurable Calibration (Lowest Sensor Value = 0%, Highest = 100%)
 *   - Water Level Percentage (0% - 100%) and Volume (Liters) Calculations
 *   - Multilevel LED Status Indicators (Green = High, Yellow = Medium, Red = Low/Crit)
 *   - Audible Warning Buzzer (Critical Level <= 15%)
 *   - Wi-Fi Connectivity with Automatic Reconnection Watchdog
 *   - HTTP POST REST API Transmission with JSON Payload & API Authentication
 * ==============================================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h> // ArduinoJson v6 or v7

// ==========================================
// 1. NETWORK & BACKEND CONFIGURATION
// ==========================================
const char* WIFI_SSID     = "YOUR_WIFI_SSID";         // Replace with your 2.4GHz Wi-Fi SSID
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";     // Replace with your Wi-Fi Password

// URL of your backend API server (Update with your computer's LAN IP, e.g., 192.168.1.239)
const char* API_ENDPOINT  = "http://192.168.1.239/WatertankMonitoringSystem/api/readings.php";

// API Authentication Key (Must match API_KEY in backend .env)
const char* API_KEY       = "smarttank_secret_key_2026";
const char* DEVICE_ID     = "SMART-TANK-001";

// ==========================================
// 2. PIN DEFINITIONS (CONFIGURABLE)
// ==========================================
// Analog Water Level Sensor Pin (ADC1 channel: GPIO 34, 35, 36, or 39 - works alongside Wi-Fi)
#define SENSOR_ADC_PIN 34

// LED Status Pins
#define PIN_LED_GREEN  25  // HIGH (70% - 100%)
#define PIN_LED_YELLOW 26  // MEDIUM (30% - 69%)
#define PIN_LED_RED    27  // LOW (16% - 29%) & CRITICAL (<= 15%)

// Audible Warning Buzzer Pin
#define PIN_BUZZER     14  // Active Buzzer (<= 15%)

// Built-in status LED (GPIO 2 on most ESP32 Dev Boards)
#define PIN_LED_BUILTIN 2

// ==========================================
// 3. SENSOR CALIBRATION & TANK CONFIGURATION
// ==========================================
// Calibration values: Raw 12-bit ADC reading (0 to 4095)
// SENSOR_MIN_RAW = Reading when the sensor is completely dry (0% water level)
// SENSOR_MAX_RAW = Reading when the sensor is submerged to max depth (100% water level)
// (These can easily be adjusted based on your physical demo sensor)
const int SENSOR_MIN_RAW = 500;   // Lowest sensor value = 0% water level
const int SENSOR_MAX_RAW = 2800;  // Highest sensor value = 100% water level

// Tank Capacity in Liters (for volume calculation)
const float TANK_CAPACITY_LITERS = 1000.0;

// Water Level Thresholds (%)
const float THRESHOLD_HIGH     = 70.0; // >= 70%: Green LED ON
const float THRESHOLD_MEDIUM   = 30.0; // 30% - 69%: Yellow LED ON
const float THRESHOLD_LOW      = 16.0; // 16% - 29%: Red LED ON
const float THRESHOLD_CRITICAL = 15.0; // <= 15%: Red LED ON + Buzzer Alarm

// Sampling and reporting intervals (milliseconds)
const unsigned long REPORT_INTERVAL_MS = 5000; // Send reading every 5 seconds
unsigned long lastReportTime = 0;

// Buzzer alert state
bool isBuzzerActive = false;
unsigned long buzzerLastToggle = 0;
bool buzzerState = false;

// ==========================================
// 4. FUNCTION DECLARATIONS
// ==========================================
void initHardware();
void connectWiFi();
int readFilteredSensorADC();
float calculateWaterPercentage(int rawSensorValue);
void updateOutputs(float waterPercentage, const char* status);
bool sendReadingToBackend(int rawSensorValue, float waterPercentage, float volume, const char* status);

// ==========================================
// SETUP
// ==========================================
void setup() {
    Serial.begin(115200);
    delay(1000);
    Serial.println("\n==================================================");
    Serial.println("  ESP32 WATER LEVEL MONITOR (ANALOG SENSOR)     ");
    Serial.println("==================================================");

    initHardware();
    connectWiFi();

    Serial.println("[SYSTEM] Ready! Commencing real-time monitoring loop...");
}

// ==========================================
// MAIN LOOP
// ==========================================
void loop() {
    unsigned long currentMillis = millis();

    // 1. Maintain Wi-Fi Connection
    if (WiFi.status() != WL_CONNECTED) {
        connectWiFi();
    }

    // 2. Non-blocking buzzer pulsing when in CRITICAL status
    if (isBuzzerActive) {
        if (currentMillis - buzzerLastToggle >= 250) { // 250ms ON / 250ms OFF pulsing tone
            buzzerLastToggle = currentMillis;
            buzzerState = !buzzerState;
            digitalWrite(PIN_BUZZER, buzzerState ? HIGH : LOW);
        }
    } else {
        digitalWrite(PIN_BUZZER, LOW);
    }

    // 3. Periodic Sensor Measurement & Cloud Reporting
    if (currentMillis - lastReportTime >= REPORT_INTERVAL_MS) {
        lastReportTime = currentMillis;

        // Take filtered analog sensor reading
        int rawSensorValue = readFilteredSensorADC();

        // Convert raw analog reading into water percentage (0% - 100%)
        float waterPercentage = calculateWaterPercentage(rawSensorValue);

        // Calculate estimated water volume: (waterPercentage / 100) * tankCapacity
        float waterVolumeLiters = (waterPercentage / 100.0) * TANK_CAPACITY_LITERS;

        // Determine Status based on Thresholds:
        // HIGH:     >= 70%
        // MEDIUM:   >= 30% and < 70%
        // LOW:      >= 16% and < 30%
        // CRITICAL: <= 15%
        const char* status = "HIGH";
        if (waterPercentage >= THRESHOLD_HIGH) {
            status = "HIGH";
        } else if (waterPercentage >= THRESHOLD_MEDIUM) {
            status = "MEDIUM";
        } else if (waterPercentage >= THRESHOLD_LOW) {
            status = "LOW";
        } else {
            status = "CRITICAL";
        }

        // Update physical LEDs and Buzzer state
        updateOutputs(waterPercentage, status);

        // Print Diagnostic Telemetry to Serial Monitor
        Serial.println("--------------------------------------------------");
        Serial.printf("[SENSOR] Raw ADC: %d (Calibrated Range: %d - %d)\n", rawSensorValue, SENSOR_MIN_RAW, SENSOR_MAX_RAW);
        Serial.printf("[METRICS] Water Level: %.1f%% | Volume: %.1f / %.1f L\n", waterPercentage, waterVolumeLiters, TANK_CAPACITY_LITERS);
        Serial.printf("[STATUS]  %s (LEDs updated, Buzzer: %s)\n", status, isBuzzerActive ? "ACTIVE" : "OFF");

        // Transmit Reading to Backend API
        if (WiFi.status() == WL_CONNECTED) {
            sendReadingToBackend(rawSensorValue, waterPercentage, waterVolumeLiters, status);
        } else {
            Serial.println("[WARNING] Cannot send reading - Wi-Fi disconnected!");
        }
    }
}

// ==========================================
// HARDWARE INITIALIZATION
// ==========================================
void initHardware() {
    // Configure ADC pin resolution (12-bit: 0 - 4095)
    analogReadResolution(12);
    pinMode(SENSOR_ADC_PIN, INPUT);

    pinMode(PIN_LED_GREEN, OUTPUT);
    pinMode(PIN_LED_YELLOW, OUTPUT);
    pinMode(PIN_LED_RED, OUTPUT);
    pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_BUILTIN, OUTPUT);

    // Initial output states
    digitalWrite(PIN_LED_GREEN, LOW);
    digitalWrite(PIN_LED_YELLOW, LOW);
    digitalWrite(PIN_LED_RED, LOW);
    digitalWrite(PIN_BUZZER, LOW);
    digitalWrite(PIN_LED_BUILTIN, LOW);

    // Startup LED & Buzzer confirmation test
    digitalWrite(PIN_LED_GREEN, HIGH);
    delay(200);
    digitalWrite(PIN_LED_YELLOW, HIGH);
    delay(200);
    digitalWrite(PIN_LED_RED, HIGH);
    delay(200);
    digitalWrite(PIN_BUZZER, HIGH);
    delay(100);
    digitalWrite(PIN_BUZZER, LOW);
    delay(300);
    digitalWrite(PIN_LED_GREEN, LOW);
    digitalWrite(PIN_LED_YELLOW, LOW);
    digitalWrite(PIN_LED_RED, LOW);
    Serial.println("[HARDWARE] Water Level Sensor, LEDs, and Buzzer initialized successfully.");
}

// ==========================================
// WI-FI CONNECTION & AUTO-RECONNECT
// ==========================================
void connectWiFi() {
    static unsigned long lastAttempt = 0;
    if (WiFi.status() == WL_CONNECTED) {
        return;
    }

    if (millis() - lastAttempt < 8000 && lastAttempt != 0) {
        return; // Don't flood reconnect calls
    }
    lastAttempt = millis();

    Serial.print("[WIFI] Connecting to SSID: ");
    Serial.println(WIFI_SSID);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 15) {
        delay(500);
        Serial.print(".");
        digitalWrite(PIN_LED_BUILTIN, !digitalRead(PIN_LED_BUILTIN));
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        digitalWrite(PIN_LED_BUILTIN, HIGH); // Solid ON when connected
        Serial.println("\n[WIFI] Connected Successfully!");
        Serial.print("[WIFI] IP Address: ");
        Serial.println(WiFi.localIP());
        Serial.print("[WIFI] RSSI Signal: ");
        Serial.print(WiFi.RSSI());
        Serial.println(" dBm");
    } else {
        digitalWrite(PIN_LED_BUILTIN, LOW);
        Serial.println("\n[WIFI ERROR] Connection failed. Will retry automatically.");
    }
}

// ==========================================
// SENSOR READING & CALIBRATION
// ==========================================
// Reads 10 samples and averages them to smooth out electrical noise
int readFilteredSensorADC() {
    long sum = 0;
    const int SAMPLES = 10;
    for (int i = 0; i < SAMPLES; i++) {
        sum += analogRead(SENSOR_ADC_PIN);
        delay(15);
    }
    return sum / SAMPLES;
}

// Converts raw analog reading into water percentage (0% to 100%)
// LOWEST SENSOR VALUE = 0% water level
// HIGHEST SENSOR VALUE = 100% water level
// Clamped between 0% and 100%
float calculateWaterPercentage(int rawSensorValue) {
    if (rawSensorValue <= SENSOR_MIN_RAW) {
        return 0.0;
    }
    if (rawSensorValue >= SENSOR_MAX_RAW) {
        return 100.0;
    }

    float percentage = ((float)(rawSensorValue - SENSOR_MIN_RAW) / (float)(SENSOR_MAX_RAW - SENSOR_MIN_RAW)) * 100.0;
    if (percentage < 0.0) percentage = 0.0;
    if (percentage > 100.0) percentage = 100.0;

    return round(percentage * 10.0) / 10.0;
}

// ==========================================
// LED & BUZZER OUTPUT LOGIC
// ==========================================
void updateOutputs(float waterPercentage, const char* status) {
    if (strcmp(status, "HIGH") == 0) {
        // High level (>= 70%): Green ON, Yellow OFF, Red OFF, Buzzer OFF
        digitalWrite(PIN_LED_GREEN, HIGH);
        digitalWrite(PIN_LED_YELLOW, LOW);
        digitalWrite(PIN_LED_RED, LOW);
        isBuzzerActive = false;
    } else if (strcmp(status, "MEDIUM") == 0) {
        // Medium level (30% - 69%): Green OFF, Yellow ON, Red OFF, Buzzer OFF
        digitalWrite(PIN_LED_GREEN, LOW);
        digitalWrite(PIN_LED_YELLOW, HIGH);
        digitalWrite(PIN_LED_RED, LOW);
        isBuzzerActive = false;
    } else if (strcmp(status, "LOW") == 0) {
        // Low level (16% - 29%): Green OFF, Yellow OFF, Red ON, Buzzer OFF
        digitalWrite(PIN_LED_GREEN, LOW);
        digitalWrite(PIN_LED_YELLOW, LOW);
        digitalWrite(PIN_LED_RED, HIGH);
        isBuzzerActive = false;
    } else { // CRITICAL (<= 15%)
        // Critical level (<= 15%): Green OFF, Yellow OFF, Red ON, Buzzer ON (pulsing)
        digitalWrite(PIN_LED_GREEN, LOW);
        digitalWrite(PIN_LED_YELLOW, LOW);
        digitalWrite(PIN_LED_RED, HIGH);
        isBuzzerActive = true;
    }
}

// ==========================================
// HTTP REST API TRANSMISSION
// ==========================================
bool sendReadingToBackend(int rawSensorValue, float waterPercentage, float volume, const char* status) {
    HTTPClient http;
    http.begin(API_ENDPOINT);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-Key", API_KEY);
    http.setTimeout(4000);

    // Construct JSON document
    StaticJsonDocument<256> doc;
    doc["device_id"]            = DEVICE_ID;
    doc["water_level"]          = waterPercentage;
    doc["water_percentage"]     = waterPercentage;
    doc["sensor_value"]         = rawSensorValue;
    doc["water_volume_liters"]  = round(volume * 10.0) / 10.0;
    doc["tank_capacity_liters"] = TANK_CAPACITY_LITERS;
    doc["status"]               = status;
    doc["api_key"]              = API_KEY;

    String jsonString;
    serializeJson(doc, jsonString);

    int httpResponseCode = http.POST(jsonString);

    if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.printf("[HTTP] POST Result Code: %d\n", httpResponseCode);
        if (httpResponseCode == 200 || httpResponseCode == 201) {
            Serial.println("[HTTP] Server Response: Success!");
            http.end();
            return true;
        } else {
            Serial.printf("[HTTP ERROR] Server returned: %s\n", response.c_str());
        }
    } else {
        Serial.printf("[HTTP ERROR] Request failed: %s\n", http.errorToString(httpResponseCode).c_str());
    }

    http.end();
    return false;
}
