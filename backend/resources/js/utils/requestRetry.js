export function isLikelyNetworkError(error) {
    if (!error || error.response) {
        return false;
    }
    const code = error.code;
    if (code === 'ECONNABORTED' || code === 'ERR_NETWORK') {
        return true;
    }
    const message = typeof error.message === 'string' ? error.message : '';

    return message === 'Network Error';
}

/** T162: один повтор для ідемпотентного POST після тимчасового network drop. */
export async function postWithOneNetworkRetry(postOnce) {
    try {
        return await postOnce();
    } catch (error) {
        if (!isLikelyNetworkError(error)) {
            throw error;
        }

        return postOnce();
    }
}
