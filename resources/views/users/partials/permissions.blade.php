@php
    $permissionModules = $permissionModules ?? [];
    $permissionActions = $permissionActions ?? [];
    $selected = collect(old('permissions', $selectedPermissions ?? []))->flip();
    $groups = collect($permissionModules)->groupBy('group');
@endphp
<div class="cx-perm" id="user-permissions" hidden>
    <div class="cx-section-head">
        <h2 class="cx-section-title">Módulos</h2>
        <p class="cx-section-note">Solo verá en el menú los módulos marcados. Un administrador tiene todos.</p>
    </div>
    <div class="cx-card-body">
        @foreach($groups as $group => $items)
        <div class="cx-perm-group">
            <p class="cx-perm-group-title">{{ $group }}</p>
            <div class="cx-perm-grid">
                @foreach($items as $item)
                <label class="cx-perm-item">
                    <input type="checkbox" name="permissions[]" value="{{ $item['key'] }}" {{ $selected->has($item['key']) ? 'checked' : '' }}>
                    <span>{{ $item['label'] }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div class="cx-section-head">
        <h2 class="cx-section-title">Opciones sensibles</h2>
        <p class="cx-section-note">Acciones que no deben tener todos los de operaciones.</p>
    </div>
    <div class="cx-card-body">
        <div class="cx-perm-actions">
            @foreach($permissionActions as $item)
            <label class="cx-perm-item cx-perm-item-wide">
                <input type="checkbox" name="permissions[]" value="{{ $item['key'] }}" {{ $selected->has($item['key']) ? 'checked' : '' }}>
                <span>
                    <strong>{{ $item['label'] }}</strong>
                    <small>{{ $item['hint'] }}</small>
                </span>
            </label>
            @endforeach
        </div>
    </div>
</div>
