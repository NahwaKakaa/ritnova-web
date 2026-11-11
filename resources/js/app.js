import './session-media';
// Countdown untuk sesi terkunci (dihitung ulang tiap detik; sumber waktu tetap server).
document.querySelectorAll('[data-countdown]').forEach((el) => {
    const target = Number(el.dataset.countdown) * 1000;
    const tick = () => {
        const s = Math.max(0, Math.floor((target - Date.now()) / 1000));
        const d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
        el.textContent = `${String(d).padStart(2, '0')} hari : ${String(h).padStart(2, '0')} jam : ${String(m).padStart(2, '0')} menit`;
        if (s === 0) location.reload();
    };
    tick();
    setInterval(tick, 1000);
});

// Countdown berpetak (Layar 19).
document.querySelectorAll('[data-countdown-grid]').forEach((el) => {
    const target = Number(el.dataset.countdownGrid) * 1000;
    const set = (k, v) => { const n = el.querySelector(`[data-unit="${k}"]`); if (n) n.textContent = String(v).padStart(2, '0'); };
    const tick = () => {
        const s = Math.max(0, Math.floor((target - Date.now()) / 1000));
        set('d', Math.floor(s / 86400)); set('h', Math.floor((s % 86400) / 3600)); set('m', Math.floor((s % 3600) / 60));
        if (s === 0) location.reload();
    };
    tick();
    setInterval(tick, 1000);
});

// Pratinjau durasi tidur di form diary (tampilan saja; validasi & hitungan di server).
document.querySelectorAll('[data-diary-form]').forEach((form) => {
    const onset = form.querySelector('#sleep_onset_time');
    const offset = form.querySelector('#sleep_offset_time');
    const out = form.querySelector('[data-duration]');
    const update = () => {
        if (!onset.value || !offset.value) { out.textContent = ''; return; }
        const toMin = (v) => { const [h, m] = v.split(':').map(Number); return h * 60 + m; };
        let d = toMin(offset.value) - toMin(onset.value);
        if (d <= 0) d += 1440;
        out.textContent = `Durasi tidur sekitar ${Math.floor(d / 60)} jam ${d % 60} menit`;
    };
    onset.addEventListener('input', update);
    offset.addEventListener('input', update);
    update();
});

// Jam WITA yang berjalan.
const witaClock = () => new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Makassar' }).format(new Date()).replace(':', '.');
document.querySelectorAll('[data-clock]').forEach((el) => {
    const tick = () => { el.textContent = witaClock(); };
    tick();
    setInterval(tick, 15000);
});

// Tooltip bersama untuk elemen [data-tip] (termasuk SVG).
(() => {
    const tip = document.querySelector('.tooltip');
    if (!tip) return;
    const show = (el, x, y) => {
        tip.textContent = el.dataset.tip;
        tip.hidden = false;
        const r = tip.getBoundingClientRect();
        tip.style.left = `${Math.min(window.innerWidth - r.width - 8, Math.max(8, x - r.width / 2))}px`;
        tip.style.top = `${Math.max(8, y - r.height - 14)}px`;
    };
    const hide = () => { tip.hidden = true; };
    document.addEventListener('pointermove', (e) => {
        const el = e.target.closest?.('[data-tip]');
        if (el) show(el, e.clientX, e.clientY); else hide();
    });
    document.addEventListener('focusin', (e) => {
        const el = e.target.closest?.('[data-tip]');
        if (el) { const r = el.getBoundingClientRect(); show(el, r.left + r.width / 2, r.top); }
    });
    document.addEventListener('focusout', hide);
    document.addEventListener('scroll', hide, { passive: true });
})();

// Jarum "sekarang" pada jam ritme.
document.querySelectorAll('[data-now-hand]').forEach((hand) => {
    const update = () => {
        const parts = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Makassar' }).formatToParts(new Date());
        const h = Number(parts.find((p) => p.type === 'hour').value) % 24;
        const m = Number(parts.find((p) => p.type === 'minute').value);
        hand.setAttribute('transform', `rotate(${((h * 60 + m) / 1440) * 360} 120 120)`);
        hand.dataset.tip = `Sekarang ${String(h).padStart(2, '0')}.${String(m).padStart(2, '0')} WITA`;
    };
    setInterval(update, 30000);
});

