function generarPDFComercial(nombre_empresa) {
    if (!nombre_empresa) {
        alert('No se ha seleccionado una empresa.');
        return;
    }

    const url = `../../backend/registros/directorio_pdf_comercial.php?nombre_empresa=${encodeURIComponent(nombre_empresa)}`;

    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/pdf',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.blob();
    })
    .then(blob => {
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = `${nombre_empresa}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);
    })
    .catch(error => {
        console.error('Error al generar el PDF:', error);
        alert('Error al generar el PDF. Inténtalo de nuevo.');
    });
}
