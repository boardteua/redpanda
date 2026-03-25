# UI primitives (`Rp*`)

Перехідні Vue 2.7-компоненти поверх існуючих класів `rp-*` з `resources/css/partials/` (entry **`welcome.css`** / **`chat.css`**, **T135**). Бізнес-логіку не містять.

## Як додати примітив

1. Один кореневий елемент з базовим класом `rp-*`.
2. Ім’я: `Rp` + PascalCase (`RpButton`).
3. Події й `v-model` — як у нативного елемента, куди це доречно.
4. Додати глобальну реєстрацію в `resources/js/app.js` **або** локальний `components`, якщо примітив лише для одного важкого view.
5. Додаткові класи з батька (`class="text-sm"`) на `RpButton` / `RpPanel` зливаються з кореневими автоматично (fallthrough у Vue 2).

## Поточний набір

| Компонент    | Призначення |
|-------------|-------------|
| `RpButton`  | Кнопки: `variant` primary / ghost / danger / secondary / outline; `native-type`; `loading`, `disabled`. |
| `RpTextField` | Одиночний `<input>` (текст, search, number тощо); `v-model`; для `type="number"` емітить `number` або `null`. |
| `RpPanel`   | Блок `rp-panel`. |
| `RpBanner`  | Повідомлення `rp-banner` з `role="alert"`. |

`<select>` і `<textarea>` поки залишаються нативними з класами `rp-input` — за потреби окремі `RpSelect` / `RpTextarea`.
