const messages = {
    'auth/popup-closed-by-user': 'Login Google dibatalkan. Kamu bisa mencoba lagi.',
    'auth/cancelled-popup-request': 'Selesaikan satu jendela login Google terlebih dahulu.',
    'auth/popup-blocked':
        'Browser memblokir jendela Google. Izinkan pop-up untuk situs ini lalu coba lagi.',
    'auth/unauthorized-domain': 'Domain aplikasi belum diizinkan di Firebase. Hubungi pengelola.',
    'auth/operation-not-allowed': 'Login Google belum diaktifkan di Firebase. Hubungi pengelola.',
    'auth/network-request-failed': 'Koneksi terganggu. Periksa internet lalu coba lagi.',
    'auth/account-exists-with-different-credential':
        'Email ini memakai metode masuk Firebase lain. Hubungi pengelola untuk menghubungkannya.',
};

export default function firebaseGoogle(config) {
    let client;
    let destroyed = false;
    return {
        ready: false,
        busy: false,
        error: '',
        async init() {
            if (!config.apiKey || !config.authDomain || !config.projectId || !config.appId) return;
            try {
                const { createGoogleClient } = await import('./firebase-client');
                if (destroyed) return;
                client = await createGoogleClient(config);
                if (destroyed) return;
                this.ready = true;
            } catch {
                this.error =
                    'Login Google belum bisa dimuat. Muat ulang halaman atau gunakan email dan kata sandi.';
            }
        },
        destroy() {
            destroyed = true;
        },
        async login() {
            if (!this.ready || this.busy) return;
            this.busy = true;
            this.error = '';
            try {
                // The SDK is loaded during init, so the popup opens directly from the click.
                const result = await client.signIn();
                const token = await result.user.getIdToken(true);
                await client.signOut();
                if (destroyed) return;
                this.$refs.idToken.value = token;
                this.$refs.googleForm.requestSubmit();
            } catch (error) {
                if (!destroyed) {
                    this.$refs.idToken.value = '';
                    this.error =
                        messages[error.code] ??
                        'Google belum dapat dihubungkan. Coba lagi atau gunakan email dan kata sandi.';
                    this.busy = false;
                }
                await client.signOut().catch(() => {});
            }
        },
    };
}
