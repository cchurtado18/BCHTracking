{{-- Visor: lee el tracking en vivo; el obturador aparece cuando lo encuentra. --}}
<div id="cspOverlay" class="csp-overlay" hidden data-csp-build="4">
    <div id="cspReader" class="csp-reader"></div>
    <video id="cspVideo" class="csp-video" autoplay muted playsinline webkit-playsinline hidden></video>
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
.csp-reader {
    position: absolute; inset: 0; overflow: hidden; background: #000;
}
.csp-reader video,
.csp-reader img,
.csp-reader canvas {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
.csp-video {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover; background: #000;
}
.csp-video[hidden] { display: none !important; }
.csp-frame {
    position: absolute; left: 6%; right: 6%; top: 24%; bottom: 32%;
    border: 2px solid rgba(255,255,255,0.9); border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.32); pointer-events: none;
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
            var existing = document.querySelector('script[src="' + src + '"]');
            if (existing) {
                if (window.Html5Qrcode || (window.__Html5QrcodeLibrary__ && window.__Html5QrcodeLibrary__.Html5Qrcode) || window.ZXing) {
                    resolve();
                    return;
                }
                existing.addEventListener('load', resolve);
                existing.addEventListener('error', reject);
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    window.skylinkHtml5QrcodeClass = function () {
        if (window.Html5Qrcode) return window.Html5Qrcode;
        if (window.__Html5QrcodeLibrary__ && window.__Html5QrcodeLibrary__.Html5Qrcode) {
            return window.__Html5QrcodeLibrary__.Html5Qrcode;
        }
        return null;
    };

    window.skylinkLoadHtml5Qrcode = function () {
        if (window.skylinkHtml5QrcodeClass()) return Promise.resolve();
        return loadScript(@json(asset('vendor/html5-qrcode.min.js')));
    };
    window.skylinkLoadHtml5Qrcode().catch(function () {});
})();

