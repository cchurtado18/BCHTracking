@extends('layouts.app')

@section('title', 'Gastos')

@section('content')
@php $openForm = $errors->any() || old('category_id') || old('amount_usd'); @endphp
<div class="gx-page">
    <x-module-banner section="Contabilidad" current="Gastos" title="Gastos operativos" subtitle="Marque el flete con Aéreo, Marítimo o Pie cúbico para calcular el costo real en Parámetros. Lo demás (planilla, renta) se resta como gasto extra en rentabilidad.">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
        </x-slot:icon>
        <x-slot:actions>
            <a href="{{ route('accounting.settings.edit') }}" class="mb-btn mb-btn-secondary">Parámetros</a>
            <a href="{{ route('accounting.profitability.index') }}" class="mb-btn mb-btn-secondary">Ver rentabilidad</a>
            <button type="button" class="mb-btn mb-btn-primary" onclick="gxToggleForm()">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Registrar gasto
            </button>
        </x-slot:actions>
    </x-module-banner>

    @if(session('success'))
    <div class="gx-alert gx-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="gx-alert gx-alert-danger">
        <ul class="gx-alert-list">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Panel de registro (se abre con el botón del encabezado) --}}
    <div id="gx-form-panel" class="gx-card gx-form-panel" style="{{ $openForm ? '' : 'display:none;' }}">
        <div class="gx-card-head">
            <h2 class="gx-card-title">Registrar gasto</h2>
            <button type="button" class="gx-close-btn" onclick="gxToggleForm()" aria-label="Cerrar formulario">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="gx-card-body">
            <form method="POST" action="{{ route('accounting.expenses.store') }}">
                @csrf
                <div class="gx-form-grid">
                    <div class="gx-field">
                        <label class="gx-label" for="category_id">Categoría <span class="gx-req">*</span></label>
                        <select name="category_id" id="category_id" class="gx-input" required>
                            <option value="">Seleccione…</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gx-field">
                        <label class="gx-label" for="amount_usd">Monto (USD) <span class="gx-req">*</span></label>
                        <div class="gx-input-affix">
                            <input type="number" step="0.01" min="0.01" name="amount_usd" id="amount_usd" class="gx-input" value="{{ old('amount_usd') }}" placeholder="0.00" required>
                            <span class="gx-affix">USD</span>
                        </div>
                    </div>
                    <div class="gx-field">
                        <label class="gx-label" for="spent_at">Fecha <span class="gx-req">*</span></label>
                        <input type="date" name="spent_at" id="spent_at" class="gx-input" value="{{ old('spent_at', now()->toDateString()) }}" required>
                    </div>
                    <div class="gx-field">
                        <label class="gx-label" for="agency_id">Agencia (opcional)</label>
                        <select name="agency_id" id="agency_id" class="gx-input">
                            <option value="">General / sin agencia</option>
                            @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" @selected(old('agency_id') == $agency->id)>{{ $agency->code }} — {{ $agency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="gx-field">
                        <label class="gx-label" for="service_type">Servicio / flete</label>
                        <select name="service_type" id="service_type" class="gx-input">
                            <option value="">General (no es flete de una vía)</option>
                            <option value="AIR" @selected(old('service_type') === 'AIR')>Flete aéreo</option>
                            <option value="SEA" @selected(old('service_type') === 'SEA')>Flete marítimo</option>
                            <option value="CFT" @selected(old('service_type') === 'CFT')>Flete pie cúbico</option>
                        </select>
                        <p class="gx-hint" style="margin:0.35rem 0 0;font-size:0.75rem;color:#64748b;">Si es el pago al transportista, elija la vía. Parámetros divide ese monto entre las lbs o pie³ recibidos y obtiene el costo exacto.</p>
                    </div>
                    <div class="gx-field gx-field--wide">
                        <label class="gx-label" for="note">Descripción (opcional)</label>
                        <input type="text" name="note" id="note" class="gx-input" value="{{ old('note') }}" placeholder="Ej: Pago de combustible">
                    </div>
                </div>
                <div class="gx-form-actions">
                    <button type="submit" class="gx-btn gx-btn-primary">Guardar gasto</button>
                </div>
            </form>

            <div class="gx-form-divider"></div>

            <form method="POST" action="{{ route('accounting.expenses.store-category') }}" class="gx-newcat-form">
                @csrf
                <div class="gx-field gx-field--grow">
                    <label class="gx-label" for="new_category">Nueva categoría</label>
                    <input type="text" name="name" id="new_category" class="gx-input" placeholder="Ej.: Papelería">
                </div>
                <button type="submit" class="gx-btn gx-btn-secondary">Agregar categoría</button>
            </form>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="gx-kpis">
        <div class="gx-kpi-card gx-kpi-card--danger">
            <span class="gx-kpi-label">Total gastos</span>
            <span class="gx-kpi-value gx-text-red">${{ number_format($periodTotal, 2) }}</span>
            <span class="gx-kpi-note">En el rango filtrado (o mes actual)</span>
        </div>
        <div class="gx-kpi-card">
            <span class="gx-kpi-label">Cantidad</span>
            <span class="gx-kpi-value">{{ $expenses->total() }}</span>
            <span class="gx-kpi-note">Gastos registrados</span>
        </div>
        <div class="gx-kpi-card">
            <span class="gx-kpi-label">Categoría #1</span>
            @if($topCategory)
            <span class="gx-kpi-value gx-kpi-value--md">{{ $topCategory->name }}</span>
            <span class="gx-kpi-note">${{ number_format($topCategory->total, 2) }} · {{ $topCategory->movs }} {{ $topCategory->movs === 1 ? 'movimiento' : 'movimientos' }}</span>
            @else
            <span class="gx-kpi-empty">Sin datos</span>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <div class="gx-card gx-filters-card">
        <form method="GET" action="{{ route('accounting.expenses.index') }}" class="gx-filters-form">
            <div class="gx-field">
                <label class="gx-label" for="from">Desde</label>
                <input type="date" name="from" id="from" class="gx-input" value="{{ request()->filled('from') ? $from->toDateString() : '' }}">
            </div>
            <div class="gx-field">
                <label class="gx-label" for="to">Hasta</label>
                <input type="date" name="to" id="to" class="gx-input" value="{{ request()->filled('to') ? $to->toDateString() : '' }}">
            </div>
            <div class="gx-field">
                <label class="gx-label" for="filter_category">Categoría</label>
                <select name="category_id" id="filter_category" class="gx-input">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gx-filters-actions">
                <button class="gx-btn gx-btn-primary" type="submit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/></svg>
                    Filtrar
                </button>
                <a href="{{ route('accounting.expenses.index') }}" class="gx-clear-link">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="gx-card">
        <div class="gx-table-scroll">
            <table class="gx-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Servicio</th>
                        <th>Descripción</th>
                        <th>Agencia</th>
                        <th>Registrado por</th>
                        <th class="gx-num">Monto</th>
                        <th class="gx-th-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td class="gx-nowrap">{{ $expense->spent_at->format('d/m/Y') }}</td>
                        <td>
                            <span class="gx-cat-badge">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                                {{ $expense->category?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="gx-muted">{{ $expense->service_type ? \App\Support\ServiceType::label($expense->service_type) : 'General' }}</td>
                        <td>{{ $expense->note ?? '—' }}</td>
                        <td class="gx-muted">{{ $expense->agency?->name ?? 'General' }}</td>
                        <td class="gx-muted">{{ $expense->createdBy?->name ?? '—' }}</td>
                        <td class="gx-num">
                            <span class="gx-amount">${{ number_format((float) $expense->amount_usd, 2) }}</span>
                            <span class="gx-currency">USD</span>
                        </td>
                        <td class="gx-actions">
                            <form action="{{ route('accounting.expenses.destroy', $expense) }}" method="POST" class="gx-form-inline" onsubmit="return confirm('¿Eliminar este gasto de ${{ number_format((float) $expense->amount_usd, 2) }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="gx-action-btn gx-action-btn--danger">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="gx-empty">Sin gastos registrados en este período.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
        <div class="gx-card-footer">
            {{ $expenses->links('vendor.pagination.primetrack') }}
        </div>
        @endif
    </div>
</div>

<style>
.gx-page {
    --gx-navy: #0A2D6F;
    --gx-blue: #1E4FA8;
    --gx-green: #2BB673;
    --gx-red: #D64545;
    --gx-red-soft: #FDECEC;
    --gx-red-border: #F6C9C9;
    --gx-line: #E8EEF8;
    --gx-border: #C5D4EB;
    --gx-soft: #F4F8FD;
    --gx-muted: #5E6168;
    padding: 1.15rem 0 2.25rem;
    max-width: 96rem;
    margin: 0 auto;
    width: 100%;
}

/* Header */
.gx-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    background: #fff;
    border: 1px solid var(--gx-line);
    border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem;
    margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.gx-breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.45rem;
}
.gx-breadcrumb strong { color: #334155; font-weight: 700; }
.gx-title-row { display: flex; align-items: center; gap: 0.6rem; }
.gx-title-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    background: linear-gradient(135deg, #D64545, #E86A6A);
    color: #fff;
    box-shadow: 0 6px 14px rgba(214, 69, 69, 0.32);
    flex-shrink: 0;
}
.gx-title {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}
.gx-subtitle {
    margin: 0.4rem 0 0;
    font-size: 0.875rem;
    color: var(--gx-muted);
    line-height: 1.45;
}
.gx-header-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }

/* Botones */
.gx-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    padding: 0.58rem 1.05rem;
    font-size: 0.875rem;
    font-weight: 700;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, color .15s ease, border-color .15s ease;
}
.gx-btn-primary {
    background: var(--gx-navy);
    color: #fff;
    border-color: var(--gx-navy);
    box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25);
}
.gx-btn-primary:hover { background: var(--gx-blue); border-color: var(--gx-blue); color: #fff; transform: translateY(-1px); }
.gx-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.gx-btn-secondary:hover { background: var(--gx-soft); color: var(--gx-navy); border-color: var(--gx-border); }
.gx-btn-outline-green { background: #fff; color: #16794C; border-color: #A7DFC3; }
.gx-btn-outline-green:hover { background: #EFFAF4; border-color: #2BB673; color: #116039; }

/* Alertas */
.gx-alert { padding: 0.85rem 1.05rem; border-radius: 0.7rem; margin-bottom: 1rem; font-size: 0.875rem; }
.gx-alert-success { background: #EFFAF4; border: 1px solid #A7DFC3; color: #116039; font-weight: 600; }
.gx-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.gx-alert-list { margin: 0; padding-left: 1.2rem; }

/* Cards */
.gx-card {
    background: #fff;
    border: 1px solid var(--gx-line);
    border-radius: 0.85rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    overflow: hidden;
    margin-bottom: 1.15rem;
}
.gx-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.55rem;
    padding: 0.85rem 1.1rem;
    border-bottom: 1px solid var(--gx-line);
}
.gx-card-title { margin: 0; font-size: 0.95rem; font-weight: 800; color: #0f172a; }
.gx-card-body { padding: 1rem 1.1rem 1.1rem; }
.gx-card-footer { padding: 0.75rem 1.1rem; border-top: 1px solid var(--gx-line); }
.gx-close-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.9rem;
    height: 1.9rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d9e6;
    background: #fff;
    color: #64748b;
    cursor: pointer;
}
.gx-close-btn:hover { background: var(--gx-soft); color: var(--gx-navy); border-color: var(--gx-border); }

/* Formulario de registro */
.gx-form-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem 0.9rem;
}
.gx-field { display: flex; flex-direction: column; gap: 0.28rem; min-width: 0; }
.gx-field--wide { grid-column: span 2; }
.gx-field--grow { flex: 1; }
.gx-label {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #94a3b8;
}
.gx-req { color: var(--gx-red); }
.gx-input {
    padding: 0.58rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #D8DCE2;
    border-radius: 0.55rem;
    background: #fff;
    color: #0f172a;
    width: 100%;
    box-sizing: border-box;
}
.gx-input:focus { outline: none; border-color: var(--gx-blue); box-shadow: 0 0 0 3px rgba(30, 79, 168, 0.15); }
.gx-input-affix { position: relative; }
.gx-input-affix .gx-input { padding-right: 2.9rem; }
.gx-affix {
    position: absolute;
    right: 0.7rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.68rem;
    font-weight: 800;
    color: #94a3b8;
    pointer-events: none;
}
.gx-form-actions { display: flex; justify-content: flex-end; margin-top: 0.85rem; }
.gx-form-divider { border-top: 1px dashed var(--gx-border); margin: 1rem 0 0.85rem; }
.gx-newcat-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 0.75rem; }

/* KPIs */
.gx-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.85rem;
    margin-bottom: 1.15rem;
}
.gx-kpi-card {
    background: #fff;
    border: 1px solid var(--gx-line);
    border-radius: 0.85rem;
    padding: 0.95rem 1.1rem;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}
