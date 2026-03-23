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
                <RpCloseButton
                    v-if="item.kind !== 'progress'"
                    variant="toast"
                    aria-label="Закрити сповіщення"
                    @click="dismiss(item.id)"
                />
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
