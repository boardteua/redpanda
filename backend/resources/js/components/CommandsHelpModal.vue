<template>
    <RpModal
        :open="open"
        variant="framed"
        size="lg"
        max-height-class="max-h-[min(85vh,32rem)]"
        aria-labelledby="commands-help-title"
        :scroll-body="true"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                <h2 id="commands-help-title" class="text-base font-semibold text-[var(--rp-text)]">
                    Довідник slash-команд
                </h2>
                <RpCloseButton @click="close" />
            </div>
        </template>
        <pre
            class="whitespace-pre-wrap break-words px-4 py-3 font-sans text-xs leading-relaxed text-[var(--rp-text)]"
        >{{ body }}</pre>
        <template #footer>
            <p class="shrink-0 border-t border-[var(--rp-border-subtle)] px-4 py-2 text-xs text-[var(--rp-text-muted)]">
                Текст дзеркалить docs/board-te-ua/commands.md (статичний). Парсер slash-команд у чаті може відрізнятися.
            </p>
        </template>
    </RpModal>
</template>

<script>
import commandsMd from '../../markdown/board-te-ua-commands.md?raw';
import RpModal from './RpModal.vue';

export default {
    name: 'CommandsHelpModal',
    components: { RpModal },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            body: typeof commandsMd === 'string' ? commandsMd : String(commandsMd),
        };
    },
    methods: {
        close() {
            this.$emit('close');
        },
    },
};
</script>
