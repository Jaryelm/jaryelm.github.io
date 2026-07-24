<?php
$rxMenuRol = $_SESSION['rol'] ?? '';
$rxEsRadiologo = ($rxMenuRol === 'Radiologo');
?>
<!-- SIDEBAR -->
<section id="sidebar">
    <a href="../radiologiaeimagen/escritorio.php" class="brand"><i class='bx bxs-home home'></i>MEDIDATA</a>
    <ul class="side-menu">
        <li><a href="../radiologiaeimagen/escritorio.php" class="active"><i class='bx bxs-dashboard icon'></i> Panel</a></li>
        <li class="divider" data-text="panel">Panel</li>

        <?php if ($rxEsRadiologo): ?>
        <li>
            <a href="../radiologiaeimagen/lista_estudios_medico.php"><i class='bx bx-file-find icon'></i> MIS ESTUDIOS</a>
        </li>
        <?php else: ?>
        <li>
            <a href="#"><i class='bx bxs-clinic icon'></i>ÁREAS DE ATENCIÓN<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="../radiologiaeimagen/tabladeestudios_user.php">MH-PACS</a></li>
                <li><a href="../radiologiaeimagen/worklist_tecnico.php">LISTA DE TRABAJO - MH-PACS</a></li>
                <li><a href="../radiologiaeimagen/lista_estudios_medico.php">LISTA DE ESTUDIOS - MH-PACS</a></li>
                <li><a href="../radiologiaeimagen/lista_transcripciones_user.php">LISTA DE TRANSCRIPCIONES - MH-PACS</a></li>
            </ul>
        </li>
        <?php endif; ?>

        <?php include __DIR__ . '/../vacaciones_permisos/_menu_vacaciones.php'; ?>
        <li><a href="../radiologiaeimagen/mostrar.php"><i class='bx bxs-info-circle icon'></i>ACERCA DE MEDICASA</a></li>
    </ul>
</section>
<!-- SIDEBAR -->