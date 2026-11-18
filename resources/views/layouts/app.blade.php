<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>
        @yield('title', 'Petualanganmu')
        · PyTurtle
    </title>
    <link rel="icon" href="/assets/ui/favicon.svg" type="image/svg+xml" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    data-user="{{ auth()->id() }}"
    data-version="{{ auth()->user()->adventure_version }}"
    class="{{ auth()->user()->setting?->reduced_motion ? 'reduce-motion' : '' }}"
>
    <a href="#main" class="skip-link">Lewati navigasi</a>
    <x-navbar />
    <main id="main" class="page-shell">
        @if (session('status'))
            <div class="notice success" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif
        @if (session('feature_notice'))
            <dialog class="feature-dialog" x-data x-init="$el.showModal()" aria-labelledby="feature-notice-title">
                <span class="eyebrow">AKSES FITUR</span>
                <h2 id="feature-notice-title">Bekal petualanganmu</h2>
                <p>{{ session('feature_notice') }}</p>
                @if (session('feature_continue_url'))
                    <a class="primary" href="{{ session('feature_continue_url') }}" autofocus>Buka Maze Quest</a>
                @else
                    <form method="dialog"><button class="primary" autofocus>Mengerti</button></form>
                @endif
            </dialog>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer">
        <span class="brand-small">PyTurtle</span><span>Langkah kecil. Petualangan besar.</span
        ><span>Belajar • Jelajah • Tumbuh</span>
    </footer>
    @stack('scripts')
</body>
</html>
