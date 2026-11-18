<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>
        @yield('title')
        · PyTurtle
    </title>
    <link rel="icon" href="/assets/ui/favicon.svg" type="image/svg+xml" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
    <main class="auth-layout">
        <section class="auth-form">
            <a class="brand" href="/"
                ><span class="brand-mark">p<span>y</span></span
                >PyTurtle<span class="brand-dot">.</span></a>
            <img
                class="auth-mobile-shiha"
                src="/assets/characters/shiha/portrait.svg"
                alt="Shiha, teman penjelajahmu"
            />
            <div class="auth-content">
                @if (session('status'))
                    <div class="notice success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="notice error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </div>
            <p class="auth-footer">Belajar Python. Temukan petualanganmu.</p>
        </section>
        <section class="auth-illustration">
            <div class="auth-caption">
                <span class="eyebrow">SHIHA’S TURTLE ADVENTURE</span>
                <h2>Sebuah peta.<br />Seribu kemungkinan.</h2>
                <p>Temani Shiha menjelajahi hutan.<br />Tulis kode pertamamu, satu langkah pada satu waktu.</p>
            </div>
            <img
                class="forest-auth"
                src="/assets/nature/forest-daylight.svg"
                alt="Shiha dengan topi penjelajah, baju oranye, dan ransel kecil di hutan yang cerah"
            /><span class="illustration-note">Petualangan hebat dimulai dengan rasa ingin tahu.</span>
        </section>
    </main>
</body>
</html>
