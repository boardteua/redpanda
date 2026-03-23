import { getAuth0AccessTokenSilentlyOrNull } from './rpAuth0';

if (typeof window !== 'undefined' && window.axios) {
    window.axios.interceptors.request.use(
        async (config) => {
            const token = await getAuth0AccessTokenSilentlyOrNull();
            if (token) {
                const h = config.headers || {};
                if (typeof h.set === 'function') {
                    h.set('Authorization', `Bearer ${token}`);
                } else {
                    h.Authorization = `Bearer ${token}`;
                }
                config.headers = h;
            }
            return config;
        },
        (err) => Promise.reject(err),
    );
}
