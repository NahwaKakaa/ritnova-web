{{-- Judul halaman panel peneliti. Pakai: @include('admin.partials.page-head', ['heading' => '...', 'sub' => '...']) --}}
<div class="min-w-0">
    <h1 class="page-title">{{ $heading }}</h1>
    @isset($sub)<p class="mt-1.5 max-w-2xl muted">{{ $sub }}</p>@endisset
</div>
