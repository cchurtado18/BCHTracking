@props([
    'section' => 'General',
    'current' => '',
    'title' => '',
    'subtitle' => '',
    'hideBack' => false,
    'backHref' => null,
    'backLabel' => 'Volver al panel',
])
@php
    $backHref = $backHref ?: route('dashboard');
@endphp
<header {{ $attributes->class('mb-banner') }}>
    <div class="mb-banner-top">
        <div class="mb-banner-left">
            <nav class="mb-breadcrumb" aria-label="Ruta">
                <span>{{ $section }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                <strong>{{ $current !== '' ? $current : $title }}</strong>
            </nav>
            <div class="mb-title-row">
                @if(isset($icon))
                <span class="mb-title-icon" aria-hidden="true">{{ $icon }}</span>
                @endif
                <h1 class="mb-title">{{ $title }}</h1>
            </div>
            @if($subtitle !== '')
            <p class="mb-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="mb-banner-actions">
            @unless($hideBack)
            <a href="{{ $backHref }}" class="mb-btn mb-btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                {{ $backLabel }}
            </a>
            @endunless
            {{ $actions ?? '' }}
        </div>
    </div>
    @if(isset($strip) && ! $strip->isEmpty())
    <div class="mb-strip">{{ $strip }}</div>
    @endif
</header>

@once
<style>
.mb-banner {
    display: flex; flex-direction: column; gap: 0;
    background: #fff; border: 1px solid #E8EEF8; border-radius: 1rem;
    padding: 1.05rem 1.25rem 1.1rem; margin-bottom: 1.15rem;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
}
.mb-banner-top { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; }
.mb-breadcrumb { display: flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.45rem; }
.mb-breadcrumb strong { color: #334155; font-weight: 700; }
.mb-title-row { display: flex; align-items: center; gap: 0.6rem; }
.mb-title-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2.35rem; height: 2.35rem; border-radius: 0.65rem;
    background: linear-gradient(135deg, #0A2D6F, #1E4FA8); color: #fff;
    box-shadow: 0 6px 14px rgba(10, 45, 111, 0.28); flex-shrink: 0;
}
.mb-title-icon svg { width: 1.25rem; height: 1.25rem; }
.mb-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
.mb-subtitle { margin: 0.4rem 0 0; font-size: 0.875rem; color: #5E6168; line-height: 1.45; max-width: 44rem; }
.mb-banner-actions { display: flex; flex-wrap: wrap; gap: 0.55rem; align-self: center; }
.mb-banner-actions form { margin: 0; display: inline-flex; }
.mb-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.58rem 1.05rem; font-size: 0.875rem; font-weight: 700; border-radius: 0.6rem;
    border: 1px solid transparent; cursor: pointer; text-decoration: none;
}
.mb-btn-primary { background: #0A2D6F; color: #fff; border-color: #0A2D6F; box-shadow: 0 5px 14px rgba(10, 45, 111, 0.25); }
.mb-btn-primary:hover { background: #1E4FA8; border-color: #1E4FA8; color: #fff; }
.mb-btn-secondary { background: #fff; color: #334155; border-color: #d1d9e6; }
.mb-btn-secondary:hover { background: #F4F8FD; color: #0A2D6F; border-color: #C5D4EB; }
.mb-btn-danger { background: #fff; color: #B03030; border-color: #F6C9C9; }
.mb-btn-danger:hover { background: #FDECEC; color: #B03030; }
.mb-btn:disabled, .mb-btn[disabled] { opacity: 0.5; cursor: not-allowed; }
.mb-strip {
    display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;
    margin-top: 0.95rem; padding-top: 0.85rem; border-top: 1px solid #E8EEF8;
}
.mb-strip-label {
    display: inline-flex; align-items: center; gap: 0.35rem;
    font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8;
}
.mb-pill {
    display: inline-flex; align-items: center; gap: 0.3rem;
    padding: 0.26rem 0.7rem; border-radius: 999px;
    background: #F4F8FD; border: 1px solid #C5D4EB; color: #0A2D6F;
    font-size: 0.75rem; font-weight: 650;
}
.mb-pill strong { font-weight: 800; }
.mb-pill--ok { background: #EFFAF4; border-color: #A7DFC3; color: #116039; }
.mb-pill--warn { background: #FDECEC; border-color: #F6C9C9; color: #B03030; }
</style>
@endonce
