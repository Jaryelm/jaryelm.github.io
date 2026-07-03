function generarPDF(chequeNo) {
    if (!chequeNo) {
        alert('No hay un cheque seleccionado.');
        return;
    }

    // Asegúrate de que la URL es correcta
    const url = `../../backend/registros/cheques_pdf.php?chequeNo=${encodeURIComponent(chequeNo)}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.blob();
        })
        .then(blob => {
            // Crear un enlace para descargar el archivo PDF
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = `${chequeNo}.pdf`;
            
            // Agregar el enlace al DOM y simular un clic
            document.body.appendChild(link);
            link.click();
            
            // Limpiar y remover el enlace
            document.body.removeChild(link);
            window.URL.revokeObjectURL(link.href);
        })
        .catch(error => {
            console.error('Error al generar el PDF:', error);
            alert('Error al generar el PDF. Inténtalo de nuevo.');
        });
}