window.skylinkOpenScanPhotoCamera = function (options) {
    options = options || {};
    var overlay = document.getElementById('cspOverlay');
    var readerHost = document.getElementById('cspReader');
    var video = document.getElementById('cspVideo');
    var hint = document.getElementById('cspHint');
    var readEl = document.getElementById('cspRead');
    var btnClose = document.getElementById('cspClose');
    var btnShutter = document.getElementById('cspShutter');
    var btnManual = document.getElementById('cspManualBtn');
    var manualWrap = document.getElementById('cspManualWrap');
    var manualInput = document.getElementById('cspManualInput');
    var manualOk = document.getElementById('cspManualOk');
    if (!overlay || !readerHost) return Promise.reject(new Error('Visor no disponible'));
    if (!overlay.hidden) return Promise.resolve();

    window.__cspSession = (window.__cspSession || 0) + 1;
    var session = window.__cspSession;
    var html5Scanner = null;
    var stream = null;
    var timer = null;
    var scanning = true;
    var closed = false;
    var last = '';
    var hits = 0;

    function setHint(text) {
        if (hint) hint.textContent = text;
    }
    function isStale() {
        return closed || session !== window.__cspSession;
    }
    function liveVideo() {
        return overlay.querySelector('video');
    }

    function stopAll() {
        scanning = false;
        if (timer) { clearInterval(timer); timer = null; }
        if (html5Scanner) {
            try { html5Scanner.stop().catch(function () {}); } catch (e) {}
            html5Scanner = null;
        }
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
        readerHost.innerHTML = '';
    }

    function close() {
        if (closed) return;
        closed = true;
        if (session === window.__cspSession) window.__cspSession += 1;
        stopAll();
        overlay.hidden = true;
        overlay.classList.remove('is-photo');
        if (btnShutter) btnShutter.hidden = true;
        if (readEl) readEl.hidden = true;
        if (manualWrap) manualWrap.hidden = true;
        if (btnManual) btnManual.hidden = false;
        if (video) video.hidden = true;
        document.body.style.overflow = '';
    }

    function normalize(raw) {
        return String(raw || '').replace(/\s+/g, '').trim().toUpperCase();
    }
    function isUrl(code) {
        return /^HTTPS?:\/\//.test(code) || /^WWW\./.test(code);
    }
    function isPlausible(code) {
        if (!code || code.length < 6) return false;
        if (isUrl(code)) return false;
        return /[A-Z0-9]/.test(code);
    }

    function onFound(code) {
        if (!scanning || isStale()) return;
        scanning = false;
        if (timer) { clearInterval(timer); timer = null; }
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
        if (code === last) hits += 1;
        else { last = code; hits = 1; }
        if (hits >= 1) {
            onFound(code);
            return true;
        }
        return false;
    }

    function captureFrame() {
        var el = liveVideo();
        if (!el || !el.videoWidth) return Promise.reject(new Error('La cámara aún no está lista'));
        var canvas = document.createElement('canvas');
        canvas.width = el.videoWidth;
        canvas.height = el.videoHeight;
        var ctx = canvas.getContext('2d');
        ctx.drawImage(el, 0, 0, canvas.width, canvas.height);
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) { reject(new Error('No se pudo capturar la foto')); return; }
                resolve(new File([blob], 'paquete.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.86);
        });
    }

    function startNativeOn(videoEl) {
        if (!window.BarcodeDetector || !videoEl) return;
        var formats = ['code_128', 'code_39', 'code_93', 'codabar', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'qr_code', 'data_matrix', 'pdf417'];
        try {
            var detector = new BarcodeDetector({ formats: formats });
            timer = setInterval(function () {
                if (!scanning || isStale() || !videoEl.videoWidth) return;
                detector.detect(videoEl).then(function (codes) {
                    if (!codes) return;
                    for (var i = 0; i < codes.length; i++) {
                        if (consider(codes[i].rawValue)) return;
                    }
                }).catch(function () {});
            }, 120);
        } catch (e) {}
    }

    function startHtml5() {
        var Ctor = window.skylinkHtml5QrcodeClass();
        if (!Ctor) return Promise.reject(new Error('Lector no disponible'));
        var formats = undefined;
        var lib = window.__Html5QrcodeLibrary__ || window;
        if (lib.Html5QrcodeSupportedFormats) {
            var F = lib.Html5QrcodeSupportedFormats;
            formats = [F.CODE_128, F.CODE_39, F.CODE_93, F.CODABAR, F.ITF, F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.QR_CODE, F.DATA_MATRIX, F.PDF_417].filter(function (v) {
                return typeof v !== 'undefined';
            });
        }
        html5Scanner = new Ctor('cspReader', { verbose: false });
        var config = { fps: 12, aspectRatio: 1.333 };
        if (formats && formats.length) config.formatsToSupport = formats;
        setHint('Buscando el código de barras…');
        return html5Scanner.start(
            { facingMode: 'environment' },
            config,
            function (decodedText) { consider(decodedText); },
            function () {}
        ).then(function () {
            startNativeOn(liveVideo());
        });
    }

    function startZxingFallback() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('Sin cámara'));
        }
        if (video) video.hidden = false;
        setHint('Usando lector de respaldo…');
        return navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' } } }).then(function (media) {
            if (isStale()) {
                media.getTracks().forEach(function (t) { t.stop(); });
                return;
            }
            stream = media;
            video.srcObject = stream;
            return video.play();
        }).then(function () {
            startNativeOn(video);
            return new Promise(function (resolve, reject) {
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            }).then(function () {
                if (!window.ZXing || isStale()) return;
                var reader = new window.ZXing.BrowserMultiFormatReader();
                timer = setInterval(function () {
                    if (!scanning || isStale() || !video.videoWidth) return;
                    try {
                        var result = reader.decode(video);
                        var text = result && (typeof result.getText === 'function' ? result.getText() : result.text);
                        if (text) consider(text);
                    } catch (e) {}
                }, 160);
                setHint('Buscando el código de barras…');
            }).catch(function () {
                setHint('Buscando el código… si no lee, escríbalo abajo.');
            });
        });
    }

    overlay.hidden = false;
    overlay.classList.remove('is-photo');
    setHint('Abriendo cámara…');
    if (readEl) { readEl.hidden = true; readEl.textContent = ''; }
    if (btnShutter) { btnShutter.hidden = true; btnShutter.disabled = false; }
    if (btnManual) btnManual.hidden = false;
    if (manualWrap) manualWrap.hidden = true;
    if (manualInput) manualInput.value = '';
    if (video) video.hidden = true;
    readerHost.innerHTML = '';
    document.body.style.overflow = 'hidden';

    btnClose.onclick = function () { close(); };
    if (btnManual) {
        btnManual.onclick = function () {
            if (manualWrap) manualWrap.hidden = false;
            if (manualInput) manualInput.focus();
        };
    }
    if (manualOk) {
        manualOk.onclick = function () {
            var code = normalize(manualInput && manualInput.value);
            if (!isPlausible(code)) {
                alert('Escriba el tracking de la etiqueta.');
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

    return window.skylinkLoadHtml5Qrcode().then(startHtml5).catch(function () {
        setHint('Usando lector de respaldo…');
        return startZxingFallback();
    }).catch(function () {
        setHint('No se pudo iniciar el lector. Escriba el tracking.');
        if (manualWrap) manualWrap.hidden = false;
    });
};
</script>
