(function () {
  const btn = document.getElementById('btnExportPDF');
  if (!btn) return;

  const pad = 12; // margen en mm
  const headerH = 16; // alto de cabecera en mm
  const footerH = 12; // alto de pie en mm

  const makePDF = async () => {
    const { jsPDF } = window.jspdf || {};
    if (!window.html2canvas || !jsPDF) {
      alert('No se pudo cargar html2canvas o jsPDF. Revisa tu conexión.');
      return;
    }

    const target = document.getElementById('historial-export');
    if (!target) {
      alert('No se encontró el contenido a exportar.');
      return;
    }

    // Render DOM a canvas (mejor calidad con scale)
    const canvas = await html2canvas(target, {
      scale: 2,
      backgroundColor: '#ffffff',
      useCORS: true
    });

    // PDF A4 vertical en mm
    const pdf = new jsPDF({ orientation: 'p', unit: 'mm', format: 'a4' });
    const pageW = pdf.internal.pageSize.getWidth();
    const pageH = pdf.internal.pageSize.getHeight();

    // Área útil descontando cabecera y pie
    const usableW = pageW - pad * 2;
    const usableH = pageH - headerH - footerH - pad; // resto del contenido

    // Escala para ajustar a ancho de página
    const scale = usableW / canvas.width; // mm por px en ancho
    const sliceHeightPx = Math.floor((usableH / scale)); // alto de slice en px para cada página

    let page = 1;

    const drawHeader = () => {
      pdf.setDrawColor(230);
      pdf.setFillColor(245, 245, 245);
      pdf.rect(pad, pad, pageW - pad * 2, headerH - 4, 'F');
      pdf.setTextColor(40);
      pdf.setFont('helvetica', 'bold');
      pdf.setFontSize(12);
      pdf.text('Historial de Repostajes', pad + 4, pad + 8);
      pdf.setFont('helvetica', 'normal');
      pdf.setFontSize(9);
      const fecha = new Date();
      const fechaTxt = fecha.toISOString().slice(0, 16).replace('T', ' ');
      pdf.text(`Generado: ${fechaTxt}`, pageW - pad - 4, pad + 8, { align: 'right' });
    };

    const drawFooter = () => {
      pdf.setDrawColor(200);
      pdf.line(pad, pageH - footerH, pageW - pad, pageH - footerH);
      pdf.setFontSize(9);
      pdf.setTextColor(120);
      pdf.text(`Página ${page}`, pageW - pad, pageH - footerH + 7, { align: 'right' });
      pdf.text(window.location.href || 'Gasolina', pad, pageH - footerH + 7);
    };

    // Crear slices verticales y dibujar por páginas
    const tmp = document.createElement('canvas');
    const ctx = tmp.getContext('2d');
    tmp.width = canvas.width;

    for (let offsetPx = 0; offsetPx < canvas.height; offsetPx += sliceHeightPx) {
      const currentSlicePx = Math.min(sliceHeightPx, canvas.height - offsetPx);
      tmp.height = currentSlicePx;
      ctx.clearRect(0, 0, tmp.width, tmp.height);
      ctx.drawImage(
        canvas,
        0, offsetPx, canvas.width, currentSlicePx, // src rect
        0, 0, tmp.width, currentSlicePx            // dst rect
      );
      const sliceData = tmp.toDataURL('image/png');

      if (offsetPx > 0) {
        pdf.addPage();
        page += 1;
      }
      drawHeader();
      const drawY = pad + headerH; // mm
      const drawH = currentSlicePx * scale; // mm
      pdf.addImage(sliceData, 'PNG', pad, drawY, usableW, drawH, undefined, 'FAST');
      drawFooter();
    }

    pdf.save('historial-repostajes.pdf');
  };

  btn.addEventListener('click', () => {
    btn.disabled = true;
    btn.textContent = 'Generando...';
    makePDF().finally(() => {
      btn.disabled = false;
      btn.textContent = 'Exportar PDF';
    });
  });
})();
