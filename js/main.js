// JS general del proyecto Gasolina
// Mantén la lógica desacoplada del HTML. Añade listeners cuando sea necesario.
(function(){
  'use strict';
  // Inicialización básica

  // =====================
  // Tema oscuro / claro
  // =====================
  const THEME_KEY = 'tema';
  const getStoredTheme = () => {
    try { return localStorage.getItem(THEME_KEY); } catch(_) { return null; }
  };
  const storeTheme = (t) => { try { localStorage.setItem(THEME_KEY, t); } catch(_) {} };
  const applyThemeToBody = (t) => {
    const oscuro = t === 'oscuro';
    document.body.classList.toggle('tema-oscuro', oscuro);
    const iconEl = document.getElementById('iconoTema');
    if (iconEl) iconEl.textContent = oscuro ? '☀️' : '🌙';
    return oscuro;
  };
  const updateChartsTheme = (oscuro) => {
    if (!window.Chart) return;
    const colorTexto = oscuro ? '#e5e5e5' : '#6c757d';
    const gridColor = oscuro ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
    try {
      // Actualizar defaults para futuros charts
      Chart.defaults.color = colorTexto;
    } catch(_) {}
    const ids = ['graficoGasto','graficoPrecio','graficoConsumo','graficoLitros','graficoDistribucion'];
    ids.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      const chart = Chart.getChart(el);
      if (!chart || !chart.options) return;
      const opts = chart.options;
      try {
        if (opts.plugins && opts.plugins.legend && opts.plugins.legend.labels) {
          opts.plugins.legend.labels.color = colorTexto;
        }
        if (opts.scales) {
          Object.keys(opts.scales).forEach(k => {
            if (opts.scales[k].ticks) opts.scales[k].ticks.color = colorTexto;
            if (opts.scales[k].grid) opts.scales[k].grid.color = gridColor;
          });
        }
      } catch(_) {}
      chart.update('none');
    });
  };
  const initTheme = () => {
    let t = getStoredTheme();
    if (t !== 'oscuro' && t !== 'claro') t = 'claro';
    const oscuro = applyThemeToBody(t);
    updateChartsTheme(oscuro);
    const btn = document.getElementById('toggleTema');
    if (btn) {
      btn.addEventListener('click', () => {
        const nuevo = document.body.classList.contains('tema-oscuro') ? 'claro' : 'oscuro';
        storeTheme(nuevo);
        const os = applyThemeToBody(nuevo);
        updateChartsTheme(os);
      });
    }
    return t;
  };
  // Inicializar tema cuanto antes
  const temaInicial = initTheme();

  // Graficas del dashboard (si existen datos y Chart.js)
  const dataEl = document.getElementById('dashboard-data');
  if (dataEl && window.Chart) {
    try {
      const fechas = JSON.parse(dataEl.getAttribute('data-fechas') || '[]');
      const importes = JSON.parse(dataEl.getAttribute('data-importes') || '[]');
      const litros = JSON.parse(dataEl.getAttribute('data-litros') || '[]');
      const precios = JSON.parse(dataEl.getAttribute('data-precios') || '[]');
      const consumos = JSON.parse(dataEl.getAttribute('data-consumos') || '[]');
      const rango = parseInt(dataEl.getAttribute('data-rango') || '5', 10);

      // Utilidades
      const toChrono = arr => (arr || []).slice().reverse();
      const isNum = v => typeof v === 'number' && !isNaN(v) && isFinite(v);
      const toNumArr = (arr) => (arr || []).map(v => (v === null || v === '' ? null : Number(v)));
      const qs = new URLSearchParams(window.location.search);
      const debug = qs.get('debug') === '1';
      // Panel de depuración en página (útil en iPhone sin consola)
      let debugPanel = null;
      const ensureDebugPanel = () => {
        if (!debug || debugPanel) return debugPanel;
        const panel = document.createElement('div');
        panel.id = 'debug-charts-panel';
        panel.style.position = 'fixed';
        panel.style.bottom = '8px';
        panel.style.right = '8px';
        panel.style.zIndex = '9999';
        panel.style.maxWidth = '90vw';
        panel.style.maxHeight = '45vh';
        panel.style.overflow = 'auto';
        panel.style.background = 'rgba(0,0,0,0.8)';
        panel.style.color = '#fff';
        panel.style.font = '12px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif';
        panel.style.padding = '8px 10px';
        panel.style.borderRadius = '6px';
        panel.style.boxShadow = '0 2px 8px rgba(0,0,0,0.35)';
        const title = document.createElement('div');
        title.textContent = 'DEBUG Charts';
        title.style.fontWeight = '600';
        title.style.marginBottom = '6px';
        const pre = document.createElement('pre');
        pre.style.margin = '0';
        pre.style.whiteSpace = 'pre-wrap';
        pre.style.wordBreak = 'break-word';
        panel.appendChild(title);
        panel.appendChild(pre);
        document.body.appendChild(panel);
        debugPanel = { panel, pre };
        return debugPanel;
      };
      const dlog = (...args) => {
        if (!debug) return;
        // Consola
        try { console.log('[DEBUG Charts]', ...args); } catch (_) {}
        // Panel visible
        try {
          const dp = ensureDebugPanel();
          const line = args.map(a => {
            try { return typeof a === 'string' ? a : JSON.stringify(a); }
            catch (_) { return String(a); }
          }).join(' ');
          dp.pre.textContent += (dp.pre.textContent ? '\n' : '') + line;
        } catch (_) {}
      };
      const ua = navigator.userAgent || '';
      const isIOS = /(iPad|iPhone|iPod)/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
      const isSafari = /^((?!chrome|android).)*safari/i.test(ua);
      const isIOSSafari = isIOS && isSafari;
      if (debug) {
        dlog('Chart.js version:', Chart && Chart.version);
        dlog('UA:', ua);
        dlog('isIOS:', isIOS, 'isSafari:', isSafari, 'isIOSSafari:', isIOSSafari);
      }
      const createChart = (canvas, config) => {
        if (isIOSSafari) {
          return requestAnimationFrame(() => new Chart(canvas.getContext('2d'), config));
        }
        return new Chart(canvas.getContext('2d'), config);
      };
      const ensureCanvasSize = (canvas, h = 240) => {
        try {
          const ch = canvas.clientHeight || canvas.height || 0;
          if (isIOSSafari && ch === 0) {
            canvas.style.height = h + 'px';
            canvas.height = h;
          }
        } catch (_) {}
      };
      const sma = (arr, w = 3) => {
        const out = new Array(arr.length).fill(null);
        for (let i = 0; i <= arr.length - w; i++) {
          let sum = 0, ok = true;
          for (let j = 0; j < w; j++) {
            const val = arr[i + j];
            if (!isNum(val)) { ok = false; break; }
            sum += val;
          }
          if (ok) out[i + w - 1] = sum / w;
        }
        return out;
      };

      // Defaults globales de Chart.js (alineado con app que funciona)
      try {
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;
        // Color de texto según tema actual
        Chart.defaults.color = document.body.classList.contains('tema-oscuro') ? '#e5e5e5' : '#6c757d';
      } catch (_) {}

      // Preparar datos cronológicos
      const fechasC = toChrono(fechas);
      const importesC = toChrono(toNumArr(importes));
      const litrosC = toChrono(toNumArr(litros));
      const preciosC = toChrono(toNumArr(precios));
      const consumosC = toChrono(toNumArr(consumos));
      dlog('Longitudes:', {
        fechas: fechasC.length,
        importes: importesC.length,
        litros: litrosC.length,
        precios: preciosC.length,
        consumos: consumosC.length,
        rango
      });

      const logCanvasInfo = (canvas, nombre) => {
        try {
          const cont = canvas.closest('.chart-container');
          if (debug && cont) {
            cont.style.outline = '1px dashed #dc3545';
          }
          const cs = window.getComputedStyle(canvas);
          dlog(`${nombre} sizes`, {
            container: cont ? { w: cont.offsetWidth, h: cont.offsetHeight } : null,
            canvas: {
              ow: canvas.offsetWidth, oh: canvas.offsetHeight,
              cw: canvas.clientWidth, ch: canvas.clientHeight,
              attrH: canvas.getAttribute('height'),
              display: cs.display, visibility: cs.visibility
            }
          });
        } catch (e) { dlog('logCanvasInfo error', e); }
      };

      const markChartReady = (canvas, nombre) => {
        if (isIOSSafari) {
          requestAnimationFrame(() => {
            const chart = Chart.getChart(canvas);
            dlog(`${nombre} creado:`, !!chart);
          });
        } else {
          const chart = Chart.getChart(canvas);
          dlog(`${nombre} creado:`, !!chart);
        }
      };

      // Grafico de Gasto
      const ctxGasto = document.getElementById('graficoGasto');
      if (ctxGasto && ctxGasto.getContext) {
        logCanvasInfo(ctxGasto, 'Gasto');
        ensureCanvasSize(ctxGasto);
        createChart(ctxGasto, {
          type: 'line',
          data: {
            labels: fechasC,
            datasets: [{
              label: 'Gasto (€)',
              data: importesC,
              borderColor: '#5bd6bc',
              backgroundColor: 'rgba(91,214,188,0.15)',
              tension: 0.25,
              fill: true,
              pointRadius: 3
            }, {
              label: 'Tendencia (SMA3)',
              data: sma(importesC, 3),
              borderColor: '#2a9f8a',
              backgroundColor: 'transparent',
              borderDash: [6, 4],
              spanGaps: false,
              pointRadius: 0,
              hidden: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: !isIOSSafari,
            animation: isIOSSafari ? false : true,
            normalized: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
          }
        });
        markChartReady(ctxGasto, 'Gasto');
      }

      // Grafico de Precio/L
      const ctxPrecio = document.getElementById('graficoPrecio');
      if (ctxPrecio && ctxPrecio.getContext) {
        logCanvasInfo(ctxPrecio, 'Precio/L');
        ensureCanvasSize(ctxPrecio);
        createChart(ctxPrecio, {
          type: 'line',
          data: {
            labels: fechasC,
            datasets: [{
              label: 'Precio/L (€)',
              data: preciosC,
              borderColor: '#ffbd68',
              backgroundColor: 'rgba(255,189,104,0.15)',
              tension: 0.25,
              fill: true,
              pointRadius: 3
            }, {
              label: 'Tendencia (SMA3)',
              data: sma(preciosC, 3),
              borderColor: '#d58c3f',
              backgroundColor: 'transparent',
              borderDash: [6, 4],
              spanGaps: false,
              pointRadius: 0,
              hidden: true
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
          }
        });
        markChartReady(ctxPrecio, 'Precio/L');
      }

      // Grafico de Consumo (L/100km)
      const ctxConsumo = document.getElementById('graficoConsumo');
      if (ctxConsumo && ctxConsumo.getContext) {
        logCanvasInfo(ctxConsumo, 'Consumo');
        ensureCanvasSize(ctxConsumo);
        createChart(ctxConsumo, {
          type: 'line',
          data: {
            labels: fechasC,
            datasets: [{
              label: 'Consumo (L/100km)',
              data: consumosC,
              borderColor: '#73a8ff',
              backgroundColor: 'rgba(115,168,255,0.15)',
              tension: 0.25,
              fill: true,
              pointRadius: 3
            }, {
              label: 'Tendencia (SMA3)',
              data: sma(consumosC, 3),
              borderColor: '#4d78c0',
              backgroundColor: 'transparent',
              borderDash: [6, 4],
              spanGaps: false,
              pointRadius: 0,
              hidden: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: !isIOSSafari,
            animation: isIOSSafari ? false : true,
            normalized: true,
            plugins: { legend: { display: false } },
            spanGaps: false,
            scales: { y: { beginAtZero: true } }
          }
        });
        markChartReady(ctxConsumo, 'Consumo');
      }

      // Grafico de Litros
      const ctxLitros = document.getElementById('graficoLitros');
      if (ctxLitros && ctxLitros.getContext) {
        logCanvasInfo(ctxLitros, 'Litros');
        ensureCanvasSize(ctxLitros);
        createChart(ctxLitros, {
          type: 'bar',
          data: {
            labels: fechasC,
            datasets: [{
              label: 'Litros',
              data: litrosC,
              backgroundColor: 'rgba(91,214,188,0.3)',
              borderColor: '#5bd6bc',
              borderWidth: 1
            }, {
              type: 'line',
              label: 'Tendencia (SMA3)',
              data: sma(litrosC, 3),
              borderColor: '#2a9f8a',
              backgroundColor: 'transparent',
              borderDash: [6, 4],
              spanGaps: false,
              pointRadius: 0,
              hidden: true
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // Gráfica circular: distribución del gasto por repostaje.
      const ctxDistribucion = document.getElementById('graficoDistribucion');
      if (ctxDistribucion && ctxDistribucion.getContext) {
        const colores = [
          '#5bd6bc', '#73a8ff', '#ffbd68', '#c792ea', '#f28f8f',
          '#9bd46e', '#f7d774', '#7dd3c7', '#b8a1ff', '#ff9f68'
        ];
        const pieCallouts = {
          id: 'pieCallouts',
          afterDatasetsDraw(chart) {
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || !meta.data.length || typeof meta.data[0].startAngle !== 'number') return;
            const values = chart.data.datasets[0].data.map(Number);
            const total = values.reduce((sum, value) => sum + (Number.isFinite(value) ? value : 0), 0);
            if (!total) return;
            const context = chart.ctx;
            context.save();
            context.font = '600 12px Space Grotesk, sans-serif';
            context.textBaseline = 'middle';
            meta.data.forEach((arc, index) => {
              const angle = (arc.startAngle + arc.endAngle) / 2;
              const percentage = (values[index] / total) * 100;
              const side = Math.cos(angle) >= 0 ? 1 : -1;
              const startX = arc.x + Math.cos(angle) * arc.outerRadius * .78;
              const startY = arc.y + Math.sin(angle) * arc.outerRadius * .78;
              const elbowX = arc.x + Math.cos(angle) * (arc.outerRadius + 18);
              const elbowY = arc.y + Math.sin(angle) * (arc.outerRadius + 18);
              const labelX = elbowX + side * 34;
              const label = `${percentage.toFixed(0)}%`;
              const boxWidth = context.measureText(label).width + 18;
              const boxHeight = 24;
              const topLimit = chart.chartArea.top + boxHeight / 2 + 4;
              const bottomLimit = chart.chartArea.bottom - boxHeight / 2 - 4;
              const labelY = Math.max(topLimit, Math.min(bottomLimit, elbowY));
              const boxX = side > 0 ? labelX : labelX - boxWidth;
              const boxY = labelY - boxHeight / 2;

              context.strokeStyle = colores[index % colores.length];
              context.lineWidth = 1.5;
              context.beginPath();
              context.moveTo(startX, startY);
              context.lineTo(elbowX, elbowY);
              context.lineTo(labelX, labelY);
              context.stroke();

              context.fillStyle = 'rgba(10, 16, 22, .94)';
              context.lineWidth = 1;
              context.beginPath();
              context.rect(boxX, boxY, boxWidth, boxHeight);
              context.fill();
              context.stroke();
              context.fillStyle = '#f4f8fb';
              context.textAlign = 'center';
              context.fillText(label, boxX + boxWidth / 2, labelY);
            });
            context.restore();
          }
        };
        createChart(ctxDistribucion, {
          type: 'pie',
          plugins: [pieCallouts],
          data: {
            labels: fechasC,
            datasets: [{
              label: 'Gasto (€)',
              data: importesC,
              backgroundColor: importesC.map((_, i) => colores[i % colores.length]),
              borderColor: '#1c2329',
              borderWidth: 2,
              hoverOffset: 8,
              offset: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 16, right: 50, bottom: 12, left: 50 } },
            plugins: {
              legend: {
                position: 'bottom',
                labels: { usePointStyle: true, padding: 14 }
              },
              tooltip: {
                callbacks: {
                  label: (ctx) => ` ${ctx.label}: ${Number(ctx.raw || 0).toFixed(2)} €`
                }
              }
            }
          }
        });
        markChartReady(ctxDistribucion, 'Distribución');
      }

      // Toggle de tendencia (SMA3)
      const toggle = document.getElementById('toggleTendencia');
      if (toggle) {
        const toggleAll = (show) => {
          [ctxGasto, ctxPrecio, ctxConsumo, ctxLitros].forEach((canvas) => {
            if (!canvas) return;
            const chart = Chart.getChart(canvas);
            if (!chart) return;
            // Dataset índice 1 es tendencia en cada gráfico
            if (chart.data && chart.data.datasets && chart.data.datasets[1]) {
              chart.data.datasets[1].hidden = !show;
              chart.update();
            }
          });
        };
        // Estado inicial (oculto por defecto)
        toggle.addEventListener('change', (e) => {
          toggleAll(e.target.checked);
        });
      }
    } catch (e) {
      // Silenciar en producción; aquí podríamos loguear si se desea
      console.error('Error inicializando gráficas', e);
    }
  }
  // =============================
  // Gráfico mensual (Listar)
  // =============================
  try {
    const mensualEl = document.getElementById('mensual-data');
    const grafMensual = document.getElementById('graficoMensual');
    if (window.Chart && mensualEl && grafMensual && grafMensual.getContext) {
      const labels = JSON.parse(mensualEl.getAttribute('data-labels') || '[]');
      const totales = JSON.parse(mensualEl.getAttribute('data-totales') || '[]');
      try { console.log('[Mensual] labels:', labels, 'totales:', totales); } catch(_) {}
      // Asegurar una altura visible incluso si el contenedor no la asigna aún
      if (!grafMensual.style.height) { grafMensual.style.height = '260px'; }
      if (!grafMensual.height || grafMensual.height === 0) { grafMensual.height = 260; }
      // Forzar fondo para detectar el canvas
      try { grafMensual.style.background = 'transparent'; } catch(_) {}
      const ensureWidthAndCreate = () => {
        try {
          const parent = grafMensual.parentElement;
          const pw = parent ? parent.clientWidth : 0;
          if (!grafMensual.style.width) { grafMensual.style.width = '100%'; }
          if ((!grafMensual.width || grafMensual.width === 0) && pw > 0) { grafMensual.width = pw; }
          if ((!grafMensual.width || grafMensual.width === 0)) { grafMensual.width = 600; }
        } catch(_) {}
        const ctx = grafMensual.getContext('2d');
        new Chart(ctx, {
          type: 'bar',
          // Aplicar las etiquetas solo a esta gráfica; registrarlo globalmente
          // rompe las gráficas que no tienen configuración de datalabels.
          plugins: window.ChartDataLabels ? [window.ChartDataLabels] : [],
          data: {
            labels,
            datasets: [{
              label: 'Gasto mensual (€)',
              data: totales,
              backgroundColor: 'rgba(91,214,188,0.25)',
              borderColor: '#5bd6bc',
              borderWidth: 1,
              barPercentage: 0.6,
              categoryPercentage: 0.7
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              datalabels: {
                anchor: 'end',
                align: 'end',
                color: '#444',
                formatter: (v) => `${v.toFixed(2)} €`,
                font: { weight: '600' }
              }
            },
            scales: {
              x: { grid: { display: true } },
              y: { beginAtZero: true, grid: { display: true } }
            }
          }
        });
        try { console.log('[Mensual] chart creado:', !!Chart.getChart(grafMensual)); } catch(_) {}
      };
      // Si el ancho aún es 0, esperemos un frame
      if ((grafMensual.parentElement && grafMensual.parentElement.clientWidth === 0) || grafMensual.clientWidth === 0) {
        requestAnimationFrame(ensureWidthAndCreate);
      } else {
        ensureWidthAndCreate();
      }
    }
  } catch (e) { /* noop */ }
})();
