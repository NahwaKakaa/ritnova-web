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

// Mode terang/malam: lampu bertali (desktop) dan stop kontak (ponsel).
(() => {
    const root = document.documentElement;
    const KEY = 'ritnova-theme';
    const switches = document.querySelectorAll('[data-theme-toggle]');
    if (!switches.length) return;
    let timer;

    const sync = () => {
        const on = root.dataset.theme !== 'dark';
        const label = on ? 'Lampu menyala (mode terang). Matikan untuk mode malam' : 'Lampu mati (mode malam). Nyalakan untuk mode terang';
        switches.forEach((b) => { b.setAttribute('aria-pressed', String(on)); b.setAttribute('aria-label', label); b.dataset.tip = on ? 'Mode terang' : 'Mode malam'; });
    };
    const toggle = () => {
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.classList.add('theme-switching');
        root.dataset.theme = next;
        try { localStorage.setItem(KEY, next); } catch (e) { /* penyimpanan tidak tersedia: mode berlaku di halaman ini saja */ }
        sync();
        clearTimeout(timer);
        timer = setTimeout(() => root.classList.remove('theme-switching'), 500);
        return next;
    };
    const replay = (el, cls) => { el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); };

    // Lampu: tarik talinya ke bawah (atau klik / Enter).
    document.querySelectorAll('[data-lamp]').forEach((lamp) => {
        const cord = lamp.querySelector('.lamp-cord');
        const shade = lamp.querySelector('.lamp-shade');
        let startY = null, pull = 0, dragged = false, skipClick = false;

        cord.addEventListener('pointerdown', (e) => {
            startY = e.clientY; pull = 0; dragged = false;
            cord.setPointerCapture(e.pointerId);
            cord.classList.remove('is-released', 'is-tugged');
        });
        cord.addEventListener('pointermove', (e) => {
            if (startY === null) return;
            pull = Math.max(0, Math.min(30, e.clientY - startY));
            if (pull > 3) dragged = true;
            cord.style.transform = `translateY(${pull}px)`;
        });
        const end = () => {
            if (startY === null) return;
            startY = null;
            if (!dragged) return; // klik biasa ditangani di bawah
            skipClick = true;
            cord.style.setProperty('--pull', `${pull}px`);
            cord.style.transform = '';
            replay(cord, 'is-released');
            if (pull >= 14) { toggle(); replay(shade, 'is-swinging'); }
        };
        cord.addEventListener('pointerup', end);
        cord.addEventListener('pointercancel', end);
        cord.addEventListener('click', () => {
            if (skipClick) { skipClick = false; return; }
            replay(cord, 'is-tugged');
            replay(shade, 'is-swinging');
            setTimeout(toggle, 160);
        });
    });

    // Stop kontak: ketuk untuk menancapkan / mencabut steker.
    document.querySelectorAll('[data-socket]').forEach((socket) => {
        socket.addEventListener('click', () => {
            if (toggle() === 'light') replay(socket.querySelector('.socket-spark'), 'is-on');
        });
    });

    sync();
})();

