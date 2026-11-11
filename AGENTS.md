# RITNOVA — catatan proyek untuk agen

- Laravel 13, PHP 8.3. Bahasa UI: Indonesia. Zona waktu penelitian: Asia/Makassar (WITA), semua jadwal dihitung dari jam server.
- Logika penelitian ada di `app/Domain` (PHP murni, tanpa framework) dan diuji di `tests/Unit`. Jangan pindahkan aturan penguncian sesi atau rumus SJL ke view/JS.
- Data belum cukup untuk SJL harus `null`, bukan 0. Data mentah diary tidak boleh ditimpa tanpa revisi (`sleep_diary_revisions`).
- Konten intervensi hanya untuk `study_group = intervention` (middleware `group:intervention`).
- Butir instrumen penelitian diisi peneliti; jangan mengarang butir atau skala.
- Commit tanpa baris atribusi AI (tanpa "Co-Authored-By: Claude" / "Claude-Session").
- Status fitur: `docs/STATUS.md`. Deploy: `docs/DEPLOY.md`.
