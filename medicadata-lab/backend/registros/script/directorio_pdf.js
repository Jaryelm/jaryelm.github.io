function generarPDFProveedor(nombre_proveedor) {
    if (!nombre_proveedor) {
        alert('No se ha seleccionado un proveedor.');
        return;
    }

    const url = `../../backend/registros/directorio_pdf.php?nombre_proveedor=${encodeURIComponent(nombre_proveedor)}`;

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
        link.download = `${nombre_proveedor}.pdf`;
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
