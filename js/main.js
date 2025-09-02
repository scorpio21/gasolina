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
    const ids = ['graficoGasto','graficoPrecio','graficoConsumo','graficoLitros'];
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
              borderColor: '#0d6efd',
              backgroundColor: 'rgba(13,110,253,0.15)',
              tension: 0.25,
              fill: true,
              pointRadius: 3
            }, {
              label: 'Tendencia (SMA3)',
              data: sma(importesC, 3),
              borderColor: '#0a58ca',
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
              borderColor: '#fd7e14',
              backgroundColor: 'rgba(253,126,20,0.15)',
              tension: 0.25,
              fill: true,
              pointRadius: 3
            }, {
              label: 'Tendencia (SMA3)',
              data: sma(preciosC, 3),
              borderColor: '#cc5c00',
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
              borderColor: '#6f42c1',
              backgroundColor: 'rgba(111,66,193,0.15)',
              tension: 0.25,
              fill: true,
              pointRadius: 3
            }, {
              label: 'Tendencia (SMA3)',
              data: sma(consumosC, 3),
              borderColor: '#563d7c',
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
              backgroundColor: 'rgba(25,135,84,0.3)',
              borderColor: '#198754',
              borderWidth: 1
            }, {
              type: 'line',
              label: 'Tendencia (SMA3)',
              data: sma(litrosC, 3),
              borderColor: '#146c43',
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
})();
