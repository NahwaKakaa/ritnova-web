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
