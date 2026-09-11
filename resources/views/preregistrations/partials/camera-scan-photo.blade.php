{{-- Visor: busca el tracking sin parar; el obturador solo aparece cuando lo lee. --}}
<div id="cspOverlay" class="csp-overlay" hidden>
    <video id="cspVideo" class="csp-video" autoplay muted playsinline webkit-playsinline></video>
    <canvas id="cspWork" class="csp-work" width="640" height="360"></canvas>
    <div class="csp-frame" aria-hidden="true"></div>
    <div class="csp-bar csp-bar-top">
        <p id="cspHint" class="csp-hint">Apunte el código de barras del tracking</p>
        <p id="cspRead" class="csp-read" hidden></p>
        <button type="button" id="cspClose" class="csp-close">Cerrar</button>
    </div>
    <div class="csp-bar csp-bar-bottom">
        <button type="button" id="cspShutter" class="csp-shutter" hidden>Tomar foto del paquete</button>
        <button type="button" id="cspManualBtn" class="csp-manual-btn">No lee el código — escribir tracking</button>
        <div id="cspManualWrap" class="csp-manual-wrap" hidden>
            <input type="text" id="cspManualInput" class="csp-manual-input" autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="Tracking de la etiqueta">
            <button type="button" id="cspManualOk" class="csp-shutter">Usar este tracking</button>
        </div>
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
.csp-work {
    position: absolute; left: -9999px; top: 0; width: 640px; height: 360px;
    opacity: 0; pointer-events: none;
}
.csp-frame {
    position: absolute; left: 8%; right: 8%; top: 26%; bottom: 34%;
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
    max-width: 22rem;
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
.csp-manual-btn {
    background: transparent; color: #e2e8f0; border: 1px solid rgba(255,255,255,0.35);
    border-radius: 999px; padding: 0.45rem 0.9rem; font-weight: 600; cursor: pointer;
}
.csp-manual-wrap { width: min(22rem, 92vw); display: flex; flex-direction: column; gap: 8px; align-items: center; }
.csp-manual-wrap[hidden] { display: none !important; }
.csp-manual-input {
    width: 100%; padding: 0.7rem 0.85rem; border-radius: 0.6rem; border: 1px solid #94a3b8;
    font-size: 1rem; font-weight: 700; text-transform: uppercase; text-align: center;
}
</style>

<script>
(function () {
    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            if (document.querySelector('script[data-csp-zxing="1"][src="' + src + '"]')) {
                var wait = function () { window.ZXing ? resolve() : setTimeout(wait, 40); };
                wait();
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.setAttribute('data-csp-zxing', '1');
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    window.skylinkLoadZxing = function () {
        if (window.ZXing) return Promise.resolve();
        return loadScript('https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js').catch(function () {
            return loadScript('https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js');
        });
    };
    window.skylinkLoadZxing().catch(function () {});
})();

window.skylinkOpenScanPhotoCamera = function (options) {
    options = options || {};
    var overlay = document.getElementById('cspOverlay');
    var video = document.getElementById('cspVideo');
    var work = document.getElementById('cspWork');
    var hint = document.getElementById('cspHint');
    var readEl = document.getElementById('cspRead');
    var btnClose = document.getElementById('cspClose');
    var btnShutter = document.getElementById('cspShutter');
    var btnManual = document.getElementById('cspManualBtn');
    var manualWrap = document.getElementById('cspManualWrap');
    var manualInput = document.getElementById('cspManualInput');
    var manualOk = document.getElementById('cspManualOk');
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
        try { if (zxingReader && typeof zxingReader.stopContinuousDecode === 'function') zxingReader.stopContinuousDecode(); } catch (e) {}
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
        if (manualWrap) manualWrap.hidden = true;
        if (btnManual) btnManual.hidden = false;
        document.body.style.overflow = '';
    }

    function normalize(raw) {
        return String(raw || '').replace(/\s+/g, '').trim().toUpperCase();
    }

    function isUrl(code) {
        return /^HTTPS?:\/\//.test(code) || /^WWW\./.test(code);
    }

    function isPlausible(code) {
        if (!code || code.length < 8) return false;
        if (isUrl(code)) return false;
        if (!/[A-Z0-9]/.test(code)) return false;
        return /^[A-Z0-9\-_./]+$/.test(code);
    }

    function prefer(code) {
        return /^(1Z|TBA|1LS|JD|94|93|92|91|96|420|GM)/.test(code) || code.length >= 12;
    }

    function zxingText(result) {
        if (!result) return '';
        if (typeof result.getText === 'function') return result.getText();
        return result.text || '';
    }

    function onFound(code) {
        if (!scanning || isStale()) return;
        scanning = false;
        if (timer) { clearInterval(timer); timer = null; }
        if (zxingTimer) { clearInterval(zxingTimer); zxingTimer = null; }
        try { if (zxingReader && typeof zxingReader.stopContinuousDecode === 'function') zxingReader.stopContinuousDecode(); } catch (e) {}
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
        if (btnManual) btnManual.hidden = true;
        if (manualWrap) manualWrap.hidden = true;
        try { if (navigator.vibrate) navigator.vibrate(80); } catch (e) {}
    }

    function consider(raw) {
        var code = normalize(raw);
        if (!isPlausible(code)) return false;
        if (!prefer(code) && code.length < 10) return false;
        if (code === last) {
            hits += 1;
        } else {
            last = code;
            hits = 1;
        }
        if (hits >= 1 && prefer(code)) {
            onFound(code);
            return true;
        }
        if (hits >= 2) {
            onFound(code);
            return true;
        }
        setHint('Leyendo ' + code + '…');
        return false;
    }

    function considerMany(values) {
        if (!values || !values.length) return;
        var ranked = [];
        for (var i = 0; i < values.length; i++) {
            var code = normalize(values[i]);
            if (!isPlausible(code)) continue;
            ranked.push(code);
        }
        ranked.sort(function (a, b) {
            var pa = prefer(a) ? 1 : 0;
            var pb = prefer(b) ? 1 : 0;
            if (pa !== pb) return pb - pa;
            return b.length - a.length;
        });
        for (var j = 0; j < ranked.length; j++) {
            if (consider(ranked[j])) return;
        }
    }

    function tickNative() {
        if (!scanning || isStale() || !detector) return;
        var source = video.videoWidth ? video : null;
        if (!source) return;
        detector.detect(source).then(function (codes) {
            if (!codes || !codes.length) return;
            considerMany(codes.map(function (c) { return c.rawValue; }));
        }).catch(function () {});
    }

    function drawWorkFrame() {
        if (!work || !video.videoWidth) return null;
        var ctx = work.getContext('2d', { willReadFrequently: true });
        if (!ctx) return null;
        var vw = video.videoWidth;
        var vh = video.videoHeight;
        var sx = Math.round(vw * 0.08);
        var sy = Math.round(vh * 0.26);
        var sw = Math.max(1, Math.round(vw * 0.84));
        var sh = Math.max(1, Math.round(vh * 0.40));
        var maxW = 800;
        var scale = Math.min(1, maxW / sw);
        work.width = Math.max(1, Math.round(sw * scale));
        work.height = Math.max(1, Math.round(sh * scale));
        ctx.drawImage(video, sx, sy, sw, sh, 0, 0, work.width, work.height);
        return work;
    }

    function tickZxing() {
        if (!scanning || isStale() || !zxingReader || !video.videoWidth) return;
        var canvas = drawWorkFrame();
        if (!canvas) return;
        try {
            var result = null;
            if (typeof zxingReader.decodeFromCanvas === 'function') {
                result = zxingReader.decodeFromCanvas(canvas);
            } else if (typeof zxingReader.decode === 'function') {
                result = zxingReader.decode(canvas);
            }
            if (result && typeof result.then === 'function') {
                result.then(function (r) {
                    var text = zxingText(r);
                    if (text) consider(text);
                }).catch(function () {});
                return;
            }
            var text = zxingText(result);
            if (text) consider(text);
        } catch (e) {}
    }

    function makeZxingReader() {
        var hints;
        try {
            hints = new Map();
            if (window.ZXing.DecodeHintType && window.ZXing.BarcodeFormat) {
                hints.set(window.ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    window.ZXing.BarcodeFormat.CODE_128,
                    window.ZXing.BarcodeFormat.CODE_39,
                    window.ZXing.BarcodeFormat.CODE_93,
                    window.ZXing.BarcodeFormat.ITF,
                    window.ZXing.BarcodeFormat.CODABAR,
                    window.ZXing.BarcodeFormat.EAN_13,
                    window.ZXing.BarcodeFormat.EAN_8,
                    window.ZXing.BarcodeFormat.UPC_A,
                    window.ZXing.BarcodeFormat.UPC_E,
                    window.ZXing.BarcodeFormat.QR_CODE,
                    window.ZXing.BarcodeFormat.DATA_MATRIX,
                    window.ZXing.BarcodeFormat.PDF_417,
                ]);
                hints.set(window.ZXing.DecodeHintType.TRY_HARDER, true);
            }
        } catch (e) {
            hints = undefined;
        }
        if (hints) return new window.ZXing.BrowserMultiFormatReader(hints, 120);
        return new window.ZXing.BrowserMultiFormatReader();
    }

    function startZxingLoop() {
        if (zxingReader || isStale()) return Promise.resolve();
        return window.skylinkLoadZxing().then(function () {
            if (isStale() || zxingReader || !window.ZXing) return;
            zxingReader = makeZxingReader();
            if (typeof zxingReader.timeBetweenDecodingAttempts === 'number') {
                zxingReader.timeBetweenDecodingAttempts = 80;
            }
            zxingTimer = setInterval(tickZxing, 140);
            setHint('Buscando el código de barras…');
        }).catch(function () {
            if (!isStale()) setHint('No se pudo cargar el lector. Escriba el tracking o acerque el código.');
        });
    }

    function startNativeLoop() {
        if (!window.BarcodeDetector) return;
        var formats = ['code_128', 'code_39', 'code_93', 'codabar', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'qr_code', 'data_matrix', 'pdf417'];
        var use = formats;
        if (typeof BarcodeDetector.getSupportedFormats === 'function') {
            BarcodeDetector.getSupportedFormats().then(function (supported) {
                if (isStale() || detector) return;
                var ok = formats.filter(function (f) { return supported.indexOf(f) !== -1; });
                if (!ok.length) ok = formats;
                try {
                    detector = new BarcodeDetector({ formats: ok });
                    timer = setInterval(tickNative, 120);
                } catch (e) {}
            }).catch(function () {
                try {
                    detector = new BarcodeDetector({ formats: use });
                    timer = setInterval(tickNative, 120);
                } catch (e) {}
            });
            return;
        }
        try {
            detector = new BarcodeDetector({ formats: use });
            timer = setInterval(tickNative, 120);
        } catch (e) {}
    }

    function waitForVideo() {
        if (video.videoWidth) return Promise.resolve();
        return new Promise(function (resolve) {
            var done = function () { video.removeEventListener('loadedmetadata', done); resolve(); };
            video.addEventListener('loadedmetadata', done);
            setTimeout(done, 1200);
        });
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

    function openCamera() {
        var tries = [
            { audio: false, video: { facingMode: { ideal: 'environment' } } },
            { audio: false, video: { facingMode: 'environment' } },
            { audio: false, video: true },
        ];
        var i = 0;
        function next() {
            return navigator.mediaDevices.getUserMedia(tries[i]).catch(function (err) {
                i += 1;
                if (i < tries.length) return next();
                throw err;
            });
        }
        return next();
    }

    overlay.hidden = false;
    overlay.classList.remove('is-photo');
    setHint('Apunte el código de barras del tracking');
    if (readEl) { readEl.hidden = true; readEl.textContent = ''; }
    if (btnShutter) { btnShutter.hidden = true; btnShutter.disabled = false; }
    if (btnManual) btnManual.hidden = false;
    if (manualWrap) manualWrap.hidden = true;
    if (manualInput) manualInput.value = '';
    document.body.style.overflow = 'hidden';

    btnClose.onclick = function () { close(); };
    if (btnManual) {
        btnManual.onclick = function () {
            if (manualWrap) manualWrap.hidden = false;
            if (manualInput) {
                manualInput.focus();
                if (typeof manualInput.select === 'function') manualInput.select();
            }
        };
    }
    if (manualOk) {
        manualOk.onclick = function () {
            var code = normalize(manualInput && manualInput.value);
            if (!isPlausible(code)) {
                alert('Escriba el tracking de la etiqueta (mínimo 8 caracteres).');
                return;
            }
            onFound(code);
        };
    }
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

    return openCamera().then(function (media) {
        if (isStale()) {
            media.getTracks().forEach(function (t) { t.stop(); });
            return;
        }
        stream = media;
        video.srcObject = stream;
        return video.play().then(waitForVideo).then(function () {
            startNativeLoop();
            return startZxingLoop();
        });
    }).catch(function (err) {
        close();
        throw err;
    });
};
</script>
