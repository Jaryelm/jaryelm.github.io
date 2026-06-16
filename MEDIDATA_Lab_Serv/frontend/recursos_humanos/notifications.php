<!-- Notification Icon and Popover -->
<div class="notification-wrapper" id="notification-wrapper">
    <i class="bx bx-bell notification-bell" id="btn-notifications"></i>
    <span id="notification-badge" class="notification-badge-main" style="display: inline-block;">5</span>
    
    <!-- Notification Popup Menu -->
    <div id="notification-menu" class="notification-menu" style="display: none;">
        <div class="notification-header">
            <span>Notificaciones</span>
            <i class='bx bx-check-double' title="Marcar todas como leídas"></i>
        </div>
        
        <!-- Tabs -->
        <div class="notification-tabs">
            <div class="notif-tab active" data-tab="unread">
                No Leídas <span class="notif-tab-badge unread-badge">5</span>
            </div>
            <div class="notif-tab" data-tab="read">
                Leídas <span class="notif-tab-badge read-badge">0</span>
            </div>
        </div>

        <!-- Tab Contents -->
        <div id="notif-content-unread" class="notif-content">
            <!-- Cards de No Leídas -->
            <div class="notif-card">
                <p><strong>Alerta de Inventario:</strong> Paracetamol bajo</p>
                <small>Hace 3 horas</small>
            </div>
            <div class="notif-card">
                <p><strong>Nueva Factura</strong> generada para Juan Pérez</p>
                <small>Hace 5 horas</small>
            </div>
            <div class="notif-card">
                <p><strong>Cita Reprogramada:</strong> Dra. García</p>
                <small>Hace 6 horas</small>
            </div>
            <div class="notif-card">
                <p><strong>Resultado de Lab:</strong> Listo para revisar</p>
                <small>Ayer</small>
            </div>
            <div class="notif-card">
                <p><strong>Sistema:</strong> Actualización completada</p>
                <small>Ayer</small>
            </div>
        </div>

        <div id="notif-content-read" class="notif-content" style="display: none;">
            <!-- Cards de Leídas -->
            <div class="notif-empty">
                No hay notificaciones leídas
            </div>
        </div>

        <div class="notification-footer">
            <span class="ws-status-dot ws-online" id="ws-status-indicator"></span>
            <span id="ws-status-text">Conexión establecida</span>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const btnNotifications = document.getElementById("btn-notifications");
    const notificationMenu = document.getElementById("notification-menu");

    if (btnNotifications && notificationMenu) {
        // Toggle the notification menu on click
        btnNotifications.addEventListener("click", function(event) {
            event.stopPropagation(); // Evitar que el clic cierre el menú inmediatamente
            if (notificationMenu.style.display === "none" || notificationMenu.style.display === "") {
                notificationMenu.style.display = "flex";
            } else {
                notificationMenu.style.display = "none";
            }
        });

        // Close the notification menu when clicking outside of it
        document.addEventListener("click", function(event) {
            if (!notificationMenu.contains(event.target) && event.target !== btnNotifications) {
                notificationMenu.style.display = "none";
            }
        });
        
        // Prevent menu from closing when clicking inside it
        notificationMenu.addEventListener("click", function(event) {
            event.stopPropagation();
        });

        // Tab switching logic
        const tabs = document.querySelectorAll(".notif-tab");
        const contents = document.querySelectorAll(".notif-content");

        tabs.forEach(tab => {
            tab.addEventListener("click", function(e) {
                e.stopPropagation();
                
                // Remove active styling from all tabs
                tabs.forEach(t => {
                    t.classList.remove("active");
                });
                
                // Add active styling to clicked tab
                this.classList.add("active");

                // Hide all contents
                contents.forEach(c => c.style.display = "none");
                
                // Show the targeted content
                const targetId = "notif-content-" + this.getAttribute("data-tab");
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.style.display = "block";
                }
            });
        });
    }
});
</script>
