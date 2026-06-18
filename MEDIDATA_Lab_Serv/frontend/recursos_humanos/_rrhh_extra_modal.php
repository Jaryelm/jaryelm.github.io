<?php /* Modal visor de contrato (PDF) + estilos de celdas editables. Solo módulo RRHH. */ ?>
<div id="rrhhPdfModal" class="rrhh-modal-pdf">
    <div class="rrhh-modal-pdf-content">
        <div class="rrhh-modal-pdf-header">
            <h2 id="rrhhPdfTitle">Contrato</h2>
            <span id="rrhhPdfClose" class="rrhh-close-pdf">&times;</span>
        </div>
        <div class="rrhh-modal-pdf-body">
            <iframe id="rrhhPdfFrame" src="" frameborder="0"></iframe>
        </div>
        <div class="rrhh-modal-pdf-footer">
            <button id="rrhhPdfDownload" type="button" class="rrhh-btn-descargar-pdf">
                <i class="bx bx-download"></i> Descargar / Abrir
            </button>
        </div>
    </div>
</div>

<style>
/* Celdas editables */
td.rrhh-edit-cell { cursor: pointer; transition: background-color .15s ease; }
td.rrhh-edit-cell:hover { background-color: #e8f6f8; }
td.rrhh-edit-cell .rrhh-empty { color: #b0b6bd; }
.rrhh-edit-input {
    width: 100%;
    min-width: 90px;
    box-sizing: border-box;
    padding: 4px 6px;
    border: 1px solid #06adbf;
    border-radius: 4px;
    font-size: .85rem;
}
.rrhh-btn-mini {
    border: none;
    background: #035c67;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: .75rem;
    cursor: pointer;
    margin: 1px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.rrhh-btn-mini:hover { background: #06adbf; }

/* Modal PDF */
.rrhh-modal-pdf {
    display: none;
    position: fixed;
    z-index: 3000;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,.85);
    justify-content: center;
    align-items: center;
}
.rrhh-modal-pdf-content {
    background-color: #fff;
    width: 95%;
    max-width: 1200px;
    height: 90vh;
    display: flex;
    flex-direction: column;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,.3);
}
.rrhh-modal-pdf-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: #035c67;
    color: #fff;
    border-radius: 8px 8px 0 0;
}
.rrhh-modal-pdf-header h2 { margin: 0; font-size: 1.2rem; }
.rrhh-close-pdf { color: #fff; font-size: 30px; font-weight: bold; cursor: pointer; line-height: 1; }
.rrhh-close-pdf:hover { color: #ff6b6b; }
.rrhh-modal-pdf-body { flex: 1; overflow: hidden; }
.rrhh-modal-pdf-body iframe { width: 100%; height: 100%; border: none; display: block; }
.rrhh-modal-pdf-footer {
    padding: 12px 20px;
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    text-align: center;
}
.rrhh-btn-descargar-pdf {
    background-color: #035c67;
    color: #fff;
    border: none;
    padding: 9px 18px;
    border-radius: 5px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.rrhh-btn-descargar-pdf:hover { background-color: #06adbf; }
</style>
