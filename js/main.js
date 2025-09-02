// JS general del proyecto Gasolina
// Mantén la lógica desacoplada del HTML. Añade listeners cuando sea necesario.
(function(){
  'use strict';
  // Inicialización básica

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
      const sma = (arr, w = 3) => {
        const out = new Array(arr.length).fill(NaN);
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

      // Preparar datos cronológicos
      const fechasC = toChrono(fechas);
      const importesC = toChrono(importes);
      const litrosC = toChrono(litros);
      const preciosC = toChrono(precios);
      const consumosC = toChrono((consumos || []).map(v => (v === null ? NaN : v)));

      // Grafico de Gasto
      const ctxGasto = document.getElementById('graficoGasto');
      if (ctxGasto) {
        new Chart(ctxGasto, {
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
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // Grafico de Precio/L
      const ctxPrecio = document.getElementById('graficoPrecio');
      if (ctxPrecio) {
        new Chart(ctxPrecio, {
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
      }

      // Grafico de Consumo (L/100km)
      const ctxConsumo = document.getElementById('graficoConsumo');
      if (ctxConsumo) {
        new Chart(ctxConsumo, {
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
            plugins: { legend: { display: false } },
            spanGaps: false,
            scales: { y: { beginAtZero: true } }
          }
        });
      }

      // Grafico de Litros
      const ctxLitros = document.getElementById('graficoLitros');
      if (ctxLitros) {
        new Chart(ctxLitros, {
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
