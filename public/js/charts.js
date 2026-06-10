// ============================================
// COMMON CHART CONFIGURATIONS
// ============================================

const ChartDefaults = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      position: "bottom",
      labels: { padding: 15, font: { size: 12 } },
    },
    tooltip: {
      backgroundColor: "rgba(0,0,0,0.8)",
      padding: 12,
      titleFont: { size: 14 },
      bodyFont: { size: 13 },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 },
      grid: { color: "rgba(0,0,0,0.05)" },
    },
    x: {
      grid: { display: false },
    },
  },
};

const mergeChartOptions = (baseOptions, customOptions) => {
  return {
    ...baseOptions,
    ...customOptions,
    plugins: { ...baseOptions.plugins, ...customOptions.plugins },
    scales: { ...baseOptions.scales, ...customOptions.scales },
  };
};

// ============================================
// BASE CHART CLASS
// ============================================
class BaseChartManager {
  constructor() {
    this.charts = {};
  }

  createChart(id, config) {
    const ctx = document.getElementById(id);
    if (!ctx) {
      console.warn(`Canvas element with id "${id}" not found`);
      return null;
    }

    // Destroy existing chart if exists
    if (this.charts[id]) {
      this.charts[id].destroy();
    }

    this.charts[id] = new Chart(ctx, config);
    return this.charts[id];
  }

  destroyChart(id) {
    if (this.charts[id]) {
      this.charts[id].destroy();
      delete this.charts[id];
    }
  }

  destroyAll() {
    Object.keys(this.charts).forEach((id) => this.destroyChart(id));
  }
}

// ============================================
// DASHBOARD CHARTS (index.php)
// ============================================
class DashboardCharts extends BaseChartManager {
  init(data) {
    if (data.stats) this.initStatusChart(data.stats);
    if (data.incidentTrend) this.initIncidentTrendChart(data.incidentTrend);
    if (data.responseTimeData)
      this.initResponseTimeChart(data.responseTimeData);
  }

  initStatusChart(stats) {
    this.createChart("statusChart", {
      type: "doughnut",
      data: {
        labels: ["Aktif", "Nonaktif"],
        datasets: [
          {
            data: [stats.active_sites || 0, stats.nonactive_sites || 0],
            backgroundColor: ["#198754", "#6c757d"],
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: "bottom" },
          title: { display: true, text: "Status Website" },
        },
      },
    });
  }

  initIncidentTrendChart(incidentTrend) {
    this.createChart("incidentChart", {
      type: "bar",
      data: {
        labels: incidentTrend.labels || [],
        datasets: [
          {
            label: "Jumlah Insiden",
            data: incidentTrend.data || [],
            backgroundColor: "rgba(220, 53, 69, 0.7)",
          },
        ],
      },
      options: mergeChartOptions(ChartDefaults, {
        plugins: {
          title: { display: true, text: "Tren Insiden 7 Hari Terakhir" },
        },
      }),
    });
  }

  // Format timestamp "2025-03-17 10:10:00" → "10:10"
  // Jika ada data dari hari berbeda, tampilkan "17/03 10:10"
  _formatTimeLabels(times) {
    if (!times || times.length === 0) return [];

    // Cek apakah semua timestamp berasal dari hari yang sama
    const dates = times.map((t) => t.substring(0, 10));
    const uniqueDates = [...new Set(dates)];
    const multiDay = uniqueDates.length > 1;

    return times.map((t) => {
      // t format: "YYYY-MM-DD HH:MM:SS"
      const parts = t.split(" ");
      const timePart = parts[1] ? parts[1].substring(0, 5) : t; // "HH:MM"
      if (multiDay) {
        const dateParts = parts[0].split("-"); // ["YYYY","MM","DD"]
        return `${dateParts[2]}/${dateParts[1]} ${timePart}`;
      }
      return timePart;
    });
  }

