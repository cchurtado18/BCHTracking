<div class="nic-item-row nic-scan-item nic-scanned-item" data-code="{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? '' }}">
    <div class="nic-item-name">{{ $item->preregistration->label_name }}</div>
    <div class="nic-item-meta">
        Código: <span class="font-mono">{{ $item->preregistration->warehouse_code ?? $item->preregistration->tracking_external ?? 'N/A' }}</span>
        @if($item->preregistration->bultos_total && $item->preregistration->bultos_total > 1)
        <span class="nic-item-bulto">(bulto {{ $item->preregistration->bulto_index }} de {{ $item->preregistration->bultos_total }})</span>
        @endif
    </div>
    <div class="nic-item-scanned-at">✓ {{ $item->scanned_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
</div>
