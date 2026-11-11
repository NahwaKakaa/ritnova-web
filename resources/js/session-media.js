/**
 * Pelacak paparan media sesi (Layar 20).
 *
 * - Video YouTube: IFrame API; video lain: <video>. Podcast: <audio>.
 * - Infografis: dianggap selesai setelah digulir sampai akhir DAN dibaca minimal N detik.
 * - Detik aktif hanya dihitung saat tab terlihat dan media sedang diputar/dibaca.
 * - Persentase dikirim ke server; status "selesai" tetap diputuskan server.
 */
const SEND_EVERY_MS = 15000;

function youtubeId(url) {
    const m = String(url).match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/);
    return m ? m[1] : null;
}

function loadYouTubeApi() {
    if (window.YT?.Player) return Promise.resolve(window.YT);
    return new Promise((resolve) => {
        const previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = () => { previous?.(); resolve(window.YT); };
        const s = document.createElement('script');
        s.src = 'https://www.youtube.com/iframe_api';
        document.head.appendChild(s);
    });
}

function setupTracker(root) {
    const trackUrl = root.dataset.trackUrl;
    const trackable = root.dataset.trackable === '1';
    const type = root.dataset.type;
    const src = root.dataset.src;
    const bar = document.querySelector('[data-media-bar]');
    const label = document.querySelector('[data-media-label]');
    const activeEl = document.querySelector('[data-active-seconds]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    let pct = Number(root.dataset.pct || 0);
    let unsentSeconds = 0;
    let totalActive = Number(activeEl?.dataset.activeSeconds || 0);
    let isActive = () => false;
    let currentPct = () => pct;

    const render = () => {
        const shown = Math.round(Math.max(pct, currentPct()) * 100);
        if (bar) bar.style.width = `${shown}%`;
        if (label) label.textContent = `${shown}%`;
        if (activeEl) activeEl.textContent = `${String(Math.floor(totalActive / 60)).padStart(2, '0')}m ${String(totalActive % 60).padStart(2, '0')}d`;
    };

    const send = async (useBeacon = false) => {
        if (!trackable) return;
        const body = { pct: Math.min(1, Math.max(pct, currentPct())), active_seconds: Math.min(300, unsentSeconds) };
        if (body.active_seconds === 0 && body.pct <= pct && !useBeacon) return;
        unsentSeconds = 0;
        pct = body.pct;
        if (useBeacon) {
            const data = new FormData();
            data.append('_token', csrf);
            data.append('pct', body.pct);
            data.append('active_seconds', body.active_seconds);
            navigator.sendBeacon(trackUrl, data);
            return;
        }
        try {
            const res = await fetch(trackUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (json.media_completed && !root.dataset.reloaded) {
                root.dataset.reloaded = '1';
                window.location.reload(); // tampilkan tahap 01 selesai
            }
        } catch (e) { /* dicoba lagi pada interval berikutnya */ }
    };

    // Detik aktif: tab terlihat + media berjalan/dibaca.
    setInterval(() => {
        if (document.visibilityState === 'visible' && isActive()) {
            unsentSeconds += 1;
            totalActive += 1;
        }
        render();
    }, 1000);
    setInterval(() => send(), SEND_EVERY_MS);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') send(true); });

    if (!src) return;

    if (type === 'video') {
        const holder = root.querySelector('[data-player]');
        const id = youtubeId(src);
        if (id) {
            let player;
            loadYouTubeApi().then((YT) => {
                player = new YT.Player(holder, { videoId: id, width: '100%', height: '100%', playerVars: { cc_load_policy: 1, rel: 0 } });
                let maxSeen = 0;
                isActive = () => player?.getPlayerState?.() === YT.PlayerState.PLAYING;
                currentPct = () => {
                    const d = player?.getDuration?.() || 0;
                    if (d > 0) maxSeen = Math.max(maxSeen, (player.getCurrentTime?.() || 0) / d);
                    return maxSeen;
                };
            });
        } else {
            const video = document.createElement('video');
            Object.assign(video, { src, controls: true, preload: 'metadata', className: 'h-full w-full' });
            holder.replaceWith(video);
            trackMediaElement(video);
        }
    } else if (type === 'podcast') {
        trackMediaElement(root.querySelector('[data-player]'));
    } else {
        // Infografis
        const minSeconds = Number(root.dataset.minSeconds || 45);
        const end = root.querySelector('[data-infographic-end]');
        let reachedEnd = false;
        let readSeconds = 0;
        let visible = false;
        new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) reachedEnd = true; }), { threshold: 1 }).observe(end);
        new IntersectionObserver((entries) => entries.forEach((e) => { visible = e.isIntersecting; }), { threshold: 0.3 }).observe(root.querySelector('[data-player]'));
        isActive = () => visible;
        setInterval(() => { if (visible && document.visibilityState === 'visible') readSeconds += 1; }, 1000);
        currentPct = () => (reachedEnd && readSeconds >= minSeconds ? 1 : Math.min(0.9, readSeconds / minSeconds * 0.9));
    }

    function trackMediaElement(el) {
        let maxSeen = 0;
        isActive = () => !el.paused && !el.ended;
        currentPct = () => {
            if (el.duration > 0) maxSeen = Math.max(maxSeen, el.currentTime / el.duration);
            return el.ended ? 1 : maxSeen;
        };
    }
}

document.querySelectorAll('[data-session-media]').forEach(setupTracker);
