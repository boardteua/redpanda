<template>
    <portal>
        <div
            v-show="items.length"
            class="rp-toast-stack"
            style="z-index: 230"
            aria-label="Сповіщення інтерфейсу"
        >
            <div
                v-for="item in items"
                :key="item.id"
                class="rp-toast"
                :class="{
                    'rp-toast--error': item.kind === 'error',
                    'rp-toast--warning': item.kind === 'warning',
                    'rp-toast--progress': item.kind === 'progress',
                }"
            >
                <div
                    class="rp-toast__main"
                    :role="item.kind === 'error' ? 'alert' : 'status'"
                    :aria-live="item.kind === 'error' ? 'assertive' : 'polite'"
                >
                    <p class="rp-toast__text">{{ item.message }}</p>
                    <div v-if="item.kind === 'progress'" class="rp-toast__progress-wrap">
                        <div
                            v-if="item.percent != null"
                            class="rp-toast__progress-track"
                            role="progressbar"
                            :aria-valuenow="item.percent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div class="rp-toast__progress-fill" :style="{ width: `${item.percent}%` }" />
                        </div>
                        <div v-else class="rp-toast__progress-indeterminate" aria-hidden="true">
                            <span class="rp-toast__progress-indeterminate-inner" />
                        </div>
                    </div>
                </div>
                <button
                    v-if="item.kind !== 'progress'"
                    type="button"
                    class="rp-toast__close rp-focusable"
                    aria-label="Закрити сповіщення"
                    @click="dismiss(item.id)"
                >
                    <svg class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </portal>
</template>

<script>
import { dismissToast, rpToastState } from '../../utils/rpToastStack';

export default {
    name: 'RpToastStack',
    computed: {
        items() {
            return rpToastState.items;
        },
    },
    methods: {
        dismiss(id) {
            dismissToast(id);
        },
    },
};
</script>
