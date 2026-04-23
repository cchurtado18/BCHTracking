@extends('layouts.app')

@section('title', 'Crear saco — selección')

@section('content')
<div class="cons-page">
    <header class="cons-hero">
        <div class="cons-hero-inner">
            <div class="cons-hero-text">
                <h1 class="cons-hero-title">Crear saco — selección en tabla</h1>
                <p class="cons-hero-subtitle">Marca los preregistros en Miami que van en este saco. Para armar por pistola use el modo escaneo desde la pantalla anterior.</p>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                <a href="{{ route('consolidations.create') }}" class="cons-hero-btn" style="background:rgba(255,255,255,0.2);color:#fff;border-color:rgba(255,255,255,0.4);">← Otros modos</a>
                <a href="{{ route('consolidations.index') }}" class="cons-hero-btn">Lista de sacos</a>
            </div>
        </div>
    </header>

    <form action="{{ route('consolidations.store') }}" method="POST" id="consolidationForm">
        @csrf

        <div class="cons-grid">
            <!-- Formulario de Creación -->
            <div class="cons-card cons-form-card">
                <div class="cons-card-header cons-table-header">
                    <h2 class="cons-card-title">Información del Saco</h2>
                </div>
                <div class="cons-card-body">
                    <div class="cons-form-fields">
                        <div class="cons-field">
                            <label for="service_type" class="cons-label">Tipo de Servicio *</label>
                            <select name="service_type" id="service_type" required class="cons-select">
                                <option value="AIR">Aéreo</option>
                                <option value="SEA">Marítimo</option>
                            </select>
                        </div>

                        <div class="cons-field">
                            <label for="notes" class="cons-label">Notas</label>
                            <textarea name="notes" id="notes" rows="3" class="cons-textarea" placeholder="Notas adicionales sobre el saco..."></textarea>
                        </div>
                    </div>

                    <div class="cons-selected-wrap">
                        <div class="cons-selected-box">
                            <span class="cons-selected-label">Seleccionados:</span>
                            <span id="selectedCount">0</span> preregistro(s)
                        </div>
                        <div class="cons-form-actions">
                            <a href="{{ route('consolidations.index') }}" class="cons-btn cons-btn-secondary">Cancelar</a>
                            <button type="submit" class="cons-btn cons-btn-primary">Crear Saco con Seleccionados</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preregistros Disponibles -->
            <div class="cons-card cons-list-card">
                <div class="cons-card-header cons-table-header cons-list-header">
                    <h2 class="cons-card-title">Preregistros Disponibles</h2>
                    <div class="cons-header-actions">
                        <button type="button" id="selectAllBtn" class="cons-btn cons-btn-sm cons-btn-outline-light">Seleccionar Todos</button>
                        <button type="button" id="deselectAllBtn" class="cons-btn cons-btn-sm cons-btn-outline-light">Deseleccionar</button>
                    </div>
                </div>
                <div class="cons-card-body">
                    <!-- Filtros por Fecha -->
                    <div class="cons-filters">
                        <div class="cons-filters-grid">
                            <div class="cons-field">
                                <label for="filter_date_from" class="cons-label">Fecha Desde</label>
                                <input type="date" id="filter_date_from" class="cons-input">
                            </div>
                            <div class="cons-field">
                                <label for="filter_date_to" class="cons-label">Fecha Hasta</label>
                                <input type="date" id="filter_date_to" class="cons-input">
                            </div>
                            <div class="cons-field cons-field-actions">
                                <button type="button" id="clearFiltersBtn" class="cons-btn cons-btn-secondary">Limpiar Filtros</button>
                            </div>
                        </div>
                    </div>

                    <!-- Aéreo -->
                    <div id="available-air" class="cons-service-block">
                        <h3 class="cons-service-title">
                            Aéreo
                            <span class="cons-badge cons-badge-air" id="air-count">
                                {{ $availableByServiceType['AIR']->count() }} disponible(s)
                            </span>
                        </h3>
                        @if($availableByServiceType['AIR']->count() > 0)
                            <div class="cons-table-wrap">
                                <table class="cons-table" id="air-list">
                                    <thead>
                                        <tr>
                                            <th class="cons-th-check"></th>
                                            <th>Nombre</th>
                                            <th>Warehouse</th>
                                            <th>Peso</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availableByServiceType['AIR'] as $preregistration)
                                        <tr class="prereg-item cons-table-row" data-service-type="AIR" data-date="{{ $preregistration->created_at->format('Y-m-d') }}">
                                            <td class="cons-td-check">
                                                <input type="checkbox" name="preregistration_ids[]" value="{{ $preregistration->id }}" class="prereg-checkbox" data-weight="{{ $preregistration->intake_weight_lbs }}">
                                            </td>
                                            <td class="cons-td-name">{{ $preregistration->label_name }}</td>
                                            <td class="cons-td-code">{{ $preregistration->warehouse_code ?? $preregistration->tracking_external ?? 'N/A' }}</td>
                                            <td class="cons-td-weight">{{ $preregistration->intake_weight_lbs }} lbs</td>
                                            <td class="cons-td-date">{{ $preregistration->created_at->format('d/m/Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cons-empty-block">
                                <p class="cons-empty-text">No hay preregistros Aéreos disponibles</p>
                            </div>
                        @endif
                    </div>

                    <!-- Marítimo -->
                    <div id="available-sea" class="cons-service-block" style="display: none;">
                        <h3 class="cons-service-title">
                            Marítimo
                            <span class="cons-badge cons-badge-sea" id="sea-count">
                                {{ $availableByServiceType['SEA']->count() }} disponible(s)
                            </span>
                        </h3>
                        @if($availableByServiceType['SEA']->count() > 0)
                            <div class="cons-table-wrap">
                                <table class="cons-table" id="sea-list">
                                    <thead>
                                        <tr>
                                            <th class="cons-th-check"></th>
                                            <th>Nombre</th>
                                            <th>Warehouse</th>
                                            <th>Peso</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($availableByServiceType['SEA'] as $preregistration)
                                        <tr class="prereg-item cons-table-row" data-service-type="SEA" data-date="{{ $preregistration->created_at->format('Y-m-d') }}">
                                            <td class="cons-td-check">
                                                <input type="checkbox" name="preregistration_ids[]" value="{{ $preregistration->id }}" class="prereg-checkbox" data-weight="{{ $preregistration->intake_weight_lbs }}">
                                            </td>
                                            <td class="cons-td-name">{{ $preregistration->label_name }}</td>
                                            <td class="cons-td-code">{{ $preregistration->warehouse_code ?? $preregistration->tracking_external ?? 'N/A' }}</td>
                                            <td class="cons-td-weight">{{ $preregistration->intake_weight_lbs }} lbs</td>
                                            <td class="cons-td-date">{{ $preregistration->created_at->format('d/m/Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="cons-empty-block">
                                <p class="cons-empty-text">No hay preregistros Marítimos disponibles</p>
                            </div>
                        @endif
                    </div>

                    @if($availableByServiceType['AIR']->count() == 0 && $availableByServiceType['SEA']->count() == 0)
                        <div class="cons-empty-state">
                            <p class="cons-empty-state-text">No hay preregistros disponibles</p>
                            <p class="cons-empty-state-hint">Crea preregistros con estado RECEIVED_MIAMI para poder agregarlos a un saco</p>
                            <a href="{{ route('preregistrations.create') }}" class="cons-btn cons-btn-outline-primary">→ Crear Preregistro</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.cons-page { padding: 1.5rem 0; max-width: 96rem; margin: 0 auto; width: 100%; }

.cons-hero {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-radius: 1rem;
    padding: 1.75rem 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
}
.cons-hero-inner { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; }
.cons-hero-title { margin: 0; font-size: 1.75rem; font-weight: 700; color: #fff; letter-spacing: -0.02em; }
.cons-hero-subtitle { margin: 0.35rem 0 0; font-size: 0.9375rem; color: rgba(255,255,255,0.9); max-width: 42ch; }
.cons-hero-btn {
    display: inline-flex; align-items: center; padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
    color: #0f766e; background: #fff; border: 1px solid rgba(255,255,255,0.5); border-radius: 0.5rem; text-decoration: none;
}
.cons-hero-btn:hover { background: #f0fdfa; color: #0d9488; }

.cons-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
@media (min-width: 1024px) { .cons-grid { grid-template-columns: 360px 1fr; } }

.cons-card {
    background: #fff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    overflow: hidden;
}
.cons-card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.cons-card-header.cons-table-header {
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
}
.cons-table-header .cons-card-title { color: #fff; }
.cons-list-header { flex-wrap: wrap; }
.cons-card-title { margin: 0; font-size: 0.9375rem; font-weight: 600; color: #374151; }
.cons-card-body { padding: 1.25rem 1.5rem; }
.cons-header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.cons-form-fields { display: flex; flex-direction: column; gap: 1rem; }
.cons-field { display: flex; flex-direction: column; gap: 0.35rem; }
.cons-label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
.cons-select, .cons-input, .cons-textarea {
    width: 100%;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    background: #fff;
    color: #111827;
}
.cons-textarea { resize: vertical; min-height: 80px; }
.cons-select:focus, .cons-input:focus, .cons-textarea:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
}

.cons-selected-wrap { margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #e5e7eb; }
.cons-selected-box {
    padding: 0.75rem 1rem;
    background: rgba(13, 148, 136, 0.1);
    border: 1px solid rgba(13, 148, 136, 0.3);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #0f766e;
    margin-bottom: 1rem;
}
.cons-selected-label { font-weight: 600; }
.cons-form-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: flex-end; }

.cons-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
    border-radius: 0.5rem; border: 1px solid transparent;
    cursor: pointer; text-decoration: none;
}
.cons-btn-primary { background: #0d9488; color: #fff; border-color: #0d9488; font-weight: 600; }
.cons-btn-primary:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
.cons-btn-secondary { background: #f3f4f6; color: #374151; border-color: #e5e7eb; }
.cons-btn-secondary:hover { background: #e5e7eb; color: #111827; }
.cons-btn-outline-light {
    background: rgba(255,255,255,0.2);
    color: #fff;
    border-color: rgba(255,255,255,0.4);
}
.cons-btn-outline-light:hover { background: rgba(255,255,255,0.3); color: #fff; }
.cons-btn-outline-primary { background: #fff; color: #0d9488; border-color: #0d9488; }
.cons-btn-outline-primary:hover { background: #ccfbf1; color: #0f766e; }
.cons-btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }

.cons-filters { margin-bottom: 1.25rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
.cons-filters-grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end; flex-wrap: wrap; }
@media (max-width: 640px) { .cons-filters-grid { grid-template-columns: 1fr; } }
.cons-field-actions { display: flex; align-items: flex-end; }

.cons-service-block { margin-bottom: 1.5rem; }
.cons-service-block:last-child { margin-bottom: 0; }
.cons-service-title { font-size: 0.9375rem; font-weight: 600; color: #374151; margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.cons-badge { display: inline-block; padding: 0.2rem 0.5rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; }
.cons-badge-air { background: #dbeafe; color: #1d4ed8; }
.cons-badge-sea { background: #d1fae5; color: #047857; }

.cons-table-wrap { max-height: 24rem; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
.cons-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.cons-table thead { position: sticky; top: 0; z-index: 1; }
.cons-table th {
    text-align: left;
    padding: 0.65rem 0.75rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #14b8a6 100%);
    border-bottom: 1px solid rgba(255,255,255,0.2);
}
.cons-th-check { width: 2.5rem; }
.cons-table td { padding: 0.65rem 0.75rem; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
.cons-table tbody tr:hover { background: #f9fafb; }
.cons-td-check { width: 2.5rem; }
.cons-td-name { font-weight: 500; color: #111827; }
.cons-td-code { font-family: ui-monospace, monospace; color: #6b7280; }
.cons-td-weight, .cons-td-date { color: #6b7280; }

.cons-empty-block { padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.5rem; }
.cons-empty-text { margin: 0; font-size: 0.875rem; color: #6b7280; }

.cons-empty-state {
    text-align: center;
    padding: 2rem 1rem;
    border: 2px dashed #e5e7eb;
    border-radius: 0.75rem;
    margin-top: 1rem;
}
.cons-empty-state-text { margin: 0 0 0.25rem; font-size: 0.9375rem; font-weight: 500; color: #6b7280; }
.cons-empty-state-hint { margin: 0 0 1rem; font-size: 0.8125rem; color: #9ca3af; }
.cons-empty-state .cons-btn { margin-top: 0.5rem; }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const serviceTypeSelect = document.getElementById('service_type');
        const availableAir = document.getElementById('available-air');
        const availableSea = document.getElementById('available-sea');
        const filterDateFrom = document.getElementById('filter_date_from');
        const filterDateTo = document.getElementById('filter_date_to');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const selectedCountSpan = document.getElementById('selectedCount');

        function updateVisibility() {
            const selectedType = serviceTypeSelect.value;
            if (selectedType === 'AIR') {
                availableAir.style.display = 'block';
                availableSea.style.display = 'none';
            } else {
                availableAir.style.display = 'none';
                availableSea.style.display = 'block';
            }
            updateSelectedCount();
        }

        function filterByDate() {
            const dateFrom = filterDateFrom.value;
            const dateTo = filterDateTo.value;
            const currentType = serviceTypeSelect.value;
            const listId = currentType === 'AIR' ? 'air-list' : 'sea-list';
            const items = document.querySelectorAll(`#${listId} .prereg-item`);

            items.forEach(item => {
                const itemDate = item.getAttribute('data-date');
                let show = true;
                if (dateFrom && itemDate < dateFrom) show = false;
                if (dateTo && itemDate > dateTo) show = false;
                item.style.display = show ? '' : 'none';
            });
            updateCounts();
        }

        function updateCounts() {
            const currentType = serviceTypeSelect.value;
            const listId = currentType === 'AIR' ? 'air-list' : 'sea-list';
            const visibleItems = Array.from(document.querySelectorAll(`#${listId} .prereg-item`)).filter(function(item) {
                return item.style.display !== 'none';
            });
            const countElement = currentType === 'AIR' ? document.getElementById('air-count') : document.getElementById('sea-count');
            if (countElement) {
                const total = currentType === 'AIR' ? {{ $availableByServiceType['AIR']->count() }} : {{ $availableByServiceType['SEA']->count() }};
                const visible = visibleItems.length;
                countElement.textContent = visible + ' visible(s) de ' + total;
            }
        }

        function updateSelectedCount() {
            const currentType = serviceTypeSelect.value;
            const listId = currentType === 'AIR' ? 'air-list' : 'sea-list';
            const checkboxes = document.querySelectorAll(`#${listId} .prereg-checkbox:checked`);
            selectedCountSpan.textContent = checkboxes.length;
        }

        selectAllBtn.addEventListener('click', function() {
            const currentType = serviceTypeSelect.value;
            const listId = currentType === 'AIR' ? 'air-list' : 'sea-list';
            const visibleItems = Array.from(document.querySelectorAll(`#${listId} .prereg-item`)).filter(function(item) {
                return item.style.display !== 'none';
            });
            visibleItems.forEach(function(item) {
                const checkbox = item.querySelector('.prereg-checkbox');
                if (checkbox) checkbox.checked = true;
            });
            updateSelectedCount();
        });

        deselectAllBtn.addEventListener('click', function() {
            const checkboxes = document.querySelectorAll('.prereg-checkbox');
            checkboxes.forEach(function(cb) { cb.checked = false; });
            updateSelectedCount();
        });

        clearFiltersBtn.addEventListener('click', function() {
            filterDateFrom.value = '';
            filterDateTo.value = '';
            filterByDate();
        });

        serviceTypeSelect.addEventListener('change', updateVisibility);
        filterDateFrom.addEventListener('change', filterByDate);
        filterDateTo.addEventListener('change', filterByDate);

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('prereg-checkbox')) updateSelectedCount();
        });

        updateVisibility();
        updateSelectedCount();
    });
</script>
@endpush
@endsection
