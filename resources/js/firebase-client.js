// Pin both SDK modules to the same official Firebase CDN release.
export async function createGoogleClient(config) {
    const [
        { getApps, initializeApp },
        {
            getAuth,
            initializeAuth,
            inMemoryPersistence,
            browserPopupRedirectResolver,
            GoogleAuthProvider,
            signInWithPopup,
            signOut,
        },
    ] = await Promise.all([
        import(/* @vite-ignore */ 'https://www.gstatic.com/firebasejs/13.1.0/firebase-app.js'),
        import(/* @vite-ignore */ 'https://www.gstatic.com/firebasejs/13.1.0/firebase-auth.js'),
    ]);
    let app = getApps().find((app) => app.name === 'pyturtle-google');
    const auth = app
        ? getAuth(app)
        : initializeAuth((app = initializeApp(config, 'pyturtle-google')), {
              persistence: inMemoryPersistence,
              popupRedirectResolver: browserPopupRedirectResolver,
          });
    const provider = new GoogleAuthProvider();
    provider.setCustomParameters({ prompt: 'select_account' });
    return {
        signIn: () => signInWithPopup(auth, provider),
        signOut: () => signOut(auth),
    };
}
