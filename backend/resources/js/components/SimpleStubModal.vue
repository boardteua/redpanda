<template>
    <RpModal :open="open" variant="card" :title="title" @close="close">
        <p class="mt-2 text-sm text-[var(--rp-text-muted)]">
            {{ body }}
        </p>
        <button type="button" class="rp-focusable rp-btn rp-btn-primary mt-4 w-full" @click="close">
            Закрити
        </button>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';

export default {
    name: 'SimpleStubModal',
    components: { RpModal },
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
    methods: {
        close() {
            this.$emit('close');
        },
    },
};
</script>
