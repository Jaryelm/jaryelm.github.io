<?php /* Campanilla de notificaciones RRHH — feed dinámico (calendario + WebSocket) */ ?>
<!-- Campanilla de notificaciones RRHH -->
<div class="notification-wrapper" id="notification-wrapper">
    <i class="bx bx-bell notification-bell" id="btn-notifications" title="Notificaciones"></i>
    <span id="notification-badge" class="notification-badge-main" style="display:none;">0</span>

    <div id="notification-menu" class="notification-menu" style="display: none;">
        <div class="notification-header">
            <span>Notificaciones</span>
            <i class="bx bx-check-double" id="btn-mark-all-read" title="Marcar todas como leídas"></i>
        </div>

        <div class="notification-tabs">
            <div class="notif-tab active" data-tab="unread">
                No Leídas <span class="notif-tab-badge unread-badge">0</span>
            </div>
            <div class="notif-tab" data-tab="read">
                Leídas <span class="notif-tab-badge read-badge">0</span>
            </div>
        </div>

        <div id="notif-content-unread" class="notif-content">
            <div class="notif-empty">No hay notificaciones pendientes</div>
        </div>

        <div id="notif-content-read" class="notif-content" style="display: none;">
            <div class="notif-empty">No hay notificaciones leídas</div>
        </div>

        <div class="notification-footer">
            <span class="ws-status-dot ws-reconnecting" id="ws-status-indicator"></span>
            <span id="ws-status-text">Conectando...</span>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var FEED_KEY = 'rrhh_notif_feed_state_v2';
    var SEEN_KEY = 'rrhh_notif_seen_ids_v2';
    var MAX_ITEMS = 50;
    var RETENTION_MS = 24 * 60 * 60 * 1000; // 24 horas: se limpia todo lo más viejo
    var MAX_SEEN = 800;

    var feed = loadFeed();
    var seen = loadSeen();

    // ---------- Persistencia ----------
    function loadFeed() {
        try {
            var raw = localStorage.getItem(FEED_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function saveFeed() {
        try { localStorage.setItem(FEED_KEY, JSON.stringify(feed)); } catch (e) {}
    }
    function loadSeen() {
        try {
            var raw = localStorage.getItem(SEEN_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }
    function saveSeen() {
        try {
            if (seen.length > MAX_SEEN) seen = seen.slice(seen.length - MAX_SEEN);
            localStorage.setItem(SEEN_KEY, JSON.stringify(seen));
        } catch (e) {}
    }
    function isSeen(id) { return seen.indexOf(id) !== -1; }
    function markSeen(id) { if (!isSeen(id)) seen.push(id); }

    // ---------- Utilidades ----------
    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatRelativeTime(ts) {
        var diff = Date.now() - ts;          // > 0 pasado, < 0 futuro
        var future = diff < 0;
        var abs = Math.abs(diff) / 1000;     // segundos
        var prefix = future ? 'En ' : 'Hace ';
        if (abs < 60) return future ? 'En un momento' : 'Hace un momento';
        var mins = Math.floor(abs / 60);
        if (mins < 60) return prefix + mins + (mins === 1 ? ' minuto' : ' minutos');
        var hrs = Math.floor(mins / 60);
        if (hrs < 24) return prefix + hrs + (hrs === 1 ? ' hora' : ' horas');
        var days = Math.floor(hrs / 24);
        if (days < 30) return prefix + days + (days === 1 ? ' día' : ' días');
        if (typeof moment === 'function') return moment(ts).format('DD/MM/YYYY');
        var d = new Date(ts);
        return ('0' + d.getDate()).slice(-2) + '/' + ('0' + (d.getMonth() + 1)).slice(-2) + '/' + d.getFullYear();
    }

    // ---------- Modelo ----------
    function upsertNotification(n) {
        if (!n || !n.id) return;
        for (var i = 0; i < feed.length; i++) {
            if (feed[i].id === n.id) {
                feed[i].title = n.title;
                feed[i].body = n.body;
                feed[i].time = n.time;
                return; // conserva el estado read existente
            }
        }
        feed.push({ id: n.id, title: n.title, body: n.body, time: n.time, read: false });
    }

    function pruneFeed() {
        var limit = Date.now() - RETENTION_MS;
        feed = feed.filter(function (n) {
            // conserva lo de las últimas 24 horas
            return typeof n.time === 'number' && n.time >= limit;
        });
        feed.sort(function (a, b) { return b.time - a.time; });
        if (feed.length > MAX_ITEMS) feed = feed.slice(0, MAX_ITEMS);
    }

    function markAsRead(id) {
        for (var i = 0; i < feed.length; i++) {
            if (feed[i].id === id) { feed[i].read = true; break; }
        }
        saveFeed();
        render();
    }

    function markAllRead() {
        feed.forEach(function (n) { n.read = true; });
        saveFeed();
        render();
    }

    // ---------- Recordatorios desde el calendario ----------
    function cleanTitle(t) {
        return String(t || '').replace(/^[^\wÁÉÍÓÚÑáéíóúñ]+/, '').trim();
    }

    function reminderName(ev) {
        var t = cleanTitle(ev.title);
        switch (ev.type) {
            case 'birthday':    return 'Cumpleaños de ' + t.replace(/^Cumpleaños:\s*/i, '');
            case 'anniversary': return 'Aniversario de ' + t.replace(/^Aniversario:\s*/i, '');
            case 'vacancy_end': return 'Cierre de vacante: ' + t.replace(/^Cierre Vacante:\s*/i, '');
            default:            return t; // custom (incl. capacitaciones) e interview ("Entrevista: ...")
        }
    }

    function capitalizar(s) {
        s = String(s || '');
        return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
    }

    // Hora en formato "8:15 am" / "1:05 pm".
    function fmtHora(m) {
        var h = m.hours(), min = m.minutes();
        var ap = h < 12 ? 'am' : 'pm';
        var h12 = h % 12; if (h12 === 0) h12 = 12;
        return h12 + ':' + (min < 10 ? '0' + min : min) + ' ' + ap;
    }

    // Texto concreto que indica CUÁNDO comienza el evento, según el tiempo real
    // que falta en el momento del aviso. Ej.: "Comienza mañana 8:15 am",
    // "Comienza en 35 min (8:15 am)", "Es hoy", "Es el miércoles 24/06".
    function reminderTitle(start, isAllDay, now) {
        var mins = start.diff(now, 'minutes');
        var dayDiff = start.clone().startOf('day').diff(now.clone().startOf('day'), 'days');

        var diaLargo = capitalizar(start.clone().locale('es').format('dddd DD/MM'));

        if (isAllDay) {
            if (dayDiff <= 0) return 'Es hoy:';
            if (dayDiff === 1) return 'Es mañana:';
            return 'Es el ' + diaLargo + ':';
        }

        var hora = fmtHora(start);
        // Inminente: muestra los minutos exactos que faltan.
        if (mins >= 0 && mins <= 90) {
            if (mins <= 1) return 'Comienza ahora (' + hora + '):';
            return 'Comienza en ' + mins + ' min (' + hora + '):';
        }
        if (dayDiff === 0) return 'Comienza hoy ' + hora + ':';
        if (dayDiff === 1) return 'Comienza mañana ' + hora + ':';
        return 'Comienza el ' + diaLargo + ' ' + hora + ':';
    }

    // Etapas de recordatorio aplicables AHORA para un evento, ordenadas de
    // menos a más urgente (la última es la más próxima).
    function dueRules(start, isAllDay, now) {
        var rules = [];
        if (isAllDay) {
            var dayDiff = start.clone().startOf('day').diff(now.clone().startOf('day'), 'days');
            if (dayDiff < 0) return rules; // ya pasó
            if (dayDiff <= 7) rules.push('d7');
            if (dayDiff <= 1) rules.push('d1');
            if (dayDiff === 0) rules.push('d0');
        } else {
            var msUntil = start.diff(now);
            if (msUntil < 0) return rules; // ya pasó
            var daysUntil = msUntil / 86400000;
            var hoursUntil = msUntil / 3600000;
            if (daysUntil <= 7) rules.push('d7');
            if (hoursUntil <= 24) rules.push('d1');
            if (hoursUntil <= 1) rules.push('h1');
        }
        return rules;
    }

    // Reglas uniformes para TODO evento calendarizado. Cada etapa se emite una
    // sola vez y el título describe cuándo comienza el evento:
    //   1) 7 días antes
    //   2) 1 día antes
    //   3) 1 hora antes  (eventos con hora)  /  el mismo día (día completo)
    function buildReminders() {
        var events = window.rrhhCalendarEvents;
        if (!Array.isArray(events) || typeof moment !== 'function') return;

        var now = moment();
        var allowed = ['custom', 'birthday', 'anniversary', 'interview', 'vacancy_end'];

        events.forEach(function (ev) {
            if (!ev || !ev.start || allowed.indexOf(ev.type) === -1) return;
            var start = moment(ev.start);
            if (!start.isValid()) return;

            var isAllDay = !!ev.allDay || ev.type === 'birthday' || ev.type === 'anniversary';
            var name = reminderName(ev);
            var rules = dueRules(start, isAllDay, now);

            // Solo las etapas aún no avisadas. Si varias caen a la vez (evento
            // creado muy cerca de su inicio) emitimos un único aviso, el más
            // urgente, y marcamos las demás como vistas para no saturar.
            var pendientes = rules.filter(function (r) { return !isSeen('rem_' + ev.id + '_' + r); });
            if (!pendientes.length) return;
            rules.forEach(function (r) { markSeen('rem_' + ev.id + '_' + r); });

            var regla = pendientes[pendientes.length - 1];
            upsertNotification({
                id: 'rem_' + ev.id + '_' + regla,
                title: reminderTitle(start, isAllDay, now),
                body: name,
                time: Date.now()
            });
        });
        saveSeen();
    }

    // Al crear/editar un evento, el usuario ya recibe "Evento creado/actualizado".
    // Para no duplicar, marcamos como vistas las etapas de recordatorio que
    // aplicarían en ese mismo instante. Recorremos las ocurrencias ya expandidas
    // en window.rrhhCalendarEvents (sirve para eventos simples y recurrentes),
    // usando exactamente el mismo id que genera buildReminders.
    function suppressDueReminders(d) {
        if (typeof moment !== 'function') return;
        var rawId = d && (d.raw_id || d.id);
        if (!rawId) return;
        var events = window.rrhhCalendarEvents;
        if (!Array.isArray(events)) return;
        var now = moment();
        events.forEach(function (ev) {
            if (!ev || ev.type !== 'custom' || ev.raw_id != rawId || !ev.start) return;
            var start = moment(ev.start);
            if (!start.isValid()) return;
            dueRules(start, !!ev.allDay, now).forEach(function (r) {
                markSeen('rem_' + ev.id + '_' + r);
            });
        });
        saveSeen();
    }

    // ---------- Actualizaciones en vivo (WebSocket) ----------
    function pushLiveUpdate(payload) {
        if (!payload || !payload.datos) return;
        var d = payload.datos;
        var tipo = payload.tipo;
        var nombre = cleanTitle(d.title);

        // Las eliminaciones "técnicas" (reasignación / cambio de visibilidad)
        // llegan sin título: no se muestran al usuario.
        if (tipo === 'eliminado' && !nombre) return;

        var title, body = nombre || 'Evento del calendario';
        if (tipo === 'creado') title = 'Evento creado:';
        else if (tipo === 'editado') title = 'Evento actualizado:';
        else if (tipo === 'eliminado') title = 'Evento eliminado:';
        else return;

        // Evita el doble aviso: si el evento ya cae dentro de una ventana de
        // recordatorio (p. ej. creado para mañana), suprime ese recordatorio
        // inmediato; los futuros (1 hora antes, etc.) seguirán llegando.
        if (tipo === 'creado' || tipo === 'editado') {
            suppressDueReminders(d);
        }

        var rawId = d.raw_id || d.id || '';
        upsertNotification({
            id: 'live_' + tipo + '_' + rawId + '_' + Math.floor(Date.now() / 60000),
            title: title,
            body: body,
            time: Date.now()
        });
        pruneFeed();
        saveFeed();
        render();
    }

    // ---------- Render ----------
    function cardHtml(n) {
        return '<div class="notif-card" data-notif-id="' + escapeHtml(n.id) + '">' +
            '<p><strong>' + escapeHtml(n.title) + '</strong> ' + escapeHtml(n.body) + '</p>' +
            '<small>' + escapeHtml(formatRelativeTime(n.time)) + '</small>' +
            '</div>';
    }

    function render() {
        var unreadBox = document.getElementById('notif-content-unread');
        var readBox = document.getElementById('notif-content-read');
        if (!unreadBox || !readBox) return;

        var sorted = feed.slice().sort(function (a, b) { return b.time - a.time; });
        var unread = sorted.filter(function (n) { return !n.read; });
        var read = sorted.filter(function (n) { return n.read; });

        unreadBox.innerHTML = unread.length
            ? unread.map(cardHtml).join('')
            : '<div class="notif-empty">No hay notificaciones pendientes</div>';
        readBox.innerHTML = read.length
            ? read.map(cardHtml).join('')
            : '<div class="notif-empty">No hay notificaciones leídas</div>';

        document.querySelectorAll('.unread-badge').forEach(function (el) { el.textContent = unread.length; });
        document.querySelectorAll('.read-badge').forEach(function (el) { el.textContent = read.length; });

        var badge = document.getElementById('notification-badge');
        if (badge) {
            if (unread.length > 0) {
                badge.textContent = unread.length > 99 ? '99+' : unread.length;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function refresh() {
        buildReminders();
        pruneFeed();
        saveFeed();
        render();
    }

    // Exponer API para el calendario / socket
    window.rrhhNotif = {
        pushLiveUpdate: pushLiveUpdate,
        refresh: refresh,
        render: render
    };

    // ---------- Interacción UI ----------
    document.addEventListener('DOMContentLoaded', function () {
        var btnNotifications = document.getElementById('btn-notifications');
        var notificationMenu = document.getElementById('notification-menu');
        var markAll = document.getElementById('btn-mark-all-read');

        if (!btnNotifications || !notificationMenu) return;

        btnNotifications.addEventListener('click', function (event) {
            event.stopPropagation();
            var hidden = notificationMenu.style.display === 'none' || notificationMenu.style.display === '';
            notificationMenu.style.display = hidden ? 'flex' : 'none';
            if (hidden) render();
        });

        document.addEventListener('click', function (event) {
            if (!notificationMenu.contains(event.target) && event.target !== btnNotifications) {
                notificationMenu.style.display = 'none';
            }
        });

        notificationMenu.addEventListener('click', function (event) { event.stopPropagation(); });

        document.querySelectorAll('.notif-tab').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.notif-tab').forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');
                document.querySelectorAll('.notif-content').forEach(function (c) { c.style.display = 'none'; });
                var target = document.getElementById('notif-content-' + this.getAttribute('data-tab'));
                if (target) target.style.display = 'block';
            });
        });

        // Marcar como leída al hacer click en una tarjeta no leída
        document.getElementById('notif-content-unread').addEventListener('click', function (e) {
            var card = e.target.closest('.notif-card');
            if (card && card.getAttribute('data-notif-id')) {
                markAsRead(card.getAttribute('data-notif-id'));
            }
        });

        if (markAll) {
            markAll.addEventListener('click', function (e) {
                e.stopPropagation();
                markAllRead();
            });
        }

        // Primer cálculo y refresco periódico (recordatorios + tiempos relativos)
        refresh();
        setInterval(refresh, 60000);
    });
})();
</script>
