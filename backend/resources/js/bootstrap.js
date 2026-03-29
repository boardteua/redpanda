import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

/** T162: прострочений CSRF — один retry після /sanctum/csrf-cookie (узгоджено з умовним ensureSanctum). */
window.axios.interceptors.response.use(
    (r) => r,
    async (err) => {
        const cfg = err && err.config;
        const st = err.response && err.response.status;
        if (st !== 419 || !cfg || cfg.__rpCsrfRetry) {
            return Promise.reject(err);
        }
        cfg.__rpCsrfRetry = true;
        await window.axios.get('/sanctum/csrf-cookie');

        return window.axios(cfg);
    },
);

void import('./lib/auth0Axios.js');
