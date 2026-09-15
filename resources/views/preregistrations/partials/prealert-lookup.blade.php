<div id="prealertNotice" class="prealert-notice" hidden role="status"></div>

<style>
.prealert-notice {
    margin: 0.65rem 0 0;
    padding: 0.75rem 0.9rem;
    border-radius: 0.7rem;
    border: 1px solid #86c9a4;
    background: #ecfdf3;
    color: #14532d;
}
.prealert-notice[hidden] { display: none !important; }
.prealert-notice-title {
    margin: 0 0 0.25rem;
    font-size: 0.92rem;
    font-weight: 800;
}
.prealert-notice-meta { margin: 0; font-size: 0.85rem; line-height: 1.4; }
.prealert-notice-meta strong { font-weight: 800; }
</style>

<script>
window.skylinkPrealertLookupUrl = @json(route('prealerts.lookup'));
window.skylinkLookupPrealert = function (tracking) {
    var code = String(tracking || '').replace(/\s+/g, '').toUpperCase();
    if (code.length < 8) return Promise.resolve(null);
    return fetch(window.skylinkPrealertLookupUrl + '?tracking=' + encodeURIComponent(code), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
    }).then(function (res) {
        if (!res.ok) return null;
        return res.json();
    }).then(function (data) {
        return data && data.found ? data.prealert : null;
    }).catch(function () { return null; });
};
window.skylinkEscapeHtml = function (value) {
    return String(value || '').replace(/[&<>"']/g, function (ch) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
    });
};
window.skylinkRenderPrealertNotice = function (host, data) {
    if (!host) return;
    if (!data) {
        host.hidden = true;
        host.innerHTML = '';
        return;
    }
    var agency = [data.agency_code, data.agency_name].filter(Boolean).join(' · ');
    host.innerHTML = '<p class="prealert-notice-title">Este paquete ya fue prealertado</p>'
        + '<p class="prealert-notice-meta"><strong>' + window.skylinkEscapeHtml(data.name || '') + '</strong>'
        + (data.service_label ? ' · ' + window.skylinkEscapeHtml(data.service_label) : '')
        + (agency ? ' · ' + window.skylinkEscapeHtml(agency) : '')
        + (data.description ? '<br>' + window.skylinkEscapeHtml(data.description) : '')
        + '</p>';
    host.hidden = false;
};
window.skylinkBindPrealertLookup = function (input, host) {
    if (!input) return;
    var timer = null;
    var last = '';
    function run() {
        var code = String(input.value || '').replace(/\s+/g, '').toUpperCase();
        if (code === last) return;
        last = code;
        if (code.length < 8) {
            window.skylinkRenderPrealertNotice(host, null);
            return;
        }
        window.skylinkLookupPrealert(code).then(function (data) {
            if (String(input.value || '').replace(/\s+/g, '').toUpperCase() !== code) return;
            window.skylinkRenderPrealertNotice(host, data);
            if (!data) return;
            var nameField = document.getElementById('label_name');
            if (nameField && !String(nameField.value || '').trim() && data.name) {
                nameField.value = data.name;
            }
            var descField = document.getElementById('description');
            if (descField && !String(descField.value || '').trim() && data.description) {
                descField.value = data.description;
            }
            if (data.service_type) {
                ['service_type', 'service_type_multi', 'service_type_post'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (el && !String(el.value || '').trim()) {
                        el.value = data.service_type;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        });
    }
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(run, 220);
    });
    input.addEventListener('change', run);
    if (String(input.value || '').trim()) run();
};
document.addEventListener('DOMContentLoaded', function () {
    window.skylinkBindPrealertLookup(
        document.getElementById('tracking_external'),
        document.getElementById('prealertNotice')
    );
});
</script>
