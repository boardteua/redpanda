import { createAuth0Client } from '@auth0/auth0-spa-js';

const LANDING_KEY = 'rp_landing_auth0';

/** @type {import('@auth0/auth0-spa-js').Auth0Client | null | undefined} undefined = ще не ініціалізовано */
let resolvedClient;

/**
 * Зберігає публічні поля Auth0 з GET /api/v1/landing (без секретів).
 *
 * @param {Record<string, unknown>|null|undefined} auth0
 */
export function cacheAuth0FromLandingPayload(auth0) {
    if (auth0 && typeof auth0 === 'object') {
        try {
            sessionStorage.setItem(LANDING_KEY, JSON.stringify(auth0));
            resolvedClient = undefined;
        } catch {
            /* */
        }
    }
}

/**
 * @returns {Promise<import('@auth0/auth0-spa-js').Auth0Client | null>}
 */
export async function ensureAuth0Client() {
    if (resolvedClient !== undefined) {
        return resolvedClient;
    }

    let raw;
    try {
        raw = sessionStorage.getItem(LANDING_KEY);
    } catch {
        raw = null;
    }
    if (!raw) {
        resolvedClient = null;

        return null;
    }
    let cfg;
    try {
        cfg = JSON.parse(raw);
    } catch {
        resolvedClient = null;

        return null;
    }
    if (!cfg || !cfg.enabled || !cfg.domain || !cfg.client_id) {
        resolvedClient = null;

        return null;
    }

    resolvedClient = await createAuth0Client({
        domain: String(cfg.domain),
        clientId: String(cfg.client_id),
        authorizationParams: {
            redirect_uri: `${window.location.origin}/auth/callback`,
            audience: cfg.audience ? String(cfg.audience) : undefined,
        },
        cacheLocation: 'localstorage',
        useRefreshTokens: true,
    });

    return resolvedClient;
}

/** Скидання кешу клієнта (наприклад після повного logout). */
export function resetAuth0ClientCache() {
    resolvedClient = undefined;
}

/**
 * @returns {Promise<string|null>}
 */
export async function getAuth0AccessTokenSilentlyOrNull() {
    const c = await ensureAuth0Client();
    if (!c) {
        return null;
    }
    try {
        return await c.getTokenSilently();
    } catch {
        return null;
    }
}

export async function logoutAuth0IfLoggedIn() {
    const c = await ensureAuth0Client();
    if (!c) {
        return;
    }
    try {
        if (await c.isAuthenticated()) {
            await c.logout({
                logoutParams: { returnTo: window.location.origin },
            });
        }
    } catch {
        /* */
    }
    resetAuth0ClientCache();
}

/**
 * Якщо у sessionStorage ще немає auth0-конфігу — тягнемо публічний GET /api/v1/landing (без Bearer).
 */
export async function ensureAuth0BootstrapFromLandingApi() {
    let has;
    try {
        has = Boolean(sessionStorage.getItem(LANDING_KEY));
    } catch {
        has = false;
    }
    if (!has && typeof window !== 'undefined' && window.axios) {
        try {
            const { data } = await window.axios.get('/api/v1/landing');
            const a0 = data && data.data && data.data.auth0;
            cacheAuth0FromLandingPayload(a0);
        } catch {
            /* */
        }
    }
    return ensureAuth0Client();
}
