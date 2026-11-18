@props(['link' => false])
@php
    $firebase = ['apiKey' => config('services.firebase.api_key'), 'authDomain' => config('services.firebase.auth_domain'), 'projectId' => config('services.firebase.project_id'), 'appId' => config('services.firebase.app_id')];
    $available = ! in_array(null, $firebase, true) && ! in_array('', $firebase, true);
@endphp
<div x-data="firebaseGoogle(@js($firebase))">
    <form x-ref="googleForm" action="{{ route($link ? 'google.link' : 'google.login') }}" method="POST">
        @csrf
        <input x-ref="idToken" type="hidden" name="id_token" autocomplete="off" />
        <button
            class="google-button full"
            type="button"
            @click="login()"
            :disabled="! ready || busy"
            :aria-busy="busy"
            disabled
            @if (! $available) aria-describedby="google-unavailable" @endif
        >
            <svg width="19" height="19" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M21.6 12.2c0-.7-.1-1.4-.2-2.1H12v4h5.4a4.6 4.6 0 0 1-2 3v2.6h3.3c1.9-1.8 2.9-4.4 2.9-7.5z" />
                <path fill="#34A853" d="M12 22c2.7 0 5-.9 6.7-2.4l-3.3-2.6c-.9.6-2 .9-3.4.9-2.6 0-4.9-1.8-5.7-4.1H2.9v2.7A10 10 0 0 0 12 22z" />
                <path fill="#FBBC05" d="M6.3 13.8a6 6 0 0 1 0-3.6V7.5H2.9a10 10 0 0 0 0 9l3.4-2.7z" />
                <path fill="#EA4335" d="M12 6.1c1.5 0 2.8.5 3.8 1.5l2.9-2.9A9.6 9.6 0 0 0 12 2a10 10 0 0 0-9.1 5.5l3.4 2.7C7.1 7.9 9.4 6.1 12 6.1z" />
            </svg>
            <span x-show="! busy">{{ $link ? 'Hubungkan Google' : 'Lanjutkan dengan Google' }}</span
            ><span x-show="busy" x-cloak>Menghubungkan…</span>
        </button>
    </form>
    <p class="notice error" role="alert" x-show="error" x-text="error" x-cloak></p>
    @if (! $available)
        <p id="google-unavailable" class="small muted google-note">
            Login Google belum diaktifkan. Gunakan email dan kata sandi untuk sementara.
        </p>
    @endif
</div>