  initResponseTimeChart(responseTimeData) {
    if (!responseTimeData || Object.keys(responseTimeData).length === 0) return;

    const firstSite = Object.keys(responseTimeData)[0];
    const siteData = responseTimeData[firstSite] || { times: [], values: [] };
    const labels = this._formatTimeLabels(siteData.times);

    this.createChart("uptimeChart", {
      type: "line",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Waktu Respon (ms)",
            data: siteData.values || [],
            borderColor: "rgba(13, 110, 253, 1)",
            backgroundColor: "rgba(13, 110, 253, 0.1)",
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 6,
          },
        ],
      },
      options: mergeChartOptions(ChartDefaults, {
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: (items) => {
                // Tampilkan timestamp asli di tooltip
                const idx = items[0]?.dataIndex;
                return siteData.times?.[idx] ?? items[0]?.label ?? "";
              },
              label: (item) => ` ${item.raw} ms`,
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: {
              maxTicksLimit: 12, // Batasi agar tidak terlalu padat
              maxRotation: 45,
              minRotation: 0,
            },
          },
          y: {
            beginAtZero: true,
            title: { display: true, text: "Response Time (ms)" },
          },
        },
      }),
    });

    this.bindWebsiteSelector(responseTimeData);
  }

  bindWebsiteSelector(responseTimeData) {
    const select = document.getElementById("websiteSelect");
    if (!select || !this.charts.uptimeChart) return;

    select.addEventListener("change", () => {
      const site = select.value;
      const chart = this.charts.uptimeChart;
      const siteData = responseTimeData[site] || { times: [], values: [] };
      const labels = this._formatTimeLabels(siteData.times);

      chart.data.labels = labels;
      chart.data.datasets[0].data = siteData.values || [];
      chart.data.datasets[0].label = `${site} - Waktu Respon (ms)`;

      // Simpan times baru untuk tooltip
      chart.options.plugins.tooltip.callbacks.title = (items) => {
        const idx = items[0]?.dataIndex;
        return siteData.times?.[idx] ?? items[0]?.label ?? "";
      };

      chart.update();
    });
  }
}

// ============================================
// REPORT CHARTS (report.php) - FIXED VERSION
// ============================================
class ReportCharts extends BaseChartManager {
  init(data) {
    console.log("Initializing Report Charts with data:", data);

    if (data.incidentsByDate) {
      this.initIncidentTrendChart(data.incidentsByDate);
    }

    if (data.severityCount) {
      this.initSeverityChart(data.severityCount);
    }

    if (data.typeCount) {
      this.initIncidentTypeChart(data.typeCount);
    }
  }

  initIncidentTrendChart(incidentsByDate) {
    this.createChart("incidentTrendChart", {
      type: "line",
      data: {
        labels: Object.keys(incidentsByDate),
        datasets: [
          {
            label: "Jumlah Insiden",
            data: Object.values(incidentsByDate),
            borderColor: "#0d6efd",
            backgroundColor: "rgba(13, 110, 253, 0.1)",
            tension: 0.4,
            fill: true,
            pointBackgroundColor: "#0d6efd",
            pointBorderColor: "#fff",
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
          },
        ],
      },
      options: ChartDefaults,
    });
  }

  initSeverityChart(severityCount) {
    console.log("Severity data:", severityCount);

    // Definisikan urutan severity dan warna yang sesuai
    const severityOrder = ["Low", "Medium", "High", "Critical"];
    const severityColors = {
      Low: "#28a745", // Hijau
      Medium: "#ffc107", // Kuning
      High: "#fd7e14", // Oranye
      Critical: "#6c757d", // Abu-abu (BUKAN MERAH!)
    };

    // Urutkan data sesuai severityOrder
    const orderedLabels = [];
    const orderedData = [];
    const orderedColors = [];

    severityOrder.forEach((severity) => {
      // Cek case-insensitive
      const key = Object.keys(severityCount).find(
        (k) => k.toLowerCase() === severity.toLowerCase(),
      );

      if (key && severityCount[key] > 0) {
        orderedLabels.push(key);
        orderedData.push(severityCount[key]);
        orderedColors.push(severityColors[severity]);
      }
    });

    console.log("Ordered severity:", {
      orderedLabels,
      orderedData,
      orderedColors,
    });

    this.createChart("severityChart", {
      type: "doughnut",
      data: {
        labels: orderedLabels,
        datasets: [
          {
            data: orderedData,
            backgroundColor: orderedColors,
            borderWidth: 2,
            borderColor: "#fff",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              padding: 15,
              font: { size: 12 },
              generateLabels: (chart) => {
                const data = chart.data;
                return data.labels.map((label, i) => ({
                  text: label,
                  fillStyle: data.datasets[0].backgroundColor[i],
                  hidden: false,
                  index: i,
                }));
              },
            },
          },
          tooltip: {
            backgroundColor: "rgba(0,0,0,0.8)",
            padding: 12,
            callbacks: {
              label: (ctx) => {
                const label = ctx.label || "";
                const value = ctx.parsed || 0;
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                return `${label}: ${value} (${pct}%)`;
              },
            },
          },
        },
      },
    });
  }

  initIncidentTypeChart(typeCount) {
    if (!typeCount || Object.keys(typeCount).length === 0) {
      const canvas = document.getElementById("incidentTypeChart");
      if (canvas) {
        const ctx = canvas.getContext("2d");
        ctx.font = "14px Arial";
        ctx.fillStyle = "#6c757d";
        ctx.textAlign = "center";
        ctx.fillText("Tidak ada data jenis insiden", canvas.width / 2, 100);
      }
      return;
    }

    const labels = Object.keys(typeCount);
    const data = Object.values(typeCount);

    const colors = [
      "rgba(23, 162, 184, 0.85)",
      "rgba(13, 110, 253, 0.80)",
      "rgba(102, 16, 242, 0.75)",
      "rgba(220, 53, 69,  0.80)",
      "rgba(255, 193, 7,  0.85)",
      "rgba(40, 167, 69,  0.80)",
      "rgba(253, 126, 20, 0.80)",
    ];
    const bgColors = labels.map((_, i) => colors[i % colors.length]);
    const borderColors = bgColors.map((c) => c.replace(/[\d.]+\)$/, "1)"));

    this.createChart("incidentTypeChart", {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Jumlah Insiden",
            data: data,
            backgroundColor: bgColors,
            borderColor: borderColors,
            borderWidth: 1,
            borderRadius: 4,
            barPercentage: 0.65,
            categoryPercentage: 0.85,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { right: 12 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "rgba(0,0,0,0.8)",
            padding: 12,
            callbacks: {
              label: (ctx) => ` ${ctx.parsed.x} insiden`,
            },
          },
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: { stepSize: 1, precision: 0, font: { size: 11 } },
            grid: { color: "rgba(0,0,0,0.06)" },
            title: {
              display: true,
              text: "Jumlah Insiden",
              font: { size: 12, weight: "bold" },
              padding: { top: 6 },
            },
          },
          y: {
            grid: { display: false },
            ticks: {
              font: { size: 12 },
              autoSkip: false,
              crossAlign: "far",
              callback: function (value) {
                const label = this.getLabelForValue(value);
                return label.length > 22 ? label.substring(0, 22) + "…" : label;
              },
            },
          },
        },
      },
    });
  }
}