// Gulir halus dengan percepatan lembut untuk tautan di halaman yang sama dan tombol kembali ke atas.
(() => {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
    let frame = null;

    const scrollToY = (y, done) => {
        const start = window.scrollY;
        const max = document.documentElement.scrollHeight - window.innerHeight;
        const target = Math.max(0, Math.min(max, y));
        const dist = target - start;
        if (frame) cancelAnimationFrame(frame);
        if (reduce.matches || Math.abs(dist) < 2) { window.scrollTo({ top: target, behavior: 'instant' }); done?.(); return; }
        const duration = Math.min(1100, Math.max(450, Math.abs(dist) * 0.45));
        const root = document.documentElement;
        root.style.scrollBehavior = 'auto'; // matikan smooth CSS agar tidak bertabrakan dengan animasi ini
        const t0 = performance.now();
        const step = (now) => {
            const p = Math.min(1, (now - t0) / duration);
            window.scrollTo(0, start + dist * ease(p));
            if (p < 1) frame = requestAnimationFrame(step);
            else { frame = null; root.style.scrollBehavior = ''; done?.(); }
        };
        frame = requestAnimationFrame(step);
    };
    // Pengguna menggulir sendiri: hentikan animasi.
    ['wheel', 'touchstart', 'keydown'].forEach((ev) => window.addEventListener(ev, () => {
        if (frame) { cancelAnimationFrame(frame); frame = null; document.documentElement.style.scrollBehavior = ''; }
    }, { passive: true }));

    const offset = () => parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop) || 0;

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href^="#"]');
        if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey) return;
        const id = decodeURIComponent(a.getAttribute('href').slice(1));
        const el = id && document.getElementById(id);
        if (!el) return;
        e.preventDefault();
        scrollToY(el.getBoundingClientRect().top + window.scrollY - offset(), () => {
            if (!el.matches('a, button, input, select, textarea, [tabindex]')) el.setAttribute('tabindex', '-1');
            el.focus({ preventScroll: true });
        });
        history.pushState(null, '', `#${id}`);
    });

    // Tombol kembali ke atas + cincin progres baca.
    const btn = document.querySelector('[data-to-top]');
    if (btn) {
        const ring = btn.querySelector('[data-to-top-ring]');
        let ticking = false;
        const update = () => {
            ticking = false;
            const max = document.documentElement.scrollHeight - window.innerHeight;
            const y = window.scrollY;
            const show = y > Math.min(480, max * 0.5) && max > 300;
            btn.classList.toggle('is-visible', show);
            btn.tabIndex = show ? 0 : -1;
            btn.setAttribute('aria-hidden', String(!show));
            ring.setAttribute('stroke-dashoffset', String(100 - (max > 0 ? (y / max) * 100 : 0)));
        };
        window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
        window.addEventListener('resize', update);
        btn.addEventListener('click', () => {
            scrollToY(0, () => {
                const first = document.querySelector('#konten, main, header a');
                if (first) { if (!first.matches('a, [tabindex]')) first.setAttribute('tabindex', '-1'); first.focus({ preventScroll: true }); }
            });
        });
        update();
    }

    // Tandai menu landing sesuai bagian yang sedang dibaca.
    document.querySelectorAll('[data-scrollspy]').forEach((nav) => {
        const links = [...nav.querySelectorAll('a[href^="#"]')];
        const map = new Map(links.map((a) => [document.getElementById(a.getAttribute('href').slice(1)), a]).filter(([el]) => el));
        if (!map.size || !('IntersectionObserver' in window)) return;
        const visible = new Set();
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => (en.isIntersecting ? visible.add(en.target) : visible.delete(en.target)));
            const current = [...map.keys()].find((el) => visible.has(el));
            links.forEach((a) => a.setAttribute('aria-current', String(map.get(current) === a)));
        }, { rootMargin: '-35% 0px -55% 0px' });
        map.forEach((_, el) => io.observe(el));
    });
})();

