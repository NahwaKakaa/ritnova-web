// Ambil screenshot halaman RITNOVA (desktop & mobile) untuk tinjauan visual.
// Pemakaian: BASE_URL=http://127.0.0.1:8000 node scripts/screenshots.mjs
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';

const base = process.env.BASE_URL ?? 'http://127.0.0.1:8000';
const out = process.env.OUT_DIR ?? 'screenshots';
mkdirSync(out, { recursive: true });

const viewports = { desktop: { width: 1440, height: 900 }, mobile: { width: 390, height: 844 } };

const plans = [
    { user: null, pages: { landing: '/', login: '/masuk' } },
    { user: 'RV-001', pages: {
        dashboard: '/beranda', belajar: '/belajar', sesi: '/belajar/4', 'sesi-selesai': '/belajar/1', terkunci: '/belajar/9',
        tracker: '/catatan-tidur', 'tracker-form': '/catatan-tidur/baru', sjl: '/sjl-check', progres: '/progres',
        target: '/target', refleksi: '/refleksi', pengukuran: '/pengukuran', menu: '/menu', notifikasi: '/notifikasi',
        pengingat: '/pengingat', profil: '/profil', bantuan: '/bantuan',
    } },
    { user: 'RV-002', pages: { 'pembanding-dashboard': '/beranda' } },
    { user: 'RV-003', pages: { onboarding: '/persiapan' } },
    { user: 'RV-004', pages: { consent: '/persetujuan' } },
    // Panel peneliti
    { user: 'admin', password: process.env.ADMIN_PASSWORD ?? 'ganti-password-ini', pages: {
        'admin-ringkasan': '/admin', 'admin-peserta': '/admin/peserta', 'admin-peserta-detail': '@peserta:RV-001',
        'admin-paparan': '/admin/paparan', 'admin-pengukuran': '/admin/pengukuran', 'admin-kualitas': '/admin/kualitas-diary',
        'admin-konten': '/admin/konten', 'admin-konten-edit': '/admin/konten/4', 'admin-jadwal': '/admin/jadwal',
        'admin-ekspor': '/admin/ekspor', 'admin-audit': '/admin/audit', 'admin-akses': '/admin/akses',
    } },
    { dark: true, user: 'admin', password: process.env.ADMIN_PASSWORD ?? 'ganti-password-ini', pages: { 'malam-admin-ringkasan': '/admin', 'malam-admin-peserta': '/admin/peserta' } },
    // Mode malam (mengikuti preferensi perangkat saat belum ada pilihan tersimpan)
    { dark: true, user: null, pages: { 'malam-landing': '/', 'malam-login': '/masuk' } },
    { dark: true, user: 'RV-001', pages: {
        'malam-dashboard': '/beranda', 'malam-belajar': '/belajar', 'malam-sesi': '/belajar/4', 'malam-tracker': '/catatan-tidur',
        'malam-tracker-form': '/catatan-tidur/baru', 'malam-sjl': '/sjl-check', 'malam-progres': '/progres', 'malam-target': '/target',
    } },
];

const browser = await chromium.launch();
for (const [vpName, viewport] of Object.entries(viewports)) {
    for (const plan of plans) {
        const context = await browser.newContext({ viewport, deviceScaleFactor: 1, locale: 'id-ID', reducedMotion: 'reduce', colorScheme: plan.dark ? 'dark' : 'light' });
        const page = await context.newPage();
        if (plan.user) {
            await page.goto(`${base}/masuk`);
            await page.fill('#username', plan.user);
            await page.fill('#password', plan.password ?? 'password');
            await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type=submit], form button')]);
        }
        for (const [name, path] of Object.entries(plan.pages)) {
            let target = path;
            if (path.startsWith('@peserta:')) {
                await page.goto(`${base}/admin/peserta?q=${path.slice(9)}`, { waitUntil: 'networkidle' });
                target = new URL(await page.getAttribute('#participants-table tbody a', 'href'), base).pathname;
            }
            const res = await page.goto(`${base}${target}`, { waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
            await page.screenshot({ path: `${out}/${vpName}-${name}.png`, fullPage: true });
            console.log(`${vpName} ${name} ${path} -> ${res?.status()}`);
            if (name.endsWith('admin-peserta-detail') || name === 'admin-peserta-detail') {
                // Dialog konfirmasi RITNOVA (bukan confirm() browser).
                await page.click('text=Catat mengundurkan diri');
                await page.waitForSelector('[data-confirm-dialog][open]', { timeout: 3000 }).catch(() => {});
                await page.waitForTimeout(300);
                await page.screenshot({ path: `${out}/${vpName}-${name}-dialog.png` });
                console.log(`${vpName} ${name} dialog-open=${await page.evaluate(() => !!document.querySelector('[data-confirm-dialog][open]'))}`);
                await page.keyboard.press('Escape');
            }
            if (['landing', 'dashboard', 'malam-landing', 'sesi'].includes(name)) {
                // Uji gulir: tombol kembali ke atas muncul, lalu membawa halaman ke paling atas.
                await page.evaluate(() => window.scrollTo(0, 1200));
                await page.waitForTimeout(400);
                await page.screenshot({ path: `${out}/${vpName}-${name}-gulir.png` });
                await page.click('[data-to-top]');
                await page.waitForTimeout(1500);
                console.log(`${vpName} ${name} to-top scrollY=${await page.evaluate(() => window.scrollY)}`);
            }
        }
        await context.close();
    }
}
await browser.close();
