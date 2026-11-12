{{-- Kredensial baru: hanya tampil sekali setelah impor atau reset kata sandi --}}
@if (! empty($credentials))
    @php
        $csv = "participant_code,password\n".collect($credentials)->map(fn ($c) => $c[0].','.$c[1])->implode("\n");
    @endphp
    <section class="panel-night mb-6 p-5 sm:p-6" aria-labelledby="cred-title">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="cred-title" class="font-bold">Kata sandi sementara</h2>
                <p class="text-sm text-white/65">Hanya ditampilkan sekali. Unduh atau catat sekarang, lalu berikan langsung ke peserta.</p>
            </div>
            <a href="data:text/csv;charset=utf-8,{{ rawurlencode($csv) }}" download="kredensial-peserta-{{ now()->format('Ymd-Hi') }}.csv" class="btn-on-night btn-sm shrink-0"><x-icon name="download" class="h-4 w-4" />Unduh CSV</a>
        </div>
        <div class="max-h-64 overflow-auto rounded-2xl bg-white/5 ring-1 ring-white/10">
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-white/50"><th class="px-4 py-2.5 font-semibold">Kode</th><th class="px-4 py-2.5 font-semibold">Kata sandi</th></tr></thead>
                <tbody>
                    @foreach ($credentials as [$code, $password])
                        <tr class="border-t border-white/5"><td class="px-4 py-2 font-semibold">{{ $code }}</td><td class="px-4 py-2 font-mono tracking-wider">{{ $password }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
