{{-- Visor: la cámara propia se ve siempre; el lector solo decodifica frames. --}}
<div id="cspOverlay" class="csp-overlay" hidden data-csp-build="10">
    <video id="cspVideo" class="csp-video" autoplay muted playsinline webkit-playsinline></video>
    <div id="cspReader" class="csp-reader" aria-hidden="true"></div>
    <div class="csp-frame" aria-hidden="true"></div>
    <div class="csp-bar csp-bar-top">
        <p id="cspHint" class="csp-hint">Apunte el código de barras del tracking</p>
        <p id="cspRead" class="csp-read" hidden></p>
        <button type="button" id="cspClose" class="csp-close">Cerrar</button>
    </div>
    <div class="csp-bar csp-bar-bottom">
        <div id="cspConfirmRow" class="csp-confirm-row" hidden>
            <button type="button" id="cspReject" class="csp-manual-btn">No es este — seguir buscando</button>
        </div>
        <button type="button" id="cspShutter" class="csp-shutter" hidden>Tomar foto del paquete</button>
        <button type="button" id="cspManualBtn" class="csp-manual-btn">No lee el código — escribir tracking</button>
        <div id="cspManualWrap" class="csp-manual-wrap" hidden>
            <input type="text" id="cspManualInput" class="csp-manual-input" autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="Tracking de la etiqueta">
            <button type="button" id="cspManualOk" class="csp-shutter">Aceptar tracking</button>
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
    object-fit: cover; background: #111; z-index: 1;
}
.csp-reader {
    position: absolute; width: 1px; height: 1px; left: -9999px; overflow: hidden;
}
.csp-frame {
    position: absolute; left: 5%; right: 5%; top: 36%; bottom: 38%;
    border: 2px solid rgba(255,255,255,0.9); border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,0.32); pointer-events: none; z-index: 2;
}
.csp-overlay.is-photo .csp-frame {
    left: 4%; right: 4%; top: 12%; bottom: 22%;
    border-color: #2BB673;
}
.csp-bar {
    position: relative; z-index: 3; padding: 14px 16px;
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
.csp-confirm-row { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; align-items: center; }
.csp-confirm-row[hidden] { display: none !important; }
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
                if (window.Html5Qrcode || (window.__Html5QrcodeLibrary__ && window.__Html5QrcodeLibrary__.Html5Qrcode)) {
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
    var confirmRow = document.getElementById('cspConfirmRow');
    var btnReject = document.getElementById('cspReject');
    if (!overlay || !video) return Promise.reject(new Error('Visor no disponible'));
    if (!overlay.hidden) return Promise.resolve();

    var trackingField = options.trackingInput || document.getElementById('tracking_external');
    var existingTracking = trackingField ? String(trackingField.value || '').replace(/\s+/g, '').trim() : '';
    var skipScan = !!options.skipScan || existingTracking.length >= 6;

    window.__cspSession = (window.__cspSession || 0) + 1;
    var session = window.__cspSession;
    var html5Scanner = null;
    var stream = null;
    var timer = null;
    var scanTimer = null;
    var scanning = !skipScan;
    var photoMode = skipScan;
    var closed = false;
    var scanBusy = false;
    var scanCanvas = document.createElement('canvas');
    var scanCtx = scanCanvas.getContext('2d', { willReadFrequently: true }) || scanCanvas.getContext('2d');

    function setHint(text) {
        if (hint) hint.textContent = text;
    }
    function isStale() {
        return closed || session !== window.__cspSession;
    }
    function normalize(raw) {
        return String(raw || '')
            .replace(/[\s\u0000\u001d\u001e]/g, '')
            .replace(/^\][A-Z0-9]/, '')
            .trim()
            .toUpperCase();
    }
    function isUrl(code) {
        return /^HTTPS?:\/\//.test(code) || /^WWW\./.test(code);
    }
    function isPlausible(code) {
        if (!code || code.length < 6 || code.length > 48) return false;
        if (isUrl(code)) return false;
        return /[A-Z0-9]/.test(code);
    }
    function scoreCode(code) {
        if (!isPlausible(code)) return -1;
        var score = Math.min(code.length, 28);
        if (/[A-Z]/.test(code) && /\d/.test(code)) score += 24;
        if (/^\d+$/.test(code) && code.length < 12) score -= 12;
        if (code.length >= 10 && code.length <= 34) score += 8;
        return score;
    }
    function pickBest(values) {
        var best = '';
        var bestScore = 0;
        for (var i = 0; i < (values || []).length; i++) {
            var code = normalize(values[i]);
            var score = scoreCode(code);
            if (score > bestScore) {
                bestScore = score;
                best = code;
            }
        }
        return best;
    }
    function textFromDecode(res) {
        if (!res) return '';
        if (typeof res === 'string') return res;
        return res.text || res.decodedText || '';
    }
    function playVideo() {
        var played = video.play();
        if (played && typeof played.then === 'function') return played.catch(function () {});
        return Promise.resolve();
    }
    function waitForVideo() {
        if (video.videoWidth) return Promise.resolve();
        return new Promise(function (resolve, reject) {
            var tries = 0;
            var id = setInterval(function () {
                if (isStale()) {
                    clearInterval(id);
                    reject(new Error('closed'));
                    return;
                }
                tries += 1;
                if (video.videoWidth) {
                    clearInterval(id);
                    resolve();
                } else if (tries > 40) {
                    clearInterval(id);
                    reject(new Error('La cámara no envió imagen'));
                }
            }, 80);
        });
    }

    function pauseScanner() {
        scanning = false;
        if (timer) { clearInterval(timer); timer = null; }
        if (scanTimer) { clearTimeout(scanTimer); scanTimer = null; }
    }

    function resumeScanner() {
        scanning = true;
        photoMode = false;
        scanBusy = false;
        if (trackingField) {
            trackingField.value = '';
            trackingField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        overlay.classList.remove('is-photo');
        if (confirmRow) confirmRow.hidden = true;
        if (btnShutter) btnShutter.hidden = true;
        if (btnManual) btnManual.hidden = false;
        if (readEl) { readEl.hidden = true; readEl.textContent = ''; }
        setHint('Apunte el código de barras del tracking');
        startNativeOn(video);
        startHtml5Loop();
    }

    function enterPhotoMode() {
        photoMode = true;
        scanning = false;
        pauseScanner();
        overlay.classList.add('is-photo');
        if (confirmRow) confirmRow.hidden = skipScan;
        if (btnManual) btnManual.hidden = true;
        if (manualWrap) manualWrap.hidden = true;
        if (btnShutter) {
            btnShutter.hidden = false;
            btnShutter.disabled = false;
            btnShutter.textContent = 'Tomar foto del paquete';
        }
        setHint('Tome la foto del paquete');
    }

    function applyTracking(code) {
        if (trackingField) {
            trackingField.value = code;
            trackingField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        if (readEl) {
            readEl.textContent = code;
            readEl.hidden = false;
        }
        try { if (navigator.vibrate) navigator.vibrate(80); } catch (e) {}
        enterPhotoMode();
    }

    function consider(raw) {
        var code = typeof raw === 'string' ? normalize(raw) : pickBest(raw);
        if (scoreCode(code) < 8) return false;
        if (!scanning || isStale() || photoMode) return false;
        applyTracking(code);
        return true;
    }

    function stopAll() {
        pauseScanner();
        html5Scanner = null;
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
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
        if (confirmRow) confirmRow.hidden = true;
        if (btnManual) btnManual.hidden = false;
        document.body.style.overflow = '';
    }

    function captureFrame() {
        if (!video || !video.videoWidth) return Promise.reject(new Error('La cámara aún no está lista'));
        var canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) { reject(new Error('No se pudo capturar la foto')); return; }
                resolve(new File([blob], 'paquete.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.9);
        });
    }

    function startNativeOn(videoEl) {
        if (!window.BarcodeDetector || !videoEl || timer) return;
        var formatSets = [
            ['code_128', 'code_39', 'code_93', 'itf'],
            ['code_128', 'code_39'],
            ['code_128']
        ];
        var detector = null;
        for (var i = 0; i < formatSets.length && !detector; i++) {
            try { detector = new BarcodeDetector({ formats: formatSets[i] }); } catch (e) {}
        }
        if (!detector) {
            try { detector = new BarcodeDetector(); } catch (e) { return; }
        }
        var busy = false;
        timer = setInterval(function () {
            if (!scanning || isStale() || busy || !videoEl.videoWidth) return;
            busy = true;
            detector.detect(videoEl).then(function (codes) {
                if (!codes || !codes.length) return;
                var values = [];
                for (var i = 0; i < codes.length; i++) values.push(codes[i].rawValue);
                consider(values);
            }).catch(function () {}).finally(function () { busy = false; });
        }, 80);
    }

    function decodeCanvas() {
        if (!html5Scanner || !html5Scanner.qrcode) return Promise.reject();
        var decoder = html5Scanner.qrcode;
        var decodeFn = decoder.decodeRobustlyAsync || decoder.decodeAsync;
        if (typeof decodeFn !== 'function') return Promise.reject();
        return decodeFn.call(decoder, scanCanvas).then(textFromDecode);
    }

    function scheduleTick(delay) {
        if (scanTimer) return;
        scanTimer = setTimeout(function () {
            scanTimer = null;
            tickScan();
        }, delay);
    }

    function tickScan() {
        if (!scanning || isStale() || photoMode) return;
        if (scanBusy || !video.videoWidth || !scanCtx) {
            scheduleTick(160);
            return;
        }
        scanBusy = true;
        try {
            var vw = video.videoWidth;
            var vh = video.videoHeight;
            var scale = Math.min(1, 1600 / Math.max(vw, vh));
            scanCanvas.width = Math.max(1, Math.floor(vw * scale));
            scanCanvas.height = Math.max(1, Math.floor(vh * scale));
            scanCtx.imageSmoothingEnabled = false;
            scanCtx.drawImage(video, 0, 0, scanCanvas.width, scanCanvas.height);
        } catch (e) {
            scanBusy = false;
            scheduleTick(160);
            return;
        }
        decodeCanvas().then(function (text) {
            consider(text);
        }).catch(function () {}).finally(function () {
            scanBusy = false;
            if (scanning && !isStale() && !photoMode) scheduleTick(120);
        });
    }

    function startHtml5Loop() {
        if (!html5Scanner) return;
        scheduleTick(180);
    }

    function attachStream(media) {
        if (isStale()) {
            media.getTracks().forEach(function (t) { t.stop(); });
            return Promise.resolve();
        }
        stream = media;
        video.srcObject = stream;
        return playVideo().then(waitForVideo);
    }

    function startLiveCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('Sin cámara'));
        }
        video.removeAttribute('hidden');
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.setAttribute('autoplay', '');
        video.muted = true;
        video.playsInline = true;
        var tries = [
            { audio: false, video: { facingMode: { exact: 'environment' } } },
            { audio: false, video: { facingMode: { ideal: 'environment' } } },
            { audio: false, video: true }
        ];
        function attempt(i) {
            return navigator.mediaDevices.getUserMedia(tries[i]).then(function (media) {
                return attachStream(media).catch(function (err) {
                    media.getTracks().forEach(function (t) { t.stop(); });
                    if (stream === media) {
                        stream = null;
                        video.srcObject = null;
                    }
                    throw err;
                });
            }).catch(function () {
                if (isStale()) return Promise.resolve();
                if (i + 1 < tries.length) return attempt(i + 1);
                return Promise.reject(new Error('Sin cámara'));
            });
        }
        return attempt(0);
    }

    overlay.hidden = false;
    overlay.classList.remove('is-photo');
    setHint('Abriendo cámara…');
    if (readEl) {
        if (skipScan && existingTracking) {
            readEl.textContent = existingTracking.toUpperCase();
            readEl.hidden = false;
        } else {
            readEl.hidden = true;
            readEl.textContent = '';
        }
    }
    if (btnShutter) { btnShutter.hidden = true; btnShutter.disabled = false; }
    if (btnManual) btnManual.hidden = skipScan;
    if (manualWrap) manualWrap.hidden = true;
    if (confirmRow) confirmRow.hidden = true;
    if (manualInput) manualInput.value = '';
    if (readerHost) readerHost.innerHTML = '';
    document.body.style.overflow = 'hidden';

    btnClose.onclick = function () { close(); };
    if (btnReject) {
        btnReject.onclick = function () { resumeScanner(); };
    }
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
            applyTracking(code);
        };
    }
    if (btnShutter) btnShutter.onclick = async function () {
        if (!photoMode) return;
        btnShutter.disabled = true;
        try {
            var file = await captureFrame();
            if (window.skylinkCompressImage) {
                try { file = await window.skylinkCompressImage(file); } catch (e) {}
            }
            var keepOpen = true;
            if (typeof options.onPhoto === 'function') {
                keepOpen = options.onPhoto(file) !== false;
            }
            if (!keepOpen) {
                close();
                return;
            }
            btnShutter.disabled = false;
            btnShutter.textContent = 'Tomar otra foto';
            setHint('Foto guardada. Puede tomar otra o cerrar.');
        } catch (err) {
            btnShutter.disabled = false;
            alert(err.message || 'No se pudo tomar la foto');
        }
    };

    return startLiveCamera().then(function () {
        if (isStale()) return;
        if (skipScan) {
            setHint('Tracking listo. Tome la foto del paquete');
            enterPhotoMode();
            return;
        }
        setHint('Buscando el código de barras…');
        startNativeOn(video);
        return window.skylinkLoadHtml5Qrcode().then(function () {
            var Ctor = window.skylinkHtml5QrcodeClass();
            if (!Ctor || !readerHost) return;
            var config = {
                verbose: false,
                experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            };
            var lib = window.__Html5QrcodeLibrary__ || window;
            if (lib.Html5QrcodeSupportedFormats) {
                var F = lib.Html5QrcodeSupportedFormats;
                config.formatsToSupport = [F.CODE_128, F.CODE_39, F.CODE_93, F.ITF, F.CODABAR].filter(function (v) {
                    return typeof v !== 'undefined';
                });
            }
            try {
                html5Scanner = new Ctor('cspReader', config);
            } catch (e) {
                try { html5Scanner = new Ctor('cspReader', { verbose: false }); } catch (e2) { html5Scanner = null; }
            }
            if (html5Scanner) startHtml5Loop();
        }).catch(function () {});
    }).catch(function () {
        if (isStale()) return;
        setHint('No se pudo abrir la cámara. Escriba el tracking.');
        if (manualWrap) manualWrap.hidden = false;
        if (btnManual) btnManual.hidden = true;
    });
};
</script>
