{{-- Visor: busca el tracking sin parar; el obturador solo aparece cuando lo lee. --}}
<div id="cspOverlay" class="csp-overlay" hidden>
    <video id="cspVideo" class="csp-video" autoplay muted playsinline webkit-playsinline></video>
    <canvas id="cspWork" class="csp-work" hidden></canvas>
    <div class="csp-frame" aria-hidden="true"></div>
    <div class="csp-bar csp-bar-top">
        <p id="cspHint" class="csp-hint">Apunte el código de barras del tracking</p>
        <p id="cspRead" class="csp-read" hidden></p>
        <button type="button" id="cspClose" class="csp-close">Cerrar</button>
    </div>
    <div class="csp-bar csp-bar-bottom">
        <button type="button" id="cspShutter" class="csp-shutter" hidden>Tomar foto del paquete</button>
    </div>
</div>

<style>
.csp-overlay {
    position: fixed; inset: 0; z-index: 80;
    background: #000; display: flex; flex-direction: column;
}
.csp-overlay[hidden] { display: none !important; }
.csp-video {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover; background: #000;
}
.csp-frame {
    position: absolute; left: 10%; right: 10%; top: 28%; bottom: 32%;
    border: 2px solid rgba(255,255,255,0.85); border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.35); pointer-events: none;
}
.csp-overlay.is-photo .csp-frame { border-color: #2BB673; }
.csp-bar {
    position: relative; z-index: 2; padding: 14px 16px;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.csp-bar-top { padding-top: max(14px, env(safe-area-inset-top)); }
.csp-bar-bottom { margin-top: auto; padding-bottom: max(20px, env(safe-area-inset-bottom)); }
.csp-hint {
    margin: 0; color: #fff; font-weight: 700; font-size: 0.95rem;
    text-align: center; text-shadow: 0 1px 4px rgba(0,0,0,0.6);
}
.csp-read {
    margin: 0; color: #dbeafe; font-family: ui-monospace, monospace;
    font-weight: 700; font-size: 0.95rem; letter-spacing: 0.04em;
    text-align: center; word-break: break-all;
}
.csp-close {
    position: absolute; right: 12px; top: max(12px, env(safe-area-inset-top));
    background: rgba(15,23,42,0.7); color: #fff; border: 1px solid rgba(255,255,255,0.3);
    border-radius: 999px; padding: 0.4rem 0.85rem; font-weight: 600; cursor: pointer;
}
.csp-shutter {
    min-width: 220px; padding: 0.85rem 1.4rem; border: none; border-radius: 999px;
    background: #fff; color: #0A2D6F; font-weight: 800; font-size: 1rem; cursor: pointer;
}
.csp-shutter:disabled { opacity: 0.55; cursor: wait; }
</style>

<script>
window.skylinkOpenScanPhotoCamera = function (options) {
    options = options || {};
    var overlay = document.getElementById('cspOverlay');
    var video = document.getElementById('cspVideo');
    var work = document.getElementById('cspWork');
    var hint = document.getElementById('cspHint');
    var readEl = document.getElementById('cspRead');
    var btnClose = document.getElementById('cspClose');
    var btnShutter = document.getElementById('cspShutter');
    if (!overlay || !video) return Promise.reject(new Error('Visor no disponible'));
    if (!overlay.hidden) return Promise.resolve();

    window.__cspSession = (window.__cspSession || 0) + 1;
    var session = window.__cspSession;

    var stream = null;
    var timer = null;
    var zxingTimer = null;
    var detector = null;
    var zxingReader = null;
    var scanning = true;
    var last = '';
    var hits = 0;
    var closed = false;

    function setHint(text) {
        if (hint) hint.textContent = text;
    }

    function isStale() {
        return closed || session !== window.__cspSession;
    }

    function stopTracks() {
        if (timer) { clearInterval(timer); timer = null; }
        if (zxingTimer) { clearInterval(zxingTimer); zxingTimer = null; }
        scanning = false;
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video.srcObject) video.srcObject = null;
    }

    function close() {
        if (closed) return;
        closed = true;
        if (session === window.__cspSession) window.__cspSession += 1;
        stopTracks();
        overlay.hidden = true;
        overlay.classList.remove('is-photo');
        if (btnShutter) btnShutter.hidden = true;
        if (readEl) readEl.hidden = true;
        document.body.style.overflow = '';
    }

    function normalize(raw) {
        return String(raw || '').replace(/\s+/g, ' ').trim().toUpperCase();
    }

    function isPlausible(code) {
        if (!code || code.length < 8) return false;
        if (/^https?:\/\//i.test(code)) return false;
        return /[A-Z0-9]/.test(code);
    }

    function prefer(code) {
        return /^(1Z|TBA|1LS|JD|96|92|420)/.test(code) || code.length >= 12;
    }

    function onFound(code) {
        if (!scanning || isStale()) return;
        scanning = false;
        if (timer) { clearInterval(timer); timer = null; }
        if (zxingTimer) { clearInterval(zxingTimer); zxingTimer = null; }
        var field = options.trackingInput || document.getElementById('tracking_external');
        if (field) {
            field.value = code;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (readEl) {
            readEl.textContent = code;
            readEl.hidden = false;
        }
        overlay.classList.add('is-photo');
        setHint('Código leído. Ahora tome la foto del paquete');
        if (btnShutter) btnShutter.hidden = false;
        try { if (navigator.vibrate) navigator.vibrate(80); } catch (e) {}
    }

    function consider(raw) {
        var code = normalize(raw);
        if (!isPlausible(code)) return;
        if (!prefer(code) && code.length < 10) return;
        if (code === last) {
            hits += 1;
        } else {
            last = code;
            hits = 1;
        }
        if (hits >= 2) onFound(code);
    }

    function tickNative() {
        if (!scanning || isStale() || !detector || !video.videoWidth) return;
        detector.detect(video).then(function (codes) {
            if (!codes || !codes.length) return;
            var best = codes.slice().sort(function (a, b) {
                return String(b.rawValue || '').length - String(a.rawValue || '').length;
            })[0];
            if (best && best.rawValue) consider(best.rawValue);
        }).catch(function () {});
    }

    function decodeZxingCanvas() {
        if (zxingReader && typeof zxingReader.decodeFromCanvas === 'function') {
            return zxingReader.decodeFromCanvas(work);
        }
        if (zxingReader && typeof zxingReader.decode === 'function') {
            return zxingReader.decode(work);
        }
        var src = new window.ZXing.HTMLCanvasElementLuminanceSource(work);
        var bitmap = new window.ZXing.BinaryBitmap(new window.ZXing.HybridBinarizer(src));
        return new window.ZXing.MultiFormatReader().decode(bitmap);
    }

    function tickZxing() {
        if (!scanning || isStale() || !video.videoWidth) return;
        var ctx = work.getContext('2d');
        if (!ctx) return;
        var maxW = 640;
        var scale = Math.min(1, maxW / video.videoWidth);
        work.width = Math.max(1, Math.round(video.videoWidth * scale));
        work.height = Math.max(1, Math.round(video.videoHeight * scale));
        ctx.drawImage(video, 0, 0, work.width, work.height);
        try {
            var result = decodeZxingCanvas();
            if (result && result.getText) consider(result.getText());
        } catch (e) {}
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            var s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function loadZxing() {
        if (window.ZXing) return Promise.resolve();
        return loadScript('https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js').catch(function () {
            return loadScript('https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js');
        });
    }

    function startZxingLoop() {
        if (zxingReader || isStale()) return Promise.resolve();
        return loadZxing().then(function () {
            if (isStale() || zxingReader) return;
            zxingReader = new window.ZXing.BrowserMultiFormatReader();
            zxingTimer = setInterval(tickZxing, 180);
        });
    }

    function startLoop() {
        var formats = ['code_128', 'code_39', 'code_93', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'qr_code', 'data_matrix', 'pdf417', 'aztec'];
        if (window.BarcodeDetector) {
            try {
                detector = new BarcodeDetector({ formats: formats });
                timer = setInterval(tickNative, 140);
                setTimeout(function () {
                    if (scanning && !isStale() && hits < 2) startZxingLoop();
                }, 3500);
                return Promise.resolve();
            } catch (e) {
                detector = null;
            }
        }
        return startZxingLoop();
    }

    function captureFrame() {
        var w = video.videoWidth;
        var h = video.videoHeight;
        if (!w || !h) return Promise.reject(new Error('La cámara aún no está lista'));
        var canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, w, h);
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) { reject(new Error('No se pudo capturar la foto')); return; }
                resolve(new File([blob], 'paquete.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.86);
        });
    }

    overlay.hidden = false;
    overlay.classList.remove('is-photo');
    setHint('Apunte el código de barras del tracking');
    if (readEl) { readEl.hidden = true; readEl.textContent = ''; }
    if (btnShutter) { btnShutter.hidden = true; btnShutter.disabled = false; }
    document.body.style.overflow = 'hidden';

    btnClose.onclick = function () { close(); };
    btnShutter.onclick = async function () {
        if (scanning) return;
        btnShutter.disabled = true;
        try {
            var file = await captureFrame();
            if (window.skylinkCompressImage) {
                try { file = await window.skylinkCompressImage(file); } catch (e) {}
            }
            close();
            if (typeof options.onPhoto === 'function') options.onPhoto(file);
        } catch (err) {
            btnShutter.disabled = false;
            alert(err.message || 'No se pudo tomar la foto');
        }
    };

    return navigator.mediaDevices.getUserMedia({
        audio: false,
        video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1280 },
            height: { ideal: 720 },
        },
    }).then(function (media) {
        if (isStale()) {
            media.getTracks().forEach(function (t) { t.stop(); });
            return;
        }
        stream = media;
        video.srcObject = stream;
        return video.play().then(startLoop);
    }).catch(function (err) {
        close();
        throw err;
    });
};
</script>
