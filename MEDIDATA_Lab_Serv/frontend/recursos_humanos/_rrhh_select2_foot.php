<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="../../backend/registros/script/rrhh_select2.js"></script>

<!-- Modal para Visualizar PDF -->
<div id="pdfModal" class="modal-pdf">
    <div class="modal-pdf-content">
        <div class="modal-pdf-header">
            <h2 id="pdfModalTitle">Visualizar Documento</h2>
            <span class="close-pdf-btn" onclick="cerrarPDFModal()">&times;</span>
        </div>
        <div class="modal-pdf-body">
            <iframe id="pdfFrame" src="" frameborder="0"></iframe>
        </div>
        <div class="modal-pdf-footer">
            <button class="btn-descargar-pdf" onclick="descargarPDFActual()">
                <i class="bx bx-download"></i> Descargar PDF
            </button>
        </div>
    </div>
</div>

<script>
// Modal PDF Viewer Pattern logic
let pdfUrlActual = '';

function verPDF(url, titulo) {
    const separator = url.includes('?') ? '&' : '?';
    const urlConView = url + separator + 'view=inline#zoom=100';
    
    pdfUrlActual = url;
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    const title = document.getElementById('pdfModalTitle');
    
    title.textContent = titulo;
    frame.src = urlConView;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarPDFModal() {
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    
    modal.style.display = 'none';
    frame.src = '';
    pdfUrlActual = '';
    document.body.style.overflow = 'auto';
}

function descargarPDFActual() {
    if (pdfUrlActual) {
        window.open(pdfUrlActual, '_blank');
    }
}

function verDocumentoStaff(url, titulo) {
    if (!url) return;
    var lower = String(url).toLowerCase();
    var inlineOk = lower.indexOf('view_staff_doc.php') !== -1
        || /\.(pdf|jpe?g|png|gif|webp)(\?|#|$)/.test(lower);
    if (inlineOk && typeof verPDF === 'function') {
        verPDF(url, titulo || 'Documento');
        return;
    }
    window.open(url, '_blank');
}

window.addEventListener('click', function(event) {
    const pdfModal = document.getElementById('pdfModal');
    if (event.target === pdfModal) {
        cerrarPDFModal();
    }
});
</script>
