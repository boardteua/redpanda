/**
 * Вивід у консоль серверного trace Рудої Панди, якщо бекенд додав meta.ruda_panda_llm_debug.
 *
 * Ключ у JSON з’являється лише за CHAT_RUDA_PANDA_LLM_DEBUG_CONSOLE + (local або APP_DEBUG),
 * тому без цього прапорця в проді «шуму» не буде. Раніше лог ішов лише при import.meta.env.DEV —
 * при завантаженні зібраних асетів без HMR прапорець був false, хоча відповідь уже містила meta.
 */

const LABEL = '[Ruda Panda · LLM debug]';

/**
 * @param {unknown} data - тіло відповіді axios (корінь: data + meta, як у JsonResource)
 */
export function logRudaPandaLlmDebugFromApiResponse(data) {
    if (!data || typeof data !== 'object') {
        return;
    }
    const meta = /** @type {{ meta?: Record<string, unknown> }} */ (data).meta;
    const dbg = meta && meta.ruda_panda_llm_debug;
    if (dbg == null || typeof dbg !== 'object') {
        return;
    }

    const postId = dbg.post_id != null ? String(dbg.post_id) : '?';
    // eslint-disable-next-line no-console
    console.groupCollapsed(`${LABEL} post_id=${postId}`);
    // eslint-disable-next-line no-console
    console.log('trigger:', dbg.trigger);
    // eslint-disable-next-line no-console
    console.log('intent:', dbg.intent);
    const fc = dbg.followup_classifier;
    if (fc && typeof fc === 'object') {
        // eslint-disable-next-line no-console
        console.log('класифікатор «відповідати без згадки» (follow-up):', fc);
    }
    // eslint-disable-next-line no-console
    console.log('dispatched:', dbg.dispatched);
    if (dbg.note) {
        // eslint-disable-next-line no-console
        console.log('note:', dbg.note);
    }
    // eslint-disable-next-line no-console
    console.log('повний об’єкт:', dbg);
    // eslint-disable-next-line no-console
    console.groupEnd();
}
