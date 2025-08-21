(function() {
  "use strict";

  /**
   * A tiny utility for element creation.
   */
  function createElement(tag, options) {
    const element = document.createElement(tag);
    if (options) {
      if (options.className) element.className = options.className;
      if (options.text) element.textContent = options.text;
      if (options.html) element.innerHTML = options.html;
      if (options.attrs) {
        Object.keys(options.attrs).forEach((key) => element.setAttribute(key, options.attrs[key]));
      }
    }
    return element;
  }

  function formatNumber(num) {
    return Number(num).toFixed(2).replace(/\.00$/, "");
  }

  /**
   * RoomDesigner provides a simple UI and canvas renderer for a rectangular room.
   */
  class RoomDesigner {
    constructor(container) {
      this.container = container;
      this.config = this.readInitialConfig();
      this.state = this.createInitialState();
      this.elements = {};
      this.initialize();
    }

    readInitialConfig() {
      const raw = this.container.getAttribute("data-config");
      let base = { unit: "ft", roomWidth: 16, roomDepth: 20, ceilingHeight: 9, screenWidth: 10 };
      if (raw) {
        try { base = Object.assign(base, JSON.parse(raw)); } catch (e) {}
      }
      return base;
    }

    createInitialState() {
      return {
        unit: this.config.unit,
        roomWidth: this.config.roomWidth,
        roomDepth: this.config.roomDepth,
        ceilingHeight: this.config.ceilingHeight,
        screenWidth: this.config.screenWidth,
        seatingRows: 2,
        seatsPerRow: 4,
        aisleWidth: 2,
        includeSurrounds: true,
        includeRear: true,
        includeCeiling: false,
        canvasWidth: 900,
        canvasHeight: 540
      };
    }

    initialize() {
      this.container.innerHTML = "";
      this.buildLayout();
      this.attachEvents();
      this.draw();
    }

    buildLayout() {
      const root = createElement("div", { className: "wptd" });

      const leftPanel = createElement("div", { className: "wptd__panel" });
      leftPanel.appendChild(this.buildDimensionsSection());
      leftPanel.appendChild(this.buildSeatingSection());
      leftPanel.appendChild(this.buildSpeakersSection());
      leftPanel.appendChild(this.buildActionsSection());

      const rightPanel = createElement("div", { className: "wptd__stage" });
      const canvas = createElement("canvas", { className: "wptd__canvas", attrs: { width: String(this.state.canvasWidth), height: String(this.state.canvasHeight) } });

      this.elements.canvas = canvas;
      rightPanel.appendChild(canvas);

      root.appendChild(leftPanel);
      root.appendChild(rightPanel);
      this.container.appendChild(root);
    }

    buildField(labelText, inputEl) {
      const field = createElement("div", { className: "wptd-field" });
      const label = createElement("label", { className: "wptd-field__label", text: labelText });
      const control = createElement("div", { className: "wptd-field__control" });
      control.appendChild(inputEl);
      field.appendChild(label);
      field.appendChild(control);
      return field;
    }

    buildDimensionsSection() {
      const section = createElement("section", { className: "wptd-section" });
      section.appendChild(createElement("h3", { text: "Room Dimensions" }));

      const unitSelect = createElement("select");
      [
        { value: "ft", label: "Feet" },
        { value: "m", label: "Meters" }
      ].forEach(opt => {
        const el = createElement("option", { text: opt.label });
        el.value = opt.value;
        if (opt.value === this.state.unit) el.selected = true;
        unitSelect.appendChild(el);
      });
      unitSelect.addEventListener("change", () => { this.state.unit = unitSelect.value; this.draw(); });

      const widthInput = createElement("input", { attrs: { type: "number", min: "4", step: "0.1", value: String(this.state.roomWidth) } });
      widthInput.addEventListener("input", () => { this.state.roomWidth = parseFloat(widthInput.value || "0"); this.draw(); });

      const depthInput = createElement("input", { attrs: { type: "number", min: "6", step: "0.1", value: String(this.state.roomDepth) } });
      depthInput.addEventListener("input", () => { this.state.roomDepth = parseFloat(depthInput.value || "0"); this.draw(); });

      const heightInput = createElement("input", { attrs: { type: "number", min: "7", step: "0.1", value: String(this.state.ceilingHeight) } });
      heightInput.addEventListener("input", () => { this.state.ceilingHeight = parseFloat(heightInput.value || "0"); this.draw(); });

      const screenInput = createElement("input", { attrs: { type: "number", min: "4", step: "0.1", value: String(this.state.screenWidth) } });
      screenInput.addEventListener("input", () => { this.state.screenWidth = parseFloat(screenInput.value || "0"); this.draw(); });

      section.appendChild(this.buildField("Units", unitSelect));
      section.appendChild(this.buildField(`Width (${this.state.unit})`, widthInput));
      section.appendChild(this.buildField(`Depth (${this.state.unit})`, depthInput));
      section.appendChild(this.buildField(`Ceiling (${this.state.unit})`, heightInput));
      section.appendChild(this.buildField(`Screen width (${this.state.unit})`, screenInput));

      this.elements.unitSelect = unitSelect;
      this.elements.widthInput = widthInput;
      this.elements.depthInput = depthInput;
      this.elements.heightInput = heightInput;
      this.elements.screenInput = screenInput;

      return section;
    }

    buildSeatingSection() {
      const section = createElement("section", { className: "wptd-section" });
      section.appendChild(createElement("h3", { text: "Seating" }));

      const rowsInput = createElement("input", { attrs: { type: "number", min: "1", max: "5", step: "1", value: String(this.state.seatingRows) } });
      rowsInput.addEventListener("input", () => { this.state.seatingRows = Math.max(1, Math.min(5, parseInt(rowsInput.value || "1", 10))); this.draw(); });

      const seatsInput = createElement("input", { attrs: { type: "number", min: "1", max: "10", step: "1", value: String(this.state.seatsPerRow) } });
      seatsInput.addEventListener("input", () => { this.state.seatsPerRow = Math.max(1, Math.min(10, parseInt(seatsInput.value || "1", 10))); this.draw(); });

      const aisleInput = createElement("input", { attrs: { type: "number", min: "0", max: "6", step: "0.1", value: String(this.state.aisleWidth) } });
      aisleInput.addEventListener("input", () => { this.state.aisleWidth = Math.max(0, parseFloat(aisleInput.value || "0")); this.draw(); });

      section.appendChild(this.buildField("Rows", rowsInput));
      section.appendChild(this.buildField("Seats per row", seatsInput));
      section.appendChild(this.buildField(`Aisle width (${this.state.unit})`, aisleInput));

      this.elements.rowsInput = rowsInput;
      this.elements.seatsInput = seatsInput;
      this.elements.aisleInput = aisleInput;

      return section;
    }

    buildSpeakersSection() {
      const section = createElement("section", { className: "wptd-section" });
      section.appendChild(createElement("h3", { text: "Speakers" }));

      const surroundsToggle = createElement("input", { attrs: { type: "checkbox" } });
      surroundsToggle.checked = this.state.includeSurrounds;
      surroundsToggle.addEventListener("change", () => { this.state.includeSurrounds = surroundsToggle.checked; this.draw(); });

      const rearToggle = createElement("input", { attrs: { type: "checkbox" } });
      rearToggle.checked = this.state.includeRear;
      rearToggle.addEventListener("change", () => { this.state.includeRear = rearToggle.checked; this.draw(); });

      const ceilingToggle = createElement("input", { attrs: { type: "checkbox" } });
      ceilingToggle.checked = this.state.includeCeiling;
      ceilingToggle.addEventListener("change", () => { this.state.includeCeiling = ceilingToggle.checked; this.draw(); });

      section.appendChild(this.buildField("Side surrounds", surroundsToggle));
      section.appendChild(this.buildField("Rear surrounds", rearToggle));
      section.appendChild(this.buildField("Ceiling (Atmos)", ceilingToggle));

      return section;
    }

    buildActionsSection() {
      const section = createElement("section", { className: "wptd-section" });
      section.appendChild(createElement("h3", { text: "Actions" }));

      const exportPng = createElement("button", { className: "wptd-btn", text: "Export PNG" });
      exportPng.addEventListener("click", () => this.exportPNG());

      const exportJson = createElement("button", { className: "wptd-btn", text: "Export JSON" });
      exportJson.addEventListener("click", () => this.exportJSON());

      const resetBtn = createElement("button", { className: "wptd-btn wptd-btn--secondary", text: "Reset" });
      resetBtn.addEventListener("click", () => { this.state = this.createInitialState(); this.initialize(); });

      const inlineInfo = createElement("div", { className: "wptd-help", html: "Use inputs to configure your room. The canvas updates live." });

      const btnRow = createElement("div", { className: "wptd-actions" });
      btnRow.appendChild(exportPng);
      btnRow.appendChild(exportJson);
      btnRow.appendChild(resetBtn);

      section.appendChild(btnRow);
      section.appendChild(inlineInfo);
      return section;
    }

    getScale() {
      // Fit room rectangle within canvas with padding
      const padding = 40;
      const availableWidth = this.state.canvasWidth - padding * 2;
      const availableHeight = this.state.canvasHeight - padding * 2;
      const scaleX = availableWidth / this.state.roomWidth;
      const scaleY = availableHeight / this.state.roomDepth;
      const scale = Math.min(scaleX, scaleY);
      const originX = (this.state.canvasWidth - this.state.roomWidth * scale) / 2;
      const originY = (this.state.canvasHeight - this.state.roomDepth * scale) / 2;
      return { scale, originX, originY, padding };
    }

    draw() {
      const canvas = this.elements.canvas;
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      const { scale, originX, originY } = this.getScale();

      // Draw room background
      ctx.fillStyle = "#f7f8fb";
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // Room rectangle
      ctx.strokeStyle = "#1f2937";
      ctx.lineWidth = 2;
      ctx.strokeRect(originX, originY, this.state.roomWidth * scale, this.state.roomDepth * scale);

      // Screen at front (top edge)
      const screenWidthPx = this.state.screenWidth * scale;
      const screenX = originX + (this.state.roomWidth * scale - screenWidthPx) / 2;
      const screenY = originY - 8;
      ctx.fillStyle = "#111827";
      ctx.fillRect(screenX, screenY, screenWidthPx, 6);

      // Draw speakers: L, C, R front
      const speakerSize = 10;
      const frontY = originY + 6;
      const leftX = originX + (this.state.roomWidth * scale) / 2 - screenWidthPx / 2 - 20;
      const rightX = originX + (this.state.roomWidth * scale) / 2 + screenWidthPx / 2 + 10;
      const centerX = originX + (this.state.roomWidth * scale) / 2 - speakerSize / 2;

      ctx.fillStyle = "#2563eb";
      this.drawSpeaker(ctx, leftX, frontY, speakerSize);
      this.drawSpeaker(ctx, centerX, frontY, speakerSize);
      this.drawSpeaker(ctx, rightX, frontY, speakerSize);

      // Side surrounds
      if (this.state.includeSurrounds) {
        const sideOffset = 14;
        const sideY = originY + (this.state.roomDepth * scale) / 2;
        this.drawSpeaker(ctx, originX - sideOffset, sideY, speakerSize);
        this.drawSpeaker(ctx, originX + this.state.roomWidth * scale + sideOffset - speakerSize, sideY, speakerSize);
      }

      // Rear surrounds
      if (this.state.includeRear) {
        const rearY = originY + this.state.roomDepth * scale - 18;
        const rearLeftX = originX + 20;
        const rearRightX = originX + this.state.roomWidth * scale - 30;
        this.drawSpeaker(ctx, rearLeftX, rearY, speakerSize);
        this.drawSpeaker(ctx, rearRightX, rearY, speakerSize);
      }

      // Ceiling speakers
      if (this.state.includeCeiling) {
        ctx.fillStyle = "#059669";
        const midX = originX + (this.state.roomWidth * scale) / 2;
        const q1Y = originY + (this.state.roomDepth * scale) / 3;
        const q2Y = originY + (this.state.roomDepth * scale) * 2 / 3;
        this.drawCeilingSpeaker(ctx, midX - 70, q1Y, 8);
        this.drawCeilingSpeaker(ctx, midX + 70, q1Y, 8);
        this.drawCeilingSpeaker(ctx, midX - 70, q2Y, 8);
        this.drawCeilingSpeaker(ctx, midX + 70, q2Y, 8);
      }

      // Seating rows
      this.drawSeating(ctx, originX, originY, scale);

      // Dimension labels
      this.drawDimensions(ctx, originX, originY, scale);
    }

    drawSpeaker(ctx, x, y, size) {
      ctx.fillStyle = "#2563eb";
      ctx.fillRect(x, y, size, size);
      ctx.strokeStyle = "#1f2937";
      ctx.lineWidth = 1;
      ctx.strokeRect(x, y, size, size);
    }

    drawCeilingSpeaker(ctx, x, y, r) {
      ctx.beginPath();
      ctx.arc(x, y, r, 0, Math.PI * 2);
      ctx.fill();
      ctx.strokeStyle = "#065f46";
      ctx.stroke();
    }

    drawSeating(ctx, originX, originY, scale) {
      const seatWidth = 2.2;   // unit width
      const seatDepth = 2;     // unit depth
      const rowSpacing = 4;    // spacing between rows (unit)
      const aisle = this.state.aisleWidth;
      const totalRowWidthUnits = this.state.seatsPerRow * seatWidth + aisle;
      const startX = originX + (this.state.roomWidth * scale - totalRowWidthUnits * scale) / 2;
      const startY = originY + (this.state.roomDepth * scale) / 2;

      ctx.fillStyle = "#9ca3af";
      ctx.strokeStyle = "#4b5563";
      ctx.lineWidth = 1;

      for (let r = 0; r < this.state.seatingRows; r++) {
        const rowY = startY + r * rowSpacing * scale;
        // left seats
        for (let s = 0; s < Math.floor(this.state.seatsPerRow / 2); s++) {
          const x = startX + s * seatWidth * scale;
          this.drawSeat(ctx, x, rowY, seatWidth * scale, seatDepth * scale);
        }
        // right seats (after aisle)
        const rightStart = startX + (Math.floor(this.state.seatsPerRow / 2) * seatWidth + aisle) * scale;
        const rightCount = this.state.seatsPerRow - Math.floor(this.state.seatsPerRow / 2);
        for (let s = 0; s < rightCount; s++) {
          const x = rightStart + s * seatWidth * scale;
          this.drawSeat(ctx, x, rowY, seatWidth * scale, seatDepth * scale);
        }
      }
    }

    drawSeat(ctx, x, y, w, h) {
      ctx.fillRect(x, y, w, h);
      ctx.strokeRect(x, y, w, h);
    }

    drawDimensions(ctx, originX, originY, scale) {
      ctx.fillStyle = "#111827";
      ctx.font = "12px sans-serif";
      ctx.textAlign = "center";
      ctx.fillText(`${formatNumber(this.state.roomWidth)} ${this.state.unit}`, originX + (this.state.roomWidth * scale) / 2, originY - 16);
      ctx.save();
      ctx.textAlign = "right";
      ctx.translate(originX - 10, originY + (this.state.roomDepth * scale) / 2);
      ctx.rotate(-Math.PI / 2);
      ctx.fillText(`${formatNumber(this.state.roomDepth)} ${this.state.unit}`, 0, 0);
      ctx.restore();
    }

    exportPNG() {
      const link = document.createElement("a");
      link.download = `theater-design-${Date.now()}.png`;
      link.href = this.elements.canvas.toDataURL("image/png");
      link.click();
    }

    exportJSON() {
      const data = {
        unit: this.state.unit,
        roomWidth: this.state.roomWidth,
        roomDepth: this.state.roomDepth,
        ceilingHeight: this.state.ceilingHeight,
        screenWidth: this.state.screenWidth,
        seatingRows: this.state.seatingRows,
        seatsPerRow: this.state.seatsPerRow,
        aisleWidth: this.state.aisleWidth,
        includeSurrounds: this.state.includeSurrounds,
        includeRear: this.state.includeRear,
        includeCeiling: this.state.includeCeiling
      };
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `theater-design-${Date.now()}.json`;
      link.click();
      setTimeout(() => URL.revokeObjectURL(url), 5000);
    }

    attachEvents() {
      // Canvas resize on window resize for responsiveness
      const onResize = () => {
        const rect = this.container.getBoundingClientRect();
        const targetWidth = Math.max(600, Math.min(1200, rect.width - 320));
        const aspect = 16 / 9;
        this.state.canvasWidth = Math.round(targetWidth);
        this.state.canvasHeight = Math.round(targetWidth / aspect);
        this.elements.canvas.width = this.state.canvasWidth;
        this.elements.canvas.height = this.state.canvasHeight;
        this.draw();
      };
      window.addEventListener("resize", onResize);
      // Initialize once
      onResize();
    }
  }

  function mount(containerId) {
    const el = document.getElementById(containerId);
    if (!el) return;
    new RoomDesigner(el);
  }

  // Expose as global for inline bootstrap
  window.WPTheaterDesigner = { mount };
})();