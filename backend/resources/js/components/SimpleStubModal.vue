<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[75] flex items-end justify-center bg-black/40 p-4 sm:items-center"
            role="presentation"
            @click.self="close"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="w-full max-w-md rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] p-4 shadow-xl"
                tabindex="-1"
                @keydown.stop="onPanelKeydown"
            >
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                    {{ title }}
                </h2>
                <p class="mt-2 text-sm text-[var(--rp-text-muted)]">
                    {{ body }}
                </p>
                <button type="button" class="rp-focusable rp-btn rp-btn-primary mt-4 w-full" @click="close">
                    Закрити
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script>
let stubSeq = 0;

export default {
    name: 'SimpleStubModal',
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        title: {
            type: String,
            required: true,
        },
        body: {
            type: String,
            required: true,
        },
    },
    data() {
        stubSeq += 1;

        return {
            titleId: `stub-modal-title-${stubSeq}`,
        };
    },
    watch: {
        open(v) {
            if (v) {
                document.addEventListener('keydown', this.onDocKeydown);
                this.$nextTick(() => {
                    const p = this.$refs.panel;
                    if (p && typeof p.focus === 'function') {
                        p.focus();
                    }
                });
            } else {
                document.removeEventListener('keydown', this.onDocKeydown);
            }
        },
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.onDocKeydown);
    },
    methods: {
        close() {
            this.$emit('close');
        },
        onDocKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
            }
        },
        onPanelKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
            }
        },
    },
};
</script>
