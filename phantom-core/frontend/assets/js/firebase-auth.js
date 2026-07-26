// AETHER — Firebase Authentication Module
// Handles: Email/Password sign-up/sign-in, Google OAuth, auth state, sign-out
// Firebase v11 Modular SDK via CDN (ES module)

import { initializeApp } from 'https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js';
import {
    getAuth,
    createUserWithEmailAndPassword,
    signInWithEmailAndPassword,
    signInWithPopup,
    GoogleAuthProvider,
    signOut,
    onAuthStateChanged,
    updateProfile,
    sendPasswordResetEmail
} from 'https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js';

// ═══════════════════════════════════════════════════════════════
// FIREBASE CONFIGURATION
// Replace with your own Firebase project config from:
// https://console.firebase.google.com → Project Settings → General → Your apps
// ═══════════════════════════════════════════════════════════════
const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_PROJECT_ID.firebasestorage.app",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID"
};

// ═══════════════════════════════════════════════════════════════
// INITIALIZE FIREBASE
// ═══════════════════════════════════════════════════════════════
let app, auth, googleProvider;
let isFirebaseReady = false;

try {
    app = initializeApp(firebaseConfig);
    auth = getAuth(app);
    googleProvider = new GoogleAuthProvider();
    isFirebaseReady = true;
    console.log('[AETHER Auth] Firebase initialized successfully');
} catch (err) {
    console.warn('[AETHER Auth] Firebase init failed — running in demo mode:', err.message);
}

// ═══════════════════════════════════════════════════════════════
// AUTH STATE MANAGEMENT
// ═══════════════════════════════════════════════════════════════

/**
 * Current authenticated user object (or null)
 */
let currentUser = null;

/**
 * Auth state change listeners — other modules can subscribe
 */
const authListeners = [];

/**
 * Subscribe to auth state changes
 * @param {Function} callback - Called with (user) on every auth change
 * @returns {Function} Unsubscribe function
 */
function onAuthChange(callback) {
    authListeners.push(callback);
    // Immediately call with current state
    if (currentUser !== undefined) {
        callback(currentUser);
    }
    return () => {
        const idx = authListeners.indexOf(callback);
        if (idx > -1) authListeners.splice(idx, 1);
    };
}

function notifyListeners(user) {
    currentUser = user;
    authListeners.forEach(fn => {
        try { fn(user); } catch (e) { console.error('[AETHER Auth] Listener error:', e); }
    });
}

// Start listening for auth state changes
if (isFirebaseReady) {
    onAuthStateChanged(auth, (user) => {
        notifyListeners(user);
        console.log('[AETHER Auth] Auth state:', user ? `Logged in as ${user.email || user.displayName}` : 'Logged out');
    });
}

// ═══════════════════════════════════════════════════════════════
// EMAIL / PASSWORD — SIGN UP
// ═══════════════════════════════════════════════════════════════

/**
 * Create a new account with email and password
 * @param {string} email
 * @param {string} password
 * @param {string} fullName - Display name
 * @returns {Promise<{success: boolean, user?: object, error?: string}>}
 */
async function signUpWithEmail(email, password, fullName) {
    if (!isFirebaseReady) return { success: false, error: 'Firebase not configured. Please set up your Firebase project.' };

    try {
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);

        // Set display name
        if (fullName) {
            await updateProfile(userCredential.user, { displayName: fullName });
        }

        console.log('[AETHER Auth] Account created:', userCredential.user.email);
        return { success: true, user: userCredential.user };
    } catch (err) {
        const errorMap = {
            'auth/email-already-in-use': 'This email is already registered. Try signing in instead.',
            'auth/invalid-email': 'Please enter a valid email address.',
            'auth/weak-password': 'Password must be at least 6 characters.',
            'auth/operation-not-allowed': 'Email/password sign-up is not enabled in Firebase Console.',
            'auth/too-many-requests': 'Too many attempts. Please try again later.',
        };
        const message = errorMap[err.code] || err.message;
        console.error('[AETHER Auth] Sign up failed:', err.code);
        return { success: false, error: message };
    }
}

// ═══════════════════════════════════════════════════════════════
// EMAIL / PASSWORD — SIGN IN
// ═══════════════════════════════════════════════════════════════

/**
 * Sign in with email and password
 * @param {string} email
 * @param {string} password
 * @returns {Promise<{success: boolean, user?: object, error?: string}>}
 */
async function signInWithEmail(email, password) {
    if (!isFirebaseReady) return { success: false, error: 'Firebase not configured. Please set up your Firebase project.' };

    try {
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        console.log('[AETHER Auth] Signed in:', userCredential.user.email);
        return { success: true, user: userCredential.user };
    } catch (err) {
        const errorMap = {
            'auth/user-not-found': 'No account found with this email.',
            'auth/wrong-password': 'Incorrect password. Please try again.',
            'auth/invalid-email': 'Please enter a valid email address.',
            'auth/user-disabled': 'This account has been disabled.',
            'auth/too-many-requests': 'Too many failed attempts. Please try again later.',
            'auth/invalid-credential': 'Invalid email or password. Please try again.',
            'auth/operation-not-allowed': 'Email/password sign-in is not enabled in Firebase Console.',
            'auth/network-request-failed': 'Network error. Check your connection.',
        };
        const message = errorMap[err.code] || err.message;
        console.error('[AETHER Auth] Sign in failed:', err.code);
        return { success: false, error: message };
    }
}

// ═══════════════════════════════════════════════════════════════
// GOOGLE OAUTH — SIGN IN / SIGN UP
// ═══════════════════════════════════════════════════════════════

