# T83 — Рекомендація: один origin vs піддомен `api.`

## Контекст

Redpanda — **Laravel** (Blade `spa` + REST `/api/v1/...`) і **Vue SPA** на тому ж хості. Авторизація: **Sanctum SPA** (cookie + CSRF) для логіну/пароля та гостя; **Auth0 JWT** (Bearer) — додатковий шлях для соц-логіну (**T76**).

## Рекомендована конфігурація для поточного продукту

**Один canonical origin** (наприклад `https://chat.example.com`), без обов’язкового окремого DNS для REST.

### Чому

1. **Sanctum SPA** очікує спільний сайт для cookie сесії, `SameSite`, `SANCTUM_STATEFUL_DOMAINS` і `SESSION_DOMAIN`. Розділення на `app.` і `api.` ускладнює CORS, куки та CSRF без явної вигоди для одного веб-клієнта.
2. **Auth0**: поле **API Identifier** (`AUTH0_AUDIENCE`) — це **логічний** `aud` у JWT, часто у формі URL; **не** потрібен реальний HTTP-сервер на цьому імені. **Allowed Callback URLs** і **Web Origins** у Dashboard мають відповідати **реальному** origin застосунку (той самий хост, що й SPA).
3. **Простіший TLS і WAF**: один віртуальний хост, один набір правил; WebSocket (Reverb) зазвичай проксують з того ж домену або підшляху згідно [Laravel Reverb](https://laravel.com/docs/reverb).

### Коли варто `api.*`

- Мобільні нативні клієнти з іншим політикою cookie.
- Жорстке розділення команд / rate limits між «фасадом» і API.
- Вимога безпеки або комплаєнсу на окремий API perimeter.

У таких випадках слід окремо проєктувати CORS, refresh-токени та (за потреби) відмову від cookie-Sanctum на користь чистого Bearer для того клієнта.

## Висновок для redpanda

Для **веб-чату v2** зафіксувати **single origin** як базовий сценарій; піддомен `api.` не є обов’язковим для Auth0 audience і не повинен плутатися з **API Identifier** у tenant.
