//


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