// ============================================
// INCIDENT DETAIL CHARTS (incident_detail.php)
// ============================================
class IncidentDetailCharts extends BaseChartManager {
  initResponseChart(data) {
    this.createChart("responseTimeChart", {
      type: "line",
      data: {
        labels: data.labels || [],
        datasets: [
          {
            label: "Response Time (ms)",
            data: data.data || [],
            borderColor: "#dc3545",
            backgroundColor: "rgba(220, 53, 69, 0.1)",
            tension: 0.3,
            fill: true,
            pointBackgroundColor: "#dc3545",
            pointRadius: 4,
          },
          {
            label: "Threshold",
            data: Array(data.labels?.length || 0).fill(data.threshold || 0),
            borderColor: "#ffc107",
            borderDash: [5, 5],
            borderWidth: 2,
            pointRadius: 0,
            fill: false,
          },
        ],
      },
      options: mergeChartOptions(ChartDefaults, {
        plugins: { legend: { position: "top" } },
      }),
    });
  }
}

// ============================================
// TABLE FILTERS
// ============================================

class TableFilter {
  constructor(tableId, filters) {
    this.table = document.getElementById(tableId);
    this.filters = filters;
    this.init();
  }

  init() {
    Object.entries(this.filters).forEach(([id, attr]) => {
      const element = document.getElementById(id);
      if (element) {
        element.addEventListener("change", () => this.filter());
      }
    });
  }

  filter() {
    if (!this.table) return;

    const filterValues = {};
    Object.entries(this.filters).forEach(([id, attr]) => {
      const el = document.getElementById(id);
      filterValues[attr] = el ? el.value.toLowerCase() : "";
    });

    const rows = this.table.querySelectorAll("tbody tr[data-severity]");
    let visibleCount = 0;

    rows.forEach((row) => {
      const matches = Object.entries(filterValues).every(([attr, value]) => {
        if (!value) return true;
        const rowValue = (row.getAttribute(`data-${attr}`) || "").toLowerCase();
        return rowValue.includes(value);
      });

      row.style.display = matches ? "" : "none";
      if (matches) visibleCount++;
    });

    console.log(`Showing ${visibleCount} of ${rows.length} rows`);
  }

  reset() {
    Object.keys(this.filters).forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
    this.filter();
  }
}

// ============================================
// FORM VALIDATORS
// ============================================

class FormValidator {
  static validateUrl(url) {
    if (!url) return { valid: false, message: "URL tidak boleh kosong" };
    if (!url.startsWith("http://") && !url.startsWith("https://")) {
      return {
        valid: false,
        message: "URL harus dimulai dengan http:// atau https://",
      };
    }
    return { valid: true };
  }

  static validateInterval(interval) {
    const num = parseInt(interval);
    if (isNaN(num) || num < 1 || num > 1440) {
      return { valid: false, message: "Interval harus antara 1-1440 menit" };
    }
    return { valid: true };
  }

  static validatePassword(newPass, confirmPass) {
    if (newPass !== confirmPass) {
      return { valid: false, message: "Password tidak cocok" };
    }
    if (newPass.length < 6) {
      return { valid: false, message: "Password minimal 6 karakter" };
    }
    return { valid: true };
  }
}

