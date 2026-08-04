{{-- Compresión de fotos en el navegador antes de subir (acelera el upload). --}}
<script>
window.skylinkCompressImage = window.skylinkCompressImage || function(file, options) {
    options = options || {};
    var maxSide = options.maxSide || 1600;
    var quality = options.quality || 0.72;
    var skipUnder = options.skipUnder || (450 * 1024);

    return new Promise(function(resolve) {
        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
            resolve(file);
            return;
        }
        // Ya es liviana: no reprocesar
        if (file.size && file.size <= skipUnder) {
            resolve(file);
            return;
        }
        if (typeof createImageBitmap !== 'function' && typeof FileReader === 'undefined') {
            resolve(file);
            return;
        }

        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function() {
            try {
                var w = img.naturalWidth || img.width;
                var h = img.naturalHeight || img.height;
                if (!w || !h) {
                    URL.revokeObjectURL(url);
                    resolve(file);
                    return;
                }
                var scale = Math.min(1, maxSide / Math.max(w, h));
                var nw = Math.max(1, Math.round(w * scale));
                var nh = Math.max(1, Math.round(h * scale));
                var canvas = document.createElement('canvas');
                canvas.width = nw;
                canvas.height = nh;
                var ctx = canvas.getContext('2d');
                if (!ctx) {
                    URL.revokeObjectURL(url);
                    resolve(file);
                    return;
                }
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, nw, nh);
                ctx.drawImage(img, 0, 0, nw, nh);
                canvas.toBlob(function(blob) {
                    URL.revokeObjectURL(url);
                    if (!blob || (file.size && blob.size >= file.size)) {
                        resolve(file);
                        return;
                    }
                    var name = (file.name || 'foto.jpg').replace(/\.\w+$/, '') + '.jpg';
                    resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            } catch (e) {
                URL.revokeObjectURL(url);
                resolve(file);
            }
        };
        img.onerror = function() {
            URL.revokeObjectURL(url);
            resolve(file);
        };
        img.src = url;
    });
};
</script>
