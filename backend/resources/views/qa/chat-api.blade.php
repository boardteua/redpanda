<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>T04 — Chat REST QA (local)</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 52rem; margin: 2rem auto; padding: 0 1rem; }
        pre { background: #111827; color: #e5e7eb; padding: 1rem; overflow: auto; font-size: 0.8rem; border-radius: 0.375rem; }
        button { margin: 0.25rem 0.5rem 0.25rem 0; padding: 0.5rem 0.75rem; cursor: pointer; }
        .row { margin: 1rem 0; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 600; }
        input { width: 100%; max-width: 28rem; padding: 0.35rem 0.5rem; }
    </style>
</head>
<body>
    <h1>T04 — Chat REST (QA, лише local)</h1>
    <p>Сторінка для ручної перевірки в браузері: Sanctum cookie + CSRF, потім API v1.</p>

    <div class="row">
        <button type="button" id="btn-csrf">1. CSRF cookie</button>
        <button type="button" id="btn-guest">2. Гість</button>
        <button type="button" id="btn-rooms">3. GET rooms</button>
        <button type="button" id="btn-messages">4. GET messages (room 1)</button>
        <button type="button" id="btn-post">5. POST message</button>
        <button type="button" id="btn-dup">6. Повтор POST (idempotency)</button>
    </div>

    <div class="row">
        <label for="uuid">client_message_id (UUID)</label>
        <input id="uuid" type="text" value="" autocomplete="off">
    </div>

    <pre id="out">Натисніть кнопки по порядку або «Повний цикл».</pre>
    <div class="row">
        <button type="button" id="btn-all">Повний цикл</button>
    </div>

    <script>
        const out = document.getElementById('out');
        const uuidInput = document.getElementById('uuid');

        function log(msg, obj) {
            const line = typeof obj !== 'undefined' ? msg + '\n' + JSON.stringify(obj, null, 2) : msg;
            out.textContent = line + '\n\n' + out.textContent.slice(0, 8000);
        }

        function xsrfToken() {
            const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
            if (!m) return null;
            return decodeURIComponent(m[1]);
        }

        async function api(method, url, body) {
            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            const t = xsrfToken();
            if (t) headers['X-XSRF-TOKEN'] = t;
            const opt = { method, credentials: 'include', headers };
            if (body !== undefined) {
                headers['Content-Type'] = 'application/json';
                opt.body = JSON.stringify(body);
            }
            const res = await fetch(url, opt);
            let data = null;
            const text = await res.text();
            try { data = text ? JSON.parse(text) : null; } catch (e) { data = text; }
            return { ok: res.ok, status: res.status, data };
        }

        document.getElementById('btn-csrf').onclick = async () => {
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
            log('CSRF cookie set. XSRF present:', !!xsrfToken());
        };

        document.getElementById('btn-guest').onclick = async () => {
            const r = await api('POST', '/api/v1/auth/guest', { user_name: null });
            log('POST guest ' + r.status, r.data);
        };

        document.getElementById('btn-rooms').onclick = async () => {
            const r = await api('GET', '/api/v1/rooms');
            log('GET rooms ' + r.status, r.data);
        };

        document.getElementById('btn-messages').onclick = async () => {
            const r = await api('GET', '/api/v1/rooms/1/messages?limit=10');
            log('GET messages ' + r.status, r.data);
        };

        function ensureUuid() {
            let u = uuidInput.value.trim();
            if (!u) {
                u = crypto.randomUUID();
                uuidInput.value = u;
            }
            return u;
        }

        document.getElementById('btn-post').onclick = async () => {
            const id = ensureUuid();
            const r = await api('POST', '/api/v1/rooms/1/messages', {
                message: '/me тестує REST',
                client_message_id: id,
            });
            log('POST message ' + r.status, r.data);
        };

        document.getElementById('btn-dup').onclick = async () => {
            const id = ensureUuid();
            const r = await api('POST', '/api/v1/rooms/1/messages', {
                message: 'ignored on duplicate',
                client_message_id: id,
            });
            log('POST duplicate ' + r.status, r.data);
        };

        document.getElementById('btn-all').onclick = async () => {
            out.textContent = '';
            await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
            log('1. CSRF ok', !!xsrfToken());
            let r = await api('POST', '/api/v1/auth/guest', {});
            log('2. Guest ' + r.status, r.data);
            r = await api('GET', '/api/v1/rooms');
            log('3. Rooms ' + r.status, r.data);
            const id = crypto.randomUUID();
            uuidInput.value = id;
            r = await api('POST', '/api/v1/rooms/1/messages', {
                message: 'Hello from full cycle',
                client_message_id: id,
            });
            log('4. Post ' + r.status, r.data);
            r = await api('POST', '/api/v1/rooms/1/messages', {
                message: 'duplicate',
                client_message_id: id,
            });
            log('5. Dup ' + r.status, r.data);
            r = await api('GET', '/api/v1/rooms/1/messages?limit=5');
            log('6. Messages ' + r.status, r.data);
        };
    </script>
</body>
</html>