// ============================================
// WEBSITE FORM HANDLERS
// ============================================

const WebsiteForm = {
  initCustomInterval() {
    const select = document.getElementById("interval");
    const div = document.getElementById("customIntervalDiv");
    const input = document.getElementById("customInterval");

    if (!select) return;

    select.addEventListener("change", function () {
      const isCustom = this.value === "custom";
      if (div) div.style.display = isCustom ? "block" : "none";
      if (input) input.required = isCustom;
    });
  },

  setInterval(minutes) {
    const input = document.getElementById("interval");
    if (input) input.value = minutes;
  },

  showPreview() {
    const fields = {
      previewName: document.getElementById("name")?.value || "-",
      previewUrl: document.getElementById("url")?.value || "-",
      previewInterval: this.getIntervalText(),
      previewStatus: this.getStatusText(),
    };

    Object.entries(fields).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    });

    const info = document.getElementById("previewInfo");
    if (info) {
      info.style.display = "block";
      info.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  },

  getIntervalText() {
    const select = document.getElementById("interval");
    if (!select) return "-";

    if (select.value === "custom") {
      const val = document.getElementById("customInterval")?.value;
      return val ? `${val} menit (Custom)` : "-";
    }

    return select.options[select.selectedIndex]?.text || "-";
  },

  getStatusText() {
    const status = document.getElementById("status")?.value || "active";
    return status === "active" ? "✅ Active" : "❌ Nonactive";
  },

  validate(e) {
    const urlInput = document.getElementById("url");
    const intervalSelect = document.getElementById("interval");

    if (!urlInput) return true;

    const urlCheck = FormValidator.validateUrl(urlInput.value);
    if (!urlCheck.valid) {
      e.preventDefault();
      alert(`❌ ${urlCheck.message}`);
      return false;
    }

    let interval = parseInt(intervalSelect?.value);
    if (intervalSelect?.value === "custom") {
      const customInput = document.getElementById("customInterval");
      interval = parseInt(customInput?.value);

      const intervalCheck = FormValidator.validateInterval(interval);
      if (!intervalCheck.valid) {
        e.preventDefault();
        alert(`❌ ${intervalCheck.message}`);
        return false;
      }

      const hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = "check_interval_minutes";
      hidden.value = interval;
      e.target.appendChild(hidden);
      intervalSelect.disabled = true;
    }

    return true;
  },
};

// ============================================
// PASSWORD FORM HANDLER
// ============================================

const PasswordForm = {
  validate(e) {
    const newPass = document.getElementById("new_password")?.value;
    const confirmPass = document.getElementById("confirm_password")?.value;

    if (!newPass || !confirmPass) return true;

    const check = FormValidator.validatePassword(newPass, confirmPass);
    if (!check.valid) {
      e.preventDefault();
      alert(`❌ ${check.message}`);
      return false;
    }

    return true;
  },
};

// ============================================
// AUTO-HIDE ALERTS
// ============================================

const AlertManager = {
  init() {
    document.querySelectorAll(".alert-success").forEach((alert) => {
      setTimeout(() => {
        if (typeof bootstrap !== "undefined" && bootstrap.Alert) {
          new bootstrap.Alert(alert).close();
        } else {
          alert.style.display = "none";
        }
      }, 5000);
    });
  },
};

// ============================================
// GLOBAL INITIALIZATION
// ============================================

document.addEventListener("DOMContentLoaded", function () {
  WebsiteForm.initCustomInterval();

  const addForm = document.getElementById("addForm");
  const editForm = document.getElementById("editForm");
  const passwordForm = document.getElementById("passwordForm");

  if (addForm)
    addForm.addEventListener("submit", (e) => WebsiteForm.validate(e));
  if (editForm)
    editForm.addEventListener("submit", (e) => WebsiteForm.validate(e));
  if (passwordForm)
    passwordForm.addEventListener("submit", (e) => PasswordForm.validate(e));

  if (document.getElementById("incidentTable")) {
    window.incidentFilter = new TableFilter("incidentTable", {
      filterSeverity: "severity",
      filterStatus: "status",
      filterType: "type",
    });
  }

  AlertManager.init();
});

// ============================================
// EXPORT TO GLOBAL SCOPE
// ============================================

window.DashboardCharts = DashboardCharts;
window.ReportCharts = ReportCharts;
window.IncidentDetailCharts = IncidentDetailCharts;
window.filterTable = () => window.incidentFilter?.filter();
window.resetFilter = () => window.incidentFilter?.reset();
window.setInterval = (min) => WebsiteForm.setInterval(min);
window.showPreview = () => WebsiteForm.showPreview();