// Simulator SJL: geser slider -> server menghitung -> jam ritme diperbarui.
document.querySelectorAll('[data-sjl-sim]').forEach((root) => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const inputs = [...root.querySelectorAll('[data-sim-input]')];
    const fmt = (m) => { const v = ((m % 1440) + 1440) % 1440; return `${String(Math.floor(v / 60)).padStart(2, '0')}.${String(v % 60).padStart(2, '0')}`; };
    let timer;
    let seq = 0;

    const run = async () => {
        const body = { theme: root.dataset.theme };
        inputs.forEach((i) => { body[i.dataset.simInput] = Number(i.value) % 1440; });
        const mine = ++seq;
        try {
            const res = await fetch(root.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            if (!res.ok || mine !== seq) return;
            const json = await res.json();
            root.querySelector('[data-sim-dial]').innerHTML = json.dial;
            root.querySelector('[data-sim-result]').textContent = json.ok ? json.sjl_human : 'Periksa jam tidur dan bangun';
        } catch (e) { /* abaikan; coba lagi saat slider digeser */ }
    };

    inputs.forEach((input) => {
        input.addEventListener('input', () => {
            root.querySelector(`[data-sim-label="${input.dataset.simInput}"]`).textContent = fmt(Number(input.value));
            clearTimeout(timer);
            timer = setTimeout(run, 120);
        });
    });
});

// Garis waktu tidur yang bisa digeser (hanya mengisi input jam; validasi tetap di server).
document.querySelectorAll('[data-diary-form]').forEach((form) => {
    const box = form.querySelector('[data-timeline]');
    if (!box) return;
    const AXIS = 1080; // 18.00 sampai 12.00 berikutnya
    const STEP = 5;
    const track = box.querySelector('[data-timeline-track]');
    const fill = box.querySelector('[data-timeline-fill]');
    const inputs = { onset: form.querySelector('#sleep_onset_time'), offset: form.querySelector('#sleep_offset_time') };
    const handles = { onset: box.querySelector('[data-handle="onset"]'), offset: box.querySelector('[data-handle="offset"]') };
    const fallback = { onset: 300, offset: 780 }; // 23.00 dan 07.00

    const toAxis = (v) => {
        if (!v) return null;
        const [h, m] = v.split(':').map(Number);
        const a = (h * 60 + m - 1080 + 1440) % 1440;
        return a <= AXIS ? a : (a < 1260 ? AXIS : 0);
    };
    const toTime = (a) => {
        const m = (a + 1080) % 1440;
        return `${String(Math.floor(m / 60)).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;
    };
    const pos = (k) => toAxis(inputs[k].value) ?? fallback[k];

    const render = () => {
        const a = pos('onset'), b = pos('offset');
        for (const k of ['onset', 'offset']) {
            const v = k === 'onset' ? a : b;
            handles[k].style.left = `${(v / AXIS) * 100}%`;
            handles[k].setAttribute('aria-valuenow', v);
            handles[k].setAttribute('aria-valuetext', toTime(v).replace(':', '.'));
            handles[k].classList.toggle('opacity-60', !inputs[k].value);
        }
        fill.style.left = `${(Math.min(a, b) / AXIS) * 100}%`;
        fill.style.width = `${(Math.abs(b - a) / AXIS) * 100}%`;
    };

    const set = (k, a) => {
        a = Math.round(Math.max(0, Math.min(AXIS, a)) / STEP) * STEP;
        if (k === 'onset') a = Math.min(a, pos('offset') - 15);
        else a = Math.max(a, pos('onset') + 15);
        a = Math.max(0, Math.min(AXIS, a));
        inputs[k].value = toTime(a);
        inputs[k].dispatchEvent(new Event('input', { bubbles: true }));
        render();
    };

    for (const k of ['onset', 'offset']) {
        const h = handles[k];
        h.addEventListener('pointerdown', (e) => {
            h.setPointerCapture(e.pointerId);
            const move = (ev) => {
                const r = track.getBoundingClientRect();
                set(k, ((ev.clientX - r.left) / r.width) * AXIS);
            };
            const up = () => { h.removeEventListener('pointermove', move); h.removeEventListener('pointerup', up); h.removeEventListener('pointercancel', up); };
            h.addEventListener('pointermove', move);
            h.addEventListener('pointerup', up);
            h.addEventListener('pointercancel', up);
        });
        h.addEventListener('keydown', (e) => {
            const delta = { ArrowLeft: -STEP, ArrowDown: -STEP, ArrowRight: STEP, ArrowUp: STEP, PageDown: -60, PageUp: 60 }[e.key];
            if (delta === undefined) return;
            e.preventDefault();
            set(k, pos(k) + delta * (e.shiftKey ? 6 : 1));
        });
        inputs[k].addEventListener('input', render);
    }

    box.classList.remove('hidden');
    render();
});

// Tutup perayaan sesi selesai.
document.querySelectorAll('[data-celebrate]').forEach((overlay) => {
    const close = () => overlay.remove();
    overlay.querySelector('[data-celebrate-close]')?.addEventListener('click', (e) => { e.preventDefault(); close(); });
    overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); }, { once: true });
});