// Panel peneliti: laci menu di ponsel dan pilihan kohort.
(() => {
    const drawer = document.querySelector('[data-drawer]');
    if (drawer) {
        const backdrop = document.querySelector('[data-drawer-backdrop]');
        const opener = document.querySelector('[data-drawer-open]');
        const mobile = window.matchMedia('(max-width: 767px)');
        const syncInert = () => { drawer.inert = mobile.matches && drawer.classList.contains('-translate-x-full'); };
        mobile.addEventListener('change', syncInert);
        syncInert();
        const setOpen = (open) => {
            drawer.classList.toggle('-translate-x-full', !open);
            syncInert();
            backdrop?.classList.toggle('hidden', !open);
            opener?.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('overflow-hidden', open);
            if (open) drawer.querySelector('a, button, select')?.focus();
            else opener?.focus({ preventScroll: true });
        };
        opener?.addEventListener('click', () => setOpen(true));
        backdrop?.addEventListener('click', () => setOpen(false));
        drawer.querySelector('[data-drawer-close]')?.addEventListener('click', () => setOpen(false));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !backdrop?.classList.contains('hidden')) setOpen(false); });
    }
    document.querySelectorAll('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form?.submit()));

    // Pilih semua baris tabel (aksi massal).
    document.querySelectorAll('[data-check-all]').forEach((all) => {
        const boxes = () => document.querySelectorAll(`[data-check="${all.dataset.checkAll}"]`);
        all.addEventListener('change', () => boxes().forEach((b) => { b.checked = all.checked; }));
    });

    // Sembunyikan kolom kelompok (mode buta) — tersimpan per browser.
    document.querySelectorAll('[data-blind-toggle]').forEach((btn) => {
        const table = document.getElementById(btn.dataset.blindToggle);
        let blind = false;
        try { blind = localStorage.getItem('ritnova-blind') === '1'; } catch (e) { /* abaikan */ }
        const apply = () => {
            table?.classList.toggle('is-blind', blind);
            btn.setAttribute('aria-pressed', String(blind));
            btn.querySelector('[data-blind-label]').textContent = blind ? 'Tampilkan kelompok' : 'Sembunyikan kelompok';
        };
        btn.addEventListener('click', () => {
            blind = !blind;
            try { localStorage.setItem('ritnova-blind', blind ? '1' : '0'); } catch (e) { /* abaikan */ }
            apply();
        });
        apply();
    });

    // Konfirmasi aksi penting (form dengan data-confirm) memakai dialog RITNOVA, bukan confirm() browser.
    // Atribut: data-confirm (pesan), data-confirm-title, data-confirm-ok (label tombol), data-confirm-tone="danger".
    const dialog = document.querySelector('[data-confirm-dialog]');
    document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (e) => {
        if (form.dataset.confirmed === '1') return;
        e.preventDefault();
        if (!dialog?.showModal) return; // tanpa dukungan <dialog>: aksi tidak dijalankan diam-diam
        const submitter = e.submitter;
        dialog.dataset.tone = form.dataset.confirmTone || '';
        dialog.querySelector('[data-confirm-title]').textContent = form.dataset.confirmTitle || 'Lanjutkan?';
        dialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
        const ok = dialog.querySelector('[data-confirm-ok]');
        ok.textContent = form.dataset.confirmOk || 'Lanjutkan';
        const close = () => { dialog.close(); ok.onclick = null; };
        ok.onclick = () => {
            close();
            form.dataset.confirmed = '1';
            form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
        };
        dialog.querySelector('[data-confirm-cancel]').onclick = close;
        dialog.onclick = (ev) => { if (ev.target === dialog) close(); };
        dialog.showModal();
        (form.dataset.confirmTone === 'danger' ? dialog.querySelector('[data-confirm-cancel]') : ok).focus();
    }));

    // Editor instrumen: tampilkan isian sesuai jenis butir.
    document.addEventListener('change', (e) => {
        const select = e.target.closest('[data-item-type]');
        if (!select) return;
        select.closest('[data-instrument-item]')?.querySelectorAll('[data-show-for]').forEach((el) => {
            el.hidden = !el.dataset.showFor.split(' ').includes(select.value);
        });
    });

    // Editor butir kuis: tambah / hapus baris.
    document.querySelectorAll('[data-repeater]').forEach((wrap) => {
        const list = wrap.querySelector('[data-repeater-list]');
        const tpl = wrap.querySelector('template');
        const renumber = () => list.querySelectorAll('[data-repeater-item]').forEach((item, i) => {
            item.querySelectorAll('[data-name]').forEach((el) => { el.name = el.dataset.name.replace('__i__', i); });
            item.querySelector('[data-repeater-index]')?.replaceChildren(String(i + 1));
        });
        wrap.querySelector('[data-repeater-add]')?.addEventListener('click', () => {
            list.append(tpl.content.cloneNode(true));
            renumber();
            list.lastElementChild?.querySelector('textarea, input')?.focus();
        });
        list.addEventListener('click', (e) => {
            const item = e.target.closest('[data-repeater-item]');
            if (!item) return;
            if (e.target.closest('[data-repeater-remove]')) {
                item.remove();
            } else if (e.target.closest('[data-repeater-up]') && item.previousElementSibling) {
                item.previousElementSibling.before(item);
            } else if (e.target.closest('[data-repeater-down]') && item.nextElementSibling) {
                item.nextElementSibling.after(item);
            } else {
                return;
            }
            renumber();
            e.target.closest('button')?.focus();
        });
        renumber();
    });
})();
