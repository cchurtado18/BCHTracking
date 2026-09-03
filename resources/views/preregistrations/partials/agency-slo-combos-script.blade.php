<script>
document.addEventListener('DOMContentLoaded', function() {
    var agenciesEl = document.getElementById('agencies-data');
    var clientsEl = document.getElementById('slo-clients-data');
    var combo = document.getElementById('agency_combobox');
    var dropdown = document.getElementById('agency_dropdown');
    var wrap = document.getElementById('agency_combobox_wrap');
    var partnerHidden = document.getElementById('partner_agency_id');
    var hidden = document.getElementById('agency_id');
    var sloWrap = document.getElementById('slo_client_wrap');
    var sloCombo = document.getElementById('slo_client_combobox');
    var sloDropdown = document.getElementById('slo_client_dropdown');
    var sloComboWrap = document.getElementById('slo_combobox_wrap');
    if (!combo || !dropdown || !hidden) return;

    var agencies = agenciesEl ? JSON.parse(agenciesEl.textContent || '[]') : [];
    var sloClients = clientsEl ? JSON.parse(clientsEl.textContent || '[]') : [];

    function labelOf(row) {
        return ((row.code || '') + ' - ' + (row.name || '')).replace(/^\s-\s/, '');
    }
    function findById(list, id) {
        id = String(id || '');
        for (var i = 0; i < list.length; i++) {
            if (String(list[i].id) === id) return list[i];
        }
        return null;
    }
    function matches(row, q) {
        if (!q) return true;
        return (row.name || '').toLowerCase().indexOf(q) !== -1
            || (row.code || '').toLowerCase().indexOf(q) !== -1;
    }
    function renderItems(target, list, filter) {
        var q = (filter || '').trim().toLowerCase();
        var filtered = list.filter(function(row) { return matches(row, q); });
        target.innerHTML = filtered.length
            ? filtered.map(function(row) {
                var label = labelOf(row);
                return '<div class="agency-combo-item" role="option" data-id="' + row.id + '" data-label="' + label.replace(/"/g, '&quot;') + '">' + label + '</div>';
            }).join('')
            : '<div class="preregs-combo-empty">No hay coincidencias</div>';
        target.style.display = 'block';
    }
    function notifyPreview() {
        if (typeof updatePreview === 'function') updatePreview();
    }
    function setSloVisible(show) {
        if (!sloWrap) return;
        sloWrap.style.display = show ? '' : 'none';
        if (!show && sloCombo) sloCombo.value = '';
    }
    function selectPartner(id, label, isSlo) {
        if (partnerHidden) partnerHidden.value = id || '';
        combo.value = label || '';
        dropdown.style.display = 'none';
        if (isSlo) {
            setSloVisible(true);
            var client = findById(sloClients, hidden.value);
            hidden.value = client ? String(client.id) : '';
            if (sloCombo && client) sloCombo.value = labelOf(client);
            else if (sloCombo && !client) sloCombo.value = '';
        } else {
            setSloVisible(false);
            hidden.value = id || '';
        }
        notifyPreview();
    }
    function selectSloClient(id, label) {
        if (sloCombo) sloCombo.value = label || '';
        if (sloDropdown) sloDropdown.style.display = 'none';
        hidden.value = id || '';
        notifyPreview();
    }

    combo.addEventListener('focus', function() {
        combo.select();
        renderItems(dropdown, agencies, '');
    });
    combo.addEventListener('input', function() {
        hidden.value = '';
        if (partnerHidden) partnerHidden.value = '';
        setSloVisible(false);
        renderItems(dropdown, agencies, this.value);
    });
    combo.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { dropdown.style.display = 'none'; combo.blur(); }
    });
    dropdown.addEventListener('mousedown', function(e) {
        var item = e.target.closest('.agency-combo-item');
        if (!item) return;
        e.preventDefault();
        var row = findById(agencies, item.getAttribute('data-id'));
        selectPartner(item.getAttribute('data-id'), item.getAttribute('data-label'), !!(row && row.is_slo));
    });
    document.addEventListener('click', function(e) {
        if (dropdown.style.display === 'block' && wrap && !e.target.closest('#agency_combobox_wrap')) {
            dropdown.style.display = 'none';
        }
        if (sloDropdown && sloDropdown.style.display === 'block' && sloComboWrap && !e.target.closest('#slo_combobox_wrap')) {
            sloDropdown.style.display = 'none';
        }
    });

    if (sloCombo && sloDropdown) {
        sloCombo.addEventListener('focus', function() {
            sloCombo.select();
            renderItems(sloDropdown, sloClients, '');
        });
        sloCombo.addEventListener('input', function() {
            hidden.value = '';
            renderItems(sloDropdown, sloClients, this.value);
        });
        sloCombo.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { sloDropdown.style.display = 'none'; sloCombo.blur(); }
        });
        sloDropdown.addEventListener('mousedown', function(e) {
            var item = e.target.closest('.agency-combo-item');
            if (!item) return;
            e.preventDefault();
            selectSloClient(item.getAttribute('data-id'), item.getAttribute('data-label'));
        });
    }

    var initialId = hidden.value;
    if (!initialId) return;
    var asPartner = findById(agencies, initialId);
    if (asPartner) {
        selectPartner(asPartner.id, labelOf(asPartner), !!asPartner.is_slo);
        return;
    }
    var asClient = findById(sloClients, initialId);
    var sloPartner = agencies.filter(function(a) { return a.is_slo; })[0];
    if (asClient && sloPartner) {
        selectPartner(sloPartner.id, labelOf(sloPartner), true);
        selectSloClient(asClient.id, labelOf(asClient));
    }
});
</script>
