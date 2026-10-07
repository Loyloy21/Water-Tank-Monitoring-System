/**
 * Interactive Smart Water Tank Visualizer
 * Renders an animated cylindrical water tank with fluid waves,
 * submerged analog water level sensor probe, level ticks, and smooth transitions.
 */

class TankVisualizer {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.container.appendChild(this.canvas);

        // Canvas sizing
        this.width = 240;
        this.height = 320;
        this.canvas.width = this.width;
        this.canvas.height = this.height;

        // Fluid animation state
        this.targetPercentage = 72.0;
        this.currentPercentage = 72.0;
        this.wavePhase = 0;
        this.pulsePhase = 0;

        this.animate = this.animate.bind(this);
        requestAnimationFrame(this.animate);
    }

    setLevel(percentage) {
        this.targetPercentage = Math.max(0, Math.min(100, Number(percentage) || 0));
    }

    animate() {
        // Smoothly interpolate current percentage toward target percentage
        this.currentPercentage += (this.targetPercentage - this.currentPercentage) * 0.05;
        this.wavePhase += 0.04;
        this.pulsePhase = (this.pulsePhase + 0.03) % 1;

        this.render();
        requestAnimationFrame(this.animate);
    }

    render() {
        const ctx = this.ctx;
        const w = this.width;
        const h = this.height;

        ctx.clearRect(0, 0, w, h);

        // Tank Geometry
        const tankX = 35;
        const tankY = 45;
        const tankW = 170;
        const tankH = 240;
        const tankRadius = 14;

        // 1. Draw Tank Outer Glass Shell (Cylinder)
        ctx.save();
        ctx.beginPath();
        ctx.roundRect(tankX, tankY, tankW, tankH, tankRadius);
        ctx.fillStyle = 'rgba(235, 243, 253, 0.45)';
        ctx.fill();
        ctx.lineWidth = 3;
        ctx.strokeStyle = '#3C7EDA';
        ctx.stroke();
        ctx.restore();

        // 2. Fluid Region (clipped inside the tank)
        ctx.save();
        ctx.beginPath();
        ctx.roundRect(tankX, tankY, tankW, tankH, tankRadius);
        ctx.clip();

        // Calculate water surface position (tankY + tankH = bottom, tankY = 100%)
        const fluidHeight = (this.currentPercentage / 100) * (tankH - 10);
        const waterSurfaceY = (tankY + tankH) - fluidHeight;

        // Draw Water Wave Surface & Body
        ctx.beginPath();
        ctx.moveTo(tankX, tankY + tankH);
        ctx.lineTo(tankX, waterSurfaceY);

        // Generate sine wave curve across the water surface
        const waveAmp = this.currentPercentage > 1 && this.currentPercentage < 99 ? 4.5 : 1;
        for (let x = tankX; x <= tankX + tankW; x += 5) {
            const relX = (x - tankX) / tankW;
            const waveY = waterSurfaceY + Math.sin(relX * Math.PI * 3 + this.wavePhase) * waveAmp;
            ctx.lineTo(x, waveY);
        }

        ctx.lineTo(tankX + tankW, tankY + tankH);
        ctx.closePath();

        // Water Fluid Gradient depending on level status
        let grad = ctx.createLinearGradient(tankX, waterSurfaceY, tankX + tankW, tankY + tankH);
        if (this.currentPercentage <= 15) {
            // Critical: Red/Crimson
            grad.addColorStop(0, 'rgba(220, 60, 60, 0.85)');
            grad.addColorStop(1, 'rgba(150, 20, 20, 0.95)');
        } else if (this.currentPercentage < 30) {
            // Low: Amber/Red-Orange
            grad.addColorStop(0, 'rgba(242, 161, 38, 0.85)');
            grad.addColorStop(1, 'rgba(210, 80, 20, 0.9)');
        } else if (this.currentPercentage < 70) {
            // Medium: Cyan / Blue
            grad.addColorStop(0, 'rgba(56, 189, 248, 0.85)');
            grad.addColorStop(1, 'rgba(37, 99, 235, 0.9)');
        } else {
            // High / Optimal: Vibrant Ocean Blue
            grad.addColorStop(0, 'rgba(60, 126, 218, 0.85)');
            grad.addColorStop(1, 'rgba(23, 84, 171, 0.92)');
        }

        ctx.fillStyle = grad;
        ctx.fill();

        // Water surface crest shine
        ctx.strokeStyle = 'rgba(255, 255, 255, 0.6)';
        ctx.lineWidth = 2.5;
        ctx.stroke();

        // Tank interior glass reflection highlight
        const shineGrad = ctx.createLinearGradient(tankX, tankY, tankX + 40, tankY);
        shineGrad.addColorStop(0, 'rgba(255, 255, 255, 0.4)');
        shineGrad.addColorStop(1, 'rgba(255, 255, 255, 0.0)');
        ctx.fillStyle = shineGrad;
        ctx.fillRect(tankX + 4, tankY + 4, 30, tankH - 8);

        ctx.restore();

        // 3. Draw Analog Water Level Sensor Probe Submerged in Tank
        const sensorX = w / 2 - 12;
        const sensorY = 22;
        const sensorW = 24;
        const sensorH = 250;

        ctx.save();
        // Top Wiring (VCC, GND, Signal) leading into tank
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#DC2626'; // Red (VCC)
        ctx.beginPath();
        ctx.moveTo(sensorX + 5, 4);
        ctx.lineTo(sensorX + 5, sensorY);
        ctx.stroke();

        ctx.strokeStyle = '#1E293B'; // Black (GND)
        ctx.beginPath();
        ctx.moveTo(sensorX + 12, 4);
        ctx.lineTo(sensorX + 12, sensorY);
        ctx.stroke();

        ctx.strokeStyle = '#3C7EDA'; // Blue/Yellow (Signal AOUT)
        ctx.beginPath();
        ctx.moveTo(sensorX + 19, 4);
        ctx.lineTo(sensorX + 19, sensorY);
        ctx.stroke();

        // Sensor Top Bracket / Header
        ctx.fillStyle = '#0F172A';
        ctx.strokeStyle = '#3C7EDA';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.roundRect(sensorX - 4, sensorY, sensorW + 8, 20, 4);
        ctx.fill();
        ctx.stroke();

        // Power Indicator LED on sensor head
        ctx.fillStyle = '#22C55E';
        ctx.beginPath();
        ctx.arc(sensorX + 4, sensorY + 10, 2.5, 0, Math.PI * 2);
        ctx.fill();

        // Signal Activity LED
        ctx.fillStyle = this.currentPercentage > 15 ? '#F59E0B' : '#EF4444';
        ctx.beginPath();
        ctx.arc(sensorX + sensorW - 4, sensorY + 10, 2.5, 0, Math.PI * 2);
        ctx.fill();

        // Submersible Sensor PCB Strip
        ctx.fillStyle = '#1E293B';
        ctx.strokeStyle = '#475569';
        ctx.lineWidth = 1.5;
        ctx.beginPath();
        ctx.roundRect(sensorX, sensorY + 20, sensorW, sensorH - 20, [0, 0, 6, 6]);
        ctx.fill();
        ctx.stroke();

        // Conductive Grid Traces on Sensor Probe
        const traceStartY = sensorY + 30;
        const traceEndY = sensorY + sensorH - 10;
        ctx.lineWidth = 1.5;

        for (let y = traceStartY; y < traceEndY; y += 8) {
            // Check if this portion of the sensor is underwater
            const isSubmerged = y >= waterSurfaceY;
            ctx.strokeStyle = isSubmerged ? '#EAB308' : '#64748B'; // Gold when wet, silver when dry

            ctx.beginPath();
            ctx.moveTo(sensorX + 4, y);
            ctx.lineTo(sensorX + sensorW - 4, y);
            ctx.stroke();
        }

        // Submersion Glow Ring at water contact point
        if (waterSurfaceY > sensorY + 20 && waterSurfaceY < sensorY + sensorH) {
            ctx.strokeStyle = 'rgba(56, 189, 248, 0.8)';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.ellipse(w / 2, waterSurfaceY, sensorW / 2 + 6 + this.pulsePhase * 4, 3, 0, 0, Math.PI * 2);
            ctx.stroke();
        }

        ctx.restore();

        // 4. Measurement Gauge Ticks (0%, 25%, 50%, 75%, 100%)
        ctx.save();
        ctx.fillStyle = '#64748B';
        ctx.font = '600 10px Plus Jakarta Sans, sans-serif';
        ctx.textAlign = 'right';

        const ticks = [
            { pct: 100, label: '100%' },
            { pct: 75,  label: '75%' },
            { pct: 50,  label: '50%' },
            { pct: 25,  label: '25%' },
            { pct: 0,   label: '0%' }
        ];

        ticks.forEach(t => {
            const tickY = (tankY + tankH - 5) - (t.pct / 100) * (tankH - 10);
            
            // Left tick mark
            ctx.strokeStyle = '#94A3B8';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(tankX - 4, tickY);
            ctx.lineTo(tankX + 4, tickY);
            ctx.stroke();

            // Label
            ctx.fillText(t.label, tankX - 8, tickY + 3);
        });

        ctx.restore();
    }
}
