<form method="POST"
      action="{{ route('preregistrations.photos.destroy', ['id' => $photoDeleteId, 'photo' => $photo->id]) }}"
      class="prd-photo-delete"
      onsubmit="return confirm('¿Eliminar esta foto? Esta acción no se puede deshacer.');">
    @csrf
    @method('DELETE')
    <button type="submit" class="prd-photo-delete-btn" title="Eliminar foto" aria-label="Eliminar foto">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
        </svg>
    </button>
</form>

@once
<style>
.prd-photo-item,
.preregs-photo-wrap,
.quick-photo-item {
    position: relative;
}
.prd-photo-delete {
    position: absolute;
    top: 0.4rem;
    right: 0.4rem;
    z-index: 3;
    margin: 0;
}
.prd-photo-delete-btn {
    width: 2rem;
    height: 2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 999px;
    background: rgba(185, 28, 28, 0.94);
    color: #fff;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.28);
}
.prd-photo-delete-btn:hover {
    background: #991b1b;
}
.prd-photo-delete-btn svg {
    width: 1rem;
    height: 1rem;
    display: block;
    pointer-events: none;
}
</style>
@endonce