/**
 * Sign in (or sign up) with Google via popup
 * @returns {Promise<{success: boolean, user?: object, error?: string}>}
 */
async function signInWithGoogle() {
    if (!isFirebaseReady) return { success: false, error: 'Firebase not configured. Please set up your Firebase project.' };

    try {
        const result = await signInWithPopup(auth, googleProvider);
        const isNewUser = result.user.metadata.creationTime === result.user.metadata.lastSignInTime;
        console.log('[AETHER Auth] Google sign-in:', isNewUser ? 'New account created' : 'Existing account signed in');
        return { success: true, user: result.user, isNewUser };
    } catch (err) {
        const errorMap = {
            'auth/popup-closed-by-user': 'Sign-in popup was closed. Please try again.',
            'auth/popup-blocked': 'Popup was blocked by your browser. Allow popups for this site.',
            'auth/cancelled-popup-request': 'Sign-in was cancelled.',
            'auth/network-request-failed': 'Network error. Check your connection.',
            'auth/operation-not-allowed': 'Google sign-in is not enabled in Firebase Console.',
        };
        const message = errorMap[err.code] || err.message;
        console.error('[AETHER Auth] Google sign-in failed:', err.code);
        return { success: false, error: message };
    }
}

// ═══════════════════════════════════════════════════════════════
// PASSWORD RESET
// ═══════════════════════════════════════════════════════════════

/**
 * Send password reset email
 * @param {string} email
 * @returns {Promise<{success: boolean, error?: string}>}
 */
async function resetPassword(email) {
    if (!isFirebaseReady) return { success: false, error: 'Firebase not configured.' };

    try {
        await sendPasswordResetEmail(auth, email);
        console.log('[AETHER Auth] Password reset email sent to:', email);
        return { success: true };
    } catch (err) {
        const errorMap = {
            'auth/user-not-found': 'No account found with this email.',
            'auth/invalid-email': 'Please enter a valid email address.',
        };
        const message = errorMap[err.code] || err.message;
        return { success: false, error: message };
    }
}

// ═══════════════════════════════════════════════════════════════
// SIGN OUT
// ═══════════════════════════════════════════════════════════════

/**
 * Sign out the current user
 * @returns {Promise<{success: boolean, error?: string}>}
 */
async function logOut() {
    if (!isFirebaseReady) return { success: false, error: 'Firebase not configured.' };

    try {
        await signOut(auth);
        console.log('[AETHER Auth] Signed out');
        return { success: true };
    } catch (err) {
        console.error('[AETHER Auth] Sign out failed:', err);
        return { success: false, error: err.message };
    }
}

// ═══════════════════════════════════════════════════════════════
// DEMO MODE (when Firebase is not configured)
// ═══════════════════════════════════════════════════════════════

// If Firebase config has placeholder values, enable demo mode
const isDemo = !isFirebaseReady || firebaseConfig.apiKey === 'YOUR_API_KEY';

const demoUser = {
    uid: 'demo-user-001',
    email: 'demo@aethershoes.com',
    displayName: 'Demo User',
    photoURL: null,
    metadata: {
        creationTime: new Date().toISOString(),
        lastSignInTime: new Date().toISOString()
    },
    providerData: [{ providerId: 'password', email: 'demo@aethershoes.com' }]
};

// Override functions for demo mode
const demoSignUp = async (email, password, fullName) => {
    demoUser.email = email;
    demoUser.displayName = fullName || 'AETHER Member';
    notifyListeners(demoUser);
    localStorage.setItem('aether_demo_user', JSON.stringify(demoUser));
    return { success: true, user: demoUser };
};

const demoSignIn = async (email, password) => {
    demoUser.email = email;
    demoUser.displayName = email.split('@')[0];
    notifyListeners(demoUser);
    localStorage.setItem('aether_demo_user', JSON.stringify(demoUser));
    return { success: true, user: demoUser };
};

const demoGoogleSignIn = async () => {
    demoUser.displayName = 'AETHER Member';
    demoUser.email = 'member@aethershoes.com';
    notifyListeners(demoUser);
    localStorage.setItem('aether_demo_user', JSON.stringify(demoUser));
    return { success: true, user: demoUser, isNewUser: false };
};

const demoSignOut = async () => {
    notifyListeners(null);
    localStorage.removeItem('aether_demo_user');
    return { success: true };
};

const demoResetPassword = async (email) => {
    console.log('[AETHER Auth Demo] Password reset email would be sent to:', email);
    return { success: true };
};

// ═══════════════════════════════════════════════════════════════
// RESTORE SESSION from localStorage (demo mode)
// ═══════════════════════════════════════════════════════════════

function restoreSession() {
    if (!isDemo) return;
    const saved = localStorage.getItem('aether_demo_user');
    if (saved) {
        try {
            const user = JSON.parse(saved);
            notifyListeners(user);
        } catch (e) { /* ignore */ }
    }
}

// ═══════════════════════════════════════════════════════════════
// EXPORT PUBLIC API
// ═══════════════════════════════════════════════════════════════

window.AETHERAuth = {
    signUp: isDemo ? demoSignUp : signUpWithEmail,
    signIn: isDemo ? demoSignIn : signInWithEmail,
    signInWithGoogle: isDemo ? demoGoogleSignIn : signInWithGoogle,
    signOut: isDemo ? demoSignOut : logOut,
    resetPassword: isDemo ? demoResetPassword : resetPassword,
    onAuthChange,
    getCurrentUser: () => currentUser,
    isDemo,
    restoreSession
};

// Restore session on load
restoreSession();

// Export for ES module import
export { AETHERAuth };
