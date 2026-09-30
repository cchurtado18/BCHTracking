{{-- Visor: la cámara propia se ve siempre; el lector solo decodifica frames. --}}
<div id="cspOverlay" class="csp-overlay" hidden data-csp-build="23">
    <video id="cspVideo" class="csp-video" autoplay muted playsinline webkit-playsinline></video>
    <div id="cspReader" class="csp-reader" aria-hidden="true"></div>
    <div class="csp-frame" aria-hidden="true"></div>
    <div class="csp-bar csp-bar-top">
        <p id="cspHint" class="csp-hint">Apunte el código de barras o el QR del tracking</p>
        <p id="cspRead" class="csp-read" hidden></p>
        <div id="cspPrealert" class="csp-prealert" hidden></div>
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
        <button type="button" id="cspRetryCam" class="csp-shutter" hidden>Abrir cámara</button>
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
    position: absolute; left: 6%; right: 6%; top: 22%; bottom: 32%;
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
.csp-prealert {
    margin: 0; padding: 0.45rem 0.7rem; border-radius: 0.7rem;
    background: #ecfdf3; color: #14532d; border: 1px solid #86c9a4;
    font-size: 0.8rem; font-weight: 700; text-align: center; max-width: 22rem;
}
.csp-prealert[hidden] { display: none !important; }
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
        return loadScript('/vendor/html5-qrcode.min.js');
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
    var btnRetryCam = document.getElementById('cspRetryCam');
    var btnManual = document.getElementById('cspManualBtn');
    var manualWrap = document.getElementById('cspManualWrap');
    var manualInput = document.getElementById('cspManualInput');
    var manualOk = document.getElementById('cspManualOk');
    var confirmRow = document.getElementById('cspConfirmRow');
    var btnReject = document.getElementById('cspReject');
    var prealertEl = document.getElementById('cspPrealert');
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
    var padCanvas = document.createElement('canvas');
    var padCtx = padCanvas.getContext('2d', { willReadFrequently: true }) || padCanvas.getContext('2d');

    function setHint(text) {
        if (hint) hint.textContent = text;
    }
    function isStale() {
        return closed || session !== window.__cspSession;
    }
    function inAppBrowser() {
        var ua = navigator.userAgent || '';
        if (/WhatsApp|FBAN|FBAV|Instagram/i.test(ua)) return true;
        return /iP(hone|od|ad)/.test(ua) && !/Safari\//.test(ua);
    }
    function cameraBlockReason() {
        if (inAppBrowser()) {
            return 'Ábralo en Safari, no en WhatsApp. Toque “Abrir en Safari”.';
        }
        if (!window.isSecureContext) {
            return 'El iPhone no enciende la cámara en http. Use https:// (candado).';
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return 'Este navegador no permite la cámara en vivo. Ábralo en Safari.';
        }
        return '';
    }
    function cameraFailMessage(err) {
        var blocked = cameraBlockReason();
        if (blocked) return blocked;
        var name = err && err.name;
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            return 'Safari bloqueó la cámara. Ajustes > Safari > Cámara: permitir, y pulse Abrir cámara.';
        }
        if (name === 'SecurityError' || !window.isSecureContext) {
            return 'Abra el enlace con https://. En http el iPhone no enciende la cámara.';
        }
        return 'No se pudo abrir la cámara. Ábralo en Safari y pulse Abrir cámara.';
    }
    function showCameraFailed(err) {
        setHint(cameraFailMessage(err));
        if (manualWrap) manualWrap.hidden = false;
        if (btnManual) btnManual.hidden = true;
        if (btnRetryCam) btnRetryCam.hidden = false;
        if (btnShutter) btnShutter.hidden = true;
    }
    function normalize(raw) {
        return String(raw || '')
            .replace(/[\s\u0000\u001d\u001e()]/g, '')
            .replace(/^\][A-Z0-9]{1,3}/, '')
            .trim()
            .toUpperCase();
    }
    function trackingFromRaw(raw) {
        var original = String(raw || '').trim();
        var fromUrl = extractFromUrl(original);
        var text = fromUrl || original;
        var n = normalize(text);
        var ups = n.match(/1Z[A-Z0-9]{16}/);
        if (ups) return ups[0];
        var amazon = n.match(/TB[A-Z]\d{10,16}/);
        if (amazon) return amazon[0];
        var spx = n.match(/SPX[A-Z0-9]{12,}/);
        if (spx) return spx[0];
        var embedded = n.match(/9[0-5]\d{18,20}/);
        if (embedded && (n.length > 26 || /^HTTPS?:/i.test(original) || /USPS\.COM/i.test(original) || fromUrl)) {
            text = embedded[0];
        }
        return canonicalTracking(text);
    }
    function extractFromUrl(text) {
        var u = String(text || '');
        var hit = u.match(/[?&](?:tLabels|tLabel|q|trackingNumber|tracknum|trackNums|tracking|track|inquiryNumber|trackingId|tracking_id)=([0-9A-Za-z]{10,34})/i)
            || u.match(/\/(?:track|tracking|pkg|package)\/([0-9A-Za-z]{10,34})/i);
        return hit ? hit[1] : '';
    }
    function looksLikeUspsStart(code) {
        return /^9[0-5]\d{17,}/.test(code) || /^[A-Z]{2}\d{9}[A-Z]{2}/.test(code);
    }
    function stripRoutingPrefix(code) {
        if (code.indexOf('420') !== 0 || code.length < 8) return code;
        var afterZip5 = code.substring(8);
        var afterZip9 = code.length > 12 ? code.substring(12) : '';
        if (looksLikeUspsStart(afterZip5)) return afterZip5;
        if (afterZip9 && looksLikeUspsStart(afterZip9)) return afterZip9;
        return afterZip5 || code;
    }
    function extractUsps(code) {
        code = stripRoutingPrefix(code);
        var m = code.match(/^([A-Z]{2}\d{9}[A-Z]{2})/)
            || code.match(/(9[0-5]\d{20})/)
            || code.match(/(9[0-5]\d{18})/);
        return m ? m[1] : '';
    }
    function canonicalTracking(raw) {
        var code = normalize(raw);
        if (!code || /^\d{6}$/.test(code)) return code;
        if (/^1Z[A-Z0-9]{16}/.test(code) || /^TB[A-Z]\d{10,}/.test(code)) return code;
        return extractUsps(code) || code;
    }
    function isUrl(code) {
        return /^HTTPS?:\/\//.test(code) || /^WWW\./.test(code);
    }
    function isPostageIndicia(code) {
        return /^0\d{2}[A-Z]\d{6,}$/.test(code);
    }
    function isUspsTracking(code) {
        return /^9[0-5]\d{18,20}$/.test(code);
    }
    function isUpsTracking(code) {
        return /^1Z[A-Z0-9]{16}$/.test(code);
    }
    function isAmazonTracking(code) {
        return /^TB[A-Z]\d{10,16}$/.test(code);
    }
    function isUspsIntl(code) {
        return /^[A-Z]{2}\d{9}[A-Z]{2}$/.test(code);
    }
    function isKnownCourier(code) {
        return isUspsTracking(code) || isUpsTracking(code) || isAmazonTracking(code) || isUspsIntl(code);
    }
    function isPlausible(code) {
        if (!code || code.length < 8 || code.length > 64) return false;
        if (isUrl(code)) return false;
        return /^[A-Z0-9]+$/.test(code);
    }
    function isCompleteTracking(code) {
        var value = trackingFromRaw(code);
        if (isPostageIndicia(normalize(code)) || isPostageIndicia(value)) return false;
        if (!isPlausible(value)) return false;
        if (value.length < 12) return false;
        if (isKnownCourier(value)) return true;
        if (/[A-Z]/.test(value) && /\d/.test(value) && value.length <= 34) return true;
        return /^\d{12,22}$/.test(value);
    }
    function scoreCode(code) {
        var value = trackingFromRaw(code);
        if (isPostageIndicia(normalize(code)) || isPostageIndicia(value)) return -1;
        if (!isPlausible(value)) return -1;
        var score = Math.min(value.length, 30);
        if (isCompleteTracking(code)) score += 40;
        if (isUspsTracking(value)) score += 36;
        if (isUpsTracking(value) || isAmazonTracking(value)) score += 40;
        if (/^[A-Z]{3,8}\d{8,}$/.test(value)) score += 24;
        if (/^\d+$/.test(value) && !isUspsTracking(value)) score -= 10;
        if (/^[A-Z]{3,8}$/.test(value)) score -= 20;
        if (value.length >= 12 && value.length <= 34) score += 8;
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
        if (timer) { clearTimeout(timer); clearInterval(timer); timer = null; }
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
        showOverlayPrealert(null);
        setHint('Apunte el código de barras o el QR del tracking');
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
        if (btnRetryCam) btnRetryCam.hidden = true;
        setHint('Tome la foto del paquete');
    }

    function showOverlayPrealert(data) {
        if (!prealertEl) return;
        if (!data) {
            prealertEl.hidden = true;
            prealertEl.textContent = '';
            return;
        }
        var agency = [data.agency_code, data.agency_name].filter(Boolean).join(' · ');
        prealertEl.textContent = 'Alerta: este tracking fue prealertado · ' + (data.name || codeFromData(data))
            + (data.service_label ? ' · ' + data.service_label : '')
            + (agency ? ' · ' + agency : '');
        prealertEl.hidden = false;
    }
    function codeFromData(data) {
        return data && data.tracking ? data.tracking : '';
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
        if (typeof window.skylinkLookupPrealert === 'function') {
            window.skylinkLookupPrealert(code).then(function (data) {
                if (isStale()) return;
                showOverlayPrealert(data);
                if (data && navigator.vibrate) {
                    try { navigator.vibrate([80, 40, 80]); } catch (e) {}
                }
            });
        }
        enterPhotoMode();
    }

    function consider(raw) {
        var list = typeof raw === 'string' ? [raw] : (raw || []);
        var extracted = [];
        for (var i = 0; i < list.length; i++) {
            var item = String(list[i] || '');
            if (isPostageIndicia(normalize(item))) {
                setHint('Ese es el código de arriba. Apunte al código largo de USPS TRACKING #');
            }
            var value = trackingFromRaw(item);
            if (value && extracted.indexOf(value) === -1) extracted.push(value);
        }
        var code = trackingFromRaw(combineParts(extracted.length ? extracted : list));
        if (!isCompleteTracking(code)) {
            if (extracted.length && hint && hint.textContent.indexOf('Ese es el código') === -1) {
                    setHint('Acerque el código de barras o el QR en el recuadro');
            }
            return false;
        }
        if (!scanning || isStale() || photoMode) return false;
        var needHits = 1;
        if (code === pendingMatch) matchHits += 1;
        else {
            pendingMatch = code;
            matchHits = 1;
        }
        if (matchHits < needHits) return false;
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
        if (btnRetryCam) btnRetryCam.hidden = true;
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
        return [F.CODE_128, F.QR_CODE, F.DATA_MATRIX, F.CODE_39, F.PDF_417, F.ITF, F.CODE_93].filter(function (v) {
            return typeof v === 'number';
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
            try { return new Ctor('cspReader', { verbose: false, useBarCodeDetectorIfSupported: false, formatsToSupport: [5, 0, 6, 3] }); }
            catch (err) { return null; }
        }
    }

    function decodeCanvas(canvas) {
        if (!canvas || !canvas.width || !html5Scanner || !html5Scanner.qrcode) return Promise.resolve('');
        var decoder = html5Scanner.qrcode.primaryDecoder || html5Scanner.qrcode;
        if (!decoder.decodeAsync) return Promise.resolve('');
        return decoder.decodeAsync(canvas).then(function (res) {
            return textFromDecode(res);
        }).catch(function () { return ''; });
    }

    function coverMappedCrop(leftPct, topPct, widthPct, heightPct) {
        if (!video || !video.videoWidth || !cropCtx) return null;
        var vw = video.videoWidth;
        var vh = video.videoHeight;
        var cw = overlay.clientWidth || window.innerWidth;
        var ch = overlay.clientHeight || window.innerHeight;
        if (!cw || !ch) return null;
        var scale = Math.max(cw / vw, ch / vh);
        var dw = vw * scale;
        var dh = vh * scale;
        var ox = (cw - dw) / 2;
        var oy = (ch - dh) / 2;
        var sx = (cw * leftPct - ox) / scale;
        var sy = (ch * topPct - oy) / scale;
        var sw = (cw * widthPct) / scale;
        var sh = (ch * heightPct) / scale;
        if (sx < 0) { sw += sx; sx = 0; }
        if (sy < 0) { sh += sy; sy = 0; }
        if (sx + sw > vw) sw = vw - sx;
        if (sy + sh > vh) sh = vh - sy;
        if (sw < 48 || sh < 16) return null;
        cropCanvas.width = Math.max(1, Math.round(sw));
        cropCanvas.height = Math.max(1, Math.round(sh));
        cropCtx.imageSmoothingEnabled = false;
        cropCtx.drawImage(video, sx, sy, sw, sh, 0, 0, cropCanvas.width, cropCanvas.height);
        return cropCanvas;
    }

    function padQuietZone(src) {
        if (!src || !src.width || !padCtx) return src;
        var padX = Math.max(48, Math.round(src.width * 0.08));
        var padY = Math.max(16, Math.round(src.height * 0.18));
        padCanvas.width = src.width + padX * 2;
        padCanvas.height = src.height + padY * 2;
        padCtx.fillStyle = '#ffffff';
        padCtx.fillRect(0, 0, padCanvas.width, padCanvas.height);
        padCtx.drawImage(src, padX, padY);
        return padCanvas;
    }

    function drawGuideBand() {
        return coverMappedCrop(0.02, 0.36, 0.96, 0.26);
    }

    function drawQrBox() {
        return coverMappedCrop(0.08, 0.18, 0.84, 0.52);
    }

    function drawFull() {
        if (!video || !video.videoWidth || !fullCtx) return null;
        var vw = video.videoWidth;
        var vh = video.videoHeight;
        var maxW = 1600;
        var scale = vw > maxW ? maxW / vw : 1;
        fullCanvas.width = Math.max(1, Math.round(vw * scale));
        fullCanvas.height = Math.max(1, Math.round(vh * scale));
        fullCtx.imageSmoothingEnabled = false;
        fullCtx.drawImage(video, 0, 0, fullCanvas.width, fullCanvas.height);
        return fullCanvas;
    }

    function nativeDetect(source) {
        if (!window.BarcodeDetector || !source) return Promise.resolve([]);
        if (!nativeDetect.detectors) {
            nativeDetect.detectors = [];
            var formatSets = [
                ['qr_code'],
                ['qr_code', 'data_matrix', 'pdf417'],
                ['code_128', 'code_39'],
                ['code_128']
            ];
            for (var i = 0; i < formatSets.length; i++) {
                try { nativeDetect.detectors.push(new BarcodeDetector({ formats: formatSets[i] })); } catch (e) {}
            }
            if (!nativeDetect.detectors.length) {
                try { nativeDetect.detectors.push(new BarcodeDetector()); } catch (e) {}
            }
        }
        if (!nativeDetect.detectors.length) return Promise.resolve([]);
        return Promise.all(nativeDetect.detectors.map(function (detector) {
            return detector.detect(source).then(function (codes) {
                var values = [];
                for (var i = 0; i < (codes || []).length; i++) {
                    if (codes[i] && codes[i].rawValue) values.push(codes[i].rawValue);
                }
                return values;
            }).catch(function () { return []; });
        })).then(function (groups) {
            var values = [];
            for (var g = 0; g < groups.length; g++) {
                for (var i = 0; i < groups[g].length; i++) {
                    if (values.indexOf(groups[g][i]) === -1) values.push(groups[g][i]);
                }
            }
            return values;
        });
    }

    function startScanLoop() {
        if (timer || skipScan) return;
        var tick = 0;
        function pump() {
            if (!scanning || isStale()) { timer = null; return; }
            if (scanBusy || !video || !video.videoWidth) {
                timer = setTimeout(pump, 80);
                return;
            }
            scanBusy = true;
            tick += 1;
            var band = drawGuideBand();
            var qrBox = drawQrBox();
            var full = (tick % 2 === 0) ? drawFull() : null;
            var tasks = [nativeDetect(video)];
            if (band) {
                var padded = padQuietZone(band);
                tasks.push(decodeCanvas(padded));
                tasks.push(nativeDetect(padded));
            }
            if (qrBox) {
                tasks.push(decodeCanvas(qrBox));
                tasks.push(nativeDetect(qrBox));
            }
            if (full) {
                tasks.push(decodeCanvas(full));
                tasks.push(nativeDetect(full));
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
            }).catch(function () {}).finally(function () {
                scanBusy = false;
                if (scanning && !isStale()) timer = setTimeout(pump, 70);
            });
        }
        timer = setTimeout(pump, 50);
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
            { audio: false, video: { facingMode: 'environment' } },
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
            }).catch(function (err) {
                if (isStale()) return Promise.resolve();
                var name = err && err.name;
                if (name === 'NotAllowedError' || name === 'PermissionDeniedError' || name === 'SecurityError') {
                    return Promise.reject(err);
                }
                if (i + 1 < tries.length) return attempt(i + 1);
                return Promise.reject(err || new Error('Sin cámara'));
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
    if (btnRetryCam) btnRetryCam.hidden = true;
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
            var code = canonicalTracking(manualInput && manualInput.value);
            if (!isPlausible(code)) {
                alert('Escriba el tracking de la etiqueta.');
                return;
            }
            applyTracking(code);
        };
    }
    if (btnRetryCam) {
        btnRetryCam.onclick = function () {
            if (isStale()) return;
            btnRetryCam.hidden = true;
            setHint('Abriendo cámara…');
            startLiveCamera().then(function () {
                if (isStale()) return;
                if (skipScan) {
                    setHint('Tracking listo. Tome la foto del paquete');
                    enterPhotoMode();
                    return;
                }
                setHint('Apunte el código de barras o el QR del tracking');
                startScanLoop();
            }).catch(function (err) {
                if (isStale()) return;
                showCameraFailed(err);
            });
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

    var cameraStart = startLiveCamera();

    return window.skylinkLoadHtml5Qrcode().catch(function () {}).then(function () {
        if (isStale()) return;
        html5Scanner = prepareDecoder();
        return cameraStart;
    }).then(function () {
        if (isStale()) return;
        if (skipScan) {
            setHint('Tracking listo. Tome la foto del paquete');
            enterPhotoMode();
            return;
        }
        setHint('Apunte el código de barras o el QR del tracking');
        startScanLoop();
    }).catch(function (err) {
        if (isStale()) return;
        showCameraFailed(err);
    });
};
</script>