.gx-kpi-card--danger {
    background: linear-gradient(180deg, #fff 0%, var(--gx-red-soft) 130%);
    border-color: var(--gx-red-border);
}
.gx-kpi-label {
    font-size: 0.66rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #94a3b8;
}
.gx-kpi-card--danger .gx-kpi-label { color: var(--gx-red); }
.gx-kpi-value {
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}
.gx-kpi-value--md { font-size: 1.15rem; }
.gx-kpi-note { font-size: 0.72rem; color: #94a3b8; }
.gx-kpi-empty { font-size: 0.95rem; color: #94a3b8; }
.gx-text-red { color: var(--gx-red); }

/* Filtros */
.gx-filters-card { padding: 0.9rem 1.1rem; overflow: visible; }
.gx-filters-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem;
}
.gx-filters-form .gx-field { flex: 1; min-width: 9.5rem; max-width: 16rem; }
.gx-filters-actions { display: flex; align-items: center; gap: 0.65rem; }
.gx-clear-link { font-size: 0.8rem; font-weight: 700; color: #64748b; text-decoration: none; }
.gx-clear-link:hover { color: var(--gx-navy); text-decoration: underline; }

/* Tabla */
.gx-table-scroll { overflow-x: auto; }
.gx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.gx-table thead th {
    background: linear-gradient(135deg, var(--gx-navy), var(--gx-blue));
    color: #fff;
    text-align: left;
    padding: 0.62rem 0.85rem;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.gx-table td {
    padding: 0.66rem 0.85rem;
    border-bottom: 1px solid #f4f7fb;
    color: #334155;
    vertical-align: middle;
}
.gx-table tbody tr:last-child td { border-bottom: none; }
.gx-table tbody tr:hover td { background: var(--gx-soft); }
.gx-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.gx-th-actions { text-align: right; }
.gx-actions { text-align: right; }
.gx-nowrap { white-space: nowrap; }
.gx-muted { color: #94a3b8; }
.gx-empty { padding: 1.4rem 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem; }

.gx-cat-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.22rem 0.6rem;
    border-radius: 999px;
    background: var(--gx-red-soft);
    color: #B03030;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}
.gx-amount { font-weight: 800; color: var(--gx-red); }
.gx-currency { font-size: 0.65rem; color: #94a3b8; font-weight: 700; margin-left: 0.2rem; }

.gx-form-inline { display: inline-flex; margin: 0; }
.gx-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.34rem 0.7rem;
    font-size: 0.75rem;
    font-weight: 700;
    border-radius: 0.5rem;
    border: 1px solid #d1d9e6;
    background: #fff;
    color: #475569;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.gx-action-btn--danger:hover { background: var(--gx-red-soft); color: var(--gx-red); border-color: var(--gx-red-border); }

@media (max-width: 1100px) {
    .gx-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gx-field--wide { grid-column: span 2; }
}
@media (max-width: 768px) {
    .gx-kpis { grid-template-columns: 1fr; }
    .gx-form-grid { grid-template-columns: 1fr; }
    .gx-field--wide { grid-column: auto; }
    .gx-filters-form .gx-field { max-width: none; }
    .gx-header { padding: 0.9rem 1rem; }
}
</style>
<script>
function gxToggleForm() {
    var panel = document.getElementById('gx-form-panel');
    if (!panel) return;
    var hidden = panel.style.display === 'none';
    panel.style.display = hidden ? '' : 'none';
    if (hidden) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var first = panel.querySelector('select, input');
        if (first) first.focus();
    }
}
</script>
@endsection
