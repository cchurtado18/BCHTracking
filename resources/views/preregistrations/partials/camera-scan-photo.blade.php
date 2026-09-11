{{-- Visor: la cámara propia se ve siempre; el lector solo decodifica frames. --}}
<div id="cspOverlay" class="csp-overlay" hidden data-csp-build="14">
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
        <div id="cspManualWrap" class="csp-manual-wrap">
            <label for="cspManualInput" class="csp-field-label">Tracking (se llena al escanear)</label>
            <input type="text" id="cspManualInput" class="csp-manual-input" autocapitalize="characters" autocomplete="off" spellcheck="false" placeholder="Apunte el código o escríbalo aquí">
            <button type="button" id="cspManualOk" class="csp-shutter">Usar este tracking</button>
        </div>
        <button type="button" id="cspShutter" class="csp-shutter" hidden>Tomar foto del paquete</button>
        <button type="button" id="cspManualBtn" class="csp-manual-btn" hidden>No lee el código — escribir tracking</button>
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
    object-fit: cover; background: #000; z-index: 1;
}
.csp-reader {
    position: absolute; width: 1px; height: 1px; overflow: hidden;
    opacity: 0; pointer-events: none;
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
    font-weight: 700; font-size: 0.85rem; letter-spacing: 0.03em;
    text-align: center; white-space: nowrap; overflow-x: auto;
    max-width: 92vw; word-break: normal;
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
.csp-field-label {
    margin: 0; color: #e2e8f0; font-size: 0.8rem; font-weight: 700;
    text-align: center; text-shadow: 0 1px 4px rgba(0,0,0,0.6);
}
.csp-manual-wrap {
    width: min(22rem, 92vw); display: flex; flex-direction: column;
    gap: 8px; align-items: center;
}
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
    var skipScan = options.skipScan === true;

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
    var pendingMatch = '';
    var matchHits = 0;
    var cropCanvas = document.createElement('canvas');
    var cropCtx = cropCanvas.getContext('2d', { willReadFrequently: true }) || cropCanvas.getContext('2d');
    var fullCanvas = document.createElement('canvas');
    var fullCtx = fullCanvas.getContext('2d', { willReadFrequently: true }) || fullCanvas.getContext('2d');

    function setHint(text) {
        if (hint) hint.textContent = text;
    }
    function isStale() {
        return closed || session !== window.__cspSession;
    }
    function normalize(raw) {
        return String(raw || '')
            .replace(/[\s\u0000\u001d\u001e]/g, '')
            .replace(/^\][A-Z0-9]{1,3}/, '')
            .trim()
            .toUpperCase();
    }
    function isUrl(code) {
        return /^HTTPS?:\/\//.test(code) || /^WWW\./.test(code);
    }
    function isPlausible(code) {
        if (!code || code.length < 8 || code.length > 48) return false;
        if (isUrl(code)) return false;
        return /^[A-Z0-9]+$/.test(code);
    }
    function isCompleteTracking(code) {
        if (!isPlausible(code) || code.length < 12) return false;
        if (/[A-Z]/.test(code) && /\d/.test(code)) return true;
        return /^\d{12,22}$/.test(code);
    }
    function scoreCode(code) {
        if (!isPlausible(code)) return -1;
        var score = Math.min(code.length, 30);
        if (isCompleteTracking(code)) score += 40;
        if (/^[A-Z]{3,8}\d{8,}$/.test(code)) score += 24;
        if (/^\d+$/.test(code)) score -= 10;
        if (/^[A-Z]{3,8}$/.test(code)) score -= 20;
        if (code.length >= 12 && code.length <= 34) score += 8;
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
    function combineParts(values) {
        var codes = [];
        for (var i = 0; i < (values || []).length; i++) {
            var c = normalize(values[i]);
            if (c && codes.indexOf(c) === -1) codes.push(c);
        }
        var complete = [];
        for (var j = 0; j < codes.length; j++) {
            if (isCompleteTracking(codes[j])) complete.push(codes[j]);
        }
        if (complete.length) return pickBest(complete);

        var prefixes = [];
        var tails = [];
        for (var k = 0; k < codes.length; k++) {
            if (/^[A-Z]{3,8}$/.test(codes[k])) prefixes.push(codes[k]);
            else if (/\d/.test(codes[k]) && codes[k].length >= 8) tails.push(codes[k]);
        }
        var joined = [];
        for (var p = 0; p < prefixes.length; p++) {
            for (var t = 0; t < tails.length; t++) {
                if (tails[t].indexOf(prefixes[p]) === 0) joined.push(tails[t]);
                else joined.push(prefixes[p] + tails[t]);
            }
        }
        if (joined.length) return pickBest(joined);
        return pickBest(codes);
    }
    function textFromDecode(res) {
        if (!res) return '';
        if (typeof res === 'string') return res;
        return res.text || res.decodedText || '';
    }
    function playPreview(el) {
        if (!el || typeof el.play !== 'function') return Promise.resolve();
        el.setAttribute('playsinline', '');
        el.setAttribute('webkit-playsinline', '');
        el.muted = true;
        el.playsInline = true;
        var played = el.play();
        if (played && typeof played.then === 'function') return played.catch(function () {});
        return Promise.resolve();
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
        pendingMatch = '';
        matchHits = 0;
        if (trackingField) {
            trackingField.value = '';
            trackingField.dispatchEvent(new Event('input', { bubbles: true }));
        }
        overlay.classList.remove('is-photo');
        if (confirmRow) confirmRow.hidden = true;
        if (btnShutter) btnShutter.hidden = true;
        if (btnManual) btnManual.hidden = true;
        if (manualWrap) manualWrap.hidden = false;
        if (manualInput) {
            manualInput.value = '';
            manualInput.hidden = false;
        }
        if (readEl) { readEl.hidden = true; readEl.textContent = ''; }
        setHint('Apunte el código de barras del tracking');
        startScanLoop();
    }

    function enterPhotoMode() {
        photoMode = true;
        scanning = false;
        pauseScanner();
        overlay.classList.add('is-photo');
        if (confirmRow) confirmRow.hidden = skipScan;
        if (btnManual) btnManual.hidden = true;
        if (manualWrap) manualWrap.hidden = false;
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
        if (manualInput) manualInput.value = code;
        try { if (navigator.vibrate) navigator.vibrate(80); } catch (e) {}
        enterPhotoMode();
    }

    function consider(raw) {
        var code = typeof raw === 'string' ? combineParts([raw]) : combineParts(raw);
        if (!isCompleteTracking(code)) return false;
        if (!scanning || isStale() || photoMode) return false;
        if (code === pendingMatch) matchHits += 1;
        else {
            pendingMatch = code;
            matchHits = 1;
        }
        if (matchHits < 2) return false;
        pendingMatch = '';
        matchHits = 0;
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
        if (readerHost) readerHost.innerHTML = '';
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
        var ctx = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (!blob) { reject(new Error('No se pudo capturar la foto')); return; }
                resolve(new File([blob], 'paquete.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
            }, 'image/jpeg', 0.9);
        });
    }

    function supportedFormats() {
        var lib = window.__Html5QrcodeLibrary__ || window;
        if (!lib.Html5QrcodeSupportedFormats) return null;
        var F = lib.Html5QrcodeSupportedFormats;
        return [F.CODE_128, F.CODE_39, F.CODE_93, F.ITF, F.CODABAR].filter(function (v) {
            return typeof v !== 'undefined';
        });
    }

    function prepareDecoder() {
        var Ctor = window.skylinkHtml5QrcodeClass();
        if (!Ctor || !readerHost) return null;
        var formats = supportedFormats();
        var verbose = {
            verbose: false,
            useBarCodeDetectorIfSupported: false,
            experimentalFeatures: { useBarCodeDetectorIfSupported: false }
        };
        if (formats && formats.length) verbose.formatsToSupport = formats;
        try {
            return new Ctor('cspReader', verbose);
        } catch (e) {
            try { return new Ctor('cspReader', { verbose: false, useBarCodeDetectorIfSupported: false }); }
            catch (err) { return null; }
        }
    }

    function decodeCanvas(canvas) {
        if (!canvas || !canvas.width || !html5Scanner || !html5Scanner.qrcode) return Promise.resolve('');
        var decoder = html5Scanner.qrcode;
        var run = decoder.decodeRobustlyAsync
            ? decoder.decodeRobustlyAsync(canvas)
            : decoder.decodeAsync
                ? decoder.decodeAsync(canvas)
                : Promise.reject();
        return run.then(function (res) {
            return textFromDecode(res);
        }).catch(function () { return ''; });
    }

    function drawBand() {
        if (!video || !video.videoWidth || !cropCtx) return null;
        var vw = video.videoWidth;
        var vh = video.videoHeight;
        var cw = overlay.clientWidth || window.innerWidth;
        var ch = overlay.clientHeight || window.innerHeight;
        var scale = Math.max(cw / vw, ch / vh);
        var dw = vw * scale;
        var dh = vh * scale;
        var ox = (cw - dw) / 2;
        var oy = (ch - dh) / 2;
        var fx = cw * 0.03;
        var fy = ch * 0.30;
        var fw = cw * 0.94;
        var fh = ch * 0.40;
        var sx = (fx - ox) / scale;
        var sy = (fy - oy) / scale;
        var sw = fw / scale;
        var sh = fh / scale;
        if (sx < 0) { sw += sx; sx = 0; }
        if (sy < 0) { sh += sy; sy = 0; }
        if (sx + sw > vw) sw = vw - sx;
        if (sy + sh > vh) sh = vh - sy;
        if (sw < 40 || sh < 20) {
            sx = 0; sy = vh * 0.28; sw = vw; sh = vh * 0.44;
        }
        cropCanvas.width = Math.max(1, Math.round(sw));
        cropCanvas.height = Math.max(1, Math.round(sh));
        cropCtx.drawImage(video, sx, sy, sw, sh, 0, 0, cropCanvas.width, cropCanvas.height);
        return cropCanvas;
    }

    function drawFull() {
        if (!video || !video.videoWidth || !fullCtx) return null;
        var vw = video.videoWidth;
        var vh = video.videoHeight;
        var maxW = 960;
        var scale = vw > maxW ? maxW / vw : 1;
        fullCanvas.width = Math.max(1, Math.round(vw * scale));
        fullCanvas.height = Math.max(1, Math.round(vh * scale));
        fullCtx.drawImage(video, 0, 0, fullCanvas.width, fullCanvas.height);
        return fullCanvas;
    }

    function nativeDetect(source) {
        if (!window.BarcodeDetector || !source) return Promise.resolve([]);
        if (!nativeDetect.detector) {
            var formatSets = [
                ['code_128', 'code_39', 'code_93', 'itf', 'codabar'],
                ['code_128', 'code_39'],
                ['code_128']
            ];
            for (var i = 0; i < formatSets.length && !nativeDetect.detector; i++) {
                try { nativeDetect.detector = new BarcodeDetector({ formats: formatSets[i] }); } catch (e) {}
            }
            if (!nativeDetect.detector) {
                try { nativeDetect.detector = new BarcodeDetector(); } catch (e) { return Promise.resolve([]); }
            }
        }
        return nativeDetect.detector.detect(source).then(function (codes) {
            var values = [];
            for (var i = 0; i < (codes || []).length; i++) {
                if (codes[i] && codes[i].rawValue) values.push(codes[i].rawValue);
            }
            return values;
        }).catch(function () { return []; });
    }

    function startScanLoop() {
        if (timer || skipScan) return;
        var tick = 0;
        timer = setInterval(function () {
            if (!scanning || isStale() || scanBusy || !video || !video.videoWidth) return;
            scanBusy = true;
            tick += 1;
            var band = drawBand();
            var tasks = [nativeDetect(band || video)];
            if (band) tasks.push(decodeCanvas(band));
            if (tick % 3 === 0) {
                var full = drawFull();
                if (full) {
                    tasks.push(nativeDetect(full));
                    tasks.push(decodeCanvas(full));
                }
            }
            Promise.all(tasks).then(function (results) {
                if (!scanning || isStale()) return;
                var values = [];
                for (var i = 0; i < results.length; i++) {
                    var item = results[i];
                    if (Array.isArray(item)) values = values.concat(item);
                    else if (item) values.push(item);
                }
                if (values.length) consider(values);
            }).catch(function () {}).finally(function () { scanBusy = false; });
        }, 120);
    }

    function attachStream(media) {
        if (isStale()) {
            media.getTracks().forEach(function (t) { t.stop(); });
            return Promise.resolve();
        }
        stream = media;
        video.hidden = false;
        video.srcObject = stream;
        return playPreview(video).then(function () {
            try {
                var track = media.getVideoTracks()[0];
                if (track && typeof track.applyConstraints === 'function') {
                    track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(function () {});
                }
            } catch (e) {}
        });
    }

    function startLiveCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('Sin cámara'));
        }
        video.hidden = false;
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.muted = true;
        video.playsInline = true;
        var tries = [
            { audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } } },
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
    if (btnManual) btnManual.hidden = true;
    if (manualWrap) manualWrap.hidden = false;
    if (confirmRow) confirmRow.hidden = true;
    if (manualInput) {
        manualInput.value = existingTracking ? existingTracking.toUpperCase() : '';
        manualInput.hidden = false;
    }
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

    return window.skylinkLoadHtml5Qrcode().catch(function () {}).then(function () {
        if (isStale()) return;
        html5Scanner = prepareDecoder();
        return startLiveCamera();
    }).then(function () {
        if (isStale()) return;
        if (skipScan) {
            setHint('Tracking listo. Tome la foto del paquete');
            enterPhotoMode();
            return;
        }
        setHint('Buscando el código de barras…');
        startScanLoop();
    }).catch(function () {
        if (isStale()) return;
        setHint('No se pudo abrir la cámara. Escriba el tracking.');
        if (manualWrap) manualWrap.hidden = false;
        if (btnManual) btnManual.hidden = true;
    });
};
</script>
