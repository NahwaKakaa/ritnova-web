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
];

const browser = await chromium.launch();
for (const [vpName, viewport] of Object.entries(viewports)) {
    for (const plan of plans) {
        const context = await browser.newContext({ viewport, deviceScaleFactor: 1, locale: 'id-ID', reducedMotion: 'reduce' });
        const page = await context.newPage();
        if (plan.user) {
            await page.goto(`${base}/masuk`);
            await page.fill('#username', plan.user);
            await page.fill('#password', 'password');
            await Promise.all([page.waitForLoadState('networkidle'), page.click('button[type=submit], form button')]);
        }
        for (const [name, path] of Object.entries(plan.pages)) {
            const res = await page.goto(`${base}${path}`, { waitUntil: 'networkidle' });
            await page.waitForTimeout(300);
            await page.screenshot({ path: `${out}/${vpName}-${name}.png`, fullPage: true });
            console.log(`${vpName} ${name} ${path} -> ${res?.status()}`);
        }
        await context.close();
    }
}
await browser.close();
