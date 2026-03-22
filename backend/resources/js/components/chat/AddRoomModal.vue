<template>
    <RpModal
        :open="open"
        variant="framed"
        size="md"
        max-height-class="max-h-[min(90vh,32rem)]"
        :z-index="80"
        :scroll-body="true"
        :aria-busy="creatingRoom"
        title="Додати кімнату"
        @close="onClose"
    >
        <div class="space-y-4 px-4 py-4">
            <p
                v-if="formError"
                role="alert"
                class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
            >
                {{ formError }}
            </p>

            <template v-if="canCreateRoom">
                <form class="space-y-3" @submit.prevent="submitCreate">
                    <div>
                        <label class="rp-label" for="rp-add-room-name">Назва</label>
                        <input
                            id="rp-add-room-name"
                            v-model="createName"
                            data-rp-initial-focus
                            type="text"
                            maxlength="191"
                            required
                            class="rp-input rp-focusable w-full text-sm"
                            :disabled="creatingRoom"
                            autocomplete="off"
                        />
                    </div>
                    <div>
                        <label class="rp-label" for="rp-add-room-topic">Опис (необов’язково)</label>
                        <textarea
                            id="rp-add-room-topic"
                            v-model="createTopic"
                            rows="2"
                            maxlength="2000"
                            class="rp-input rp-focusable w-full resize-y text-sm"
                            :disabled="creatingRoom"
                        />
                    </div>
                    <button
                        type="submit"
                        class="rp-focusable w-full rounded-md bg-[var(--rp-chat-sidebar-link)] px-3 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="creatingRoom || !createName.trim()"
                    >
                        {{ creatingRoom ? 'Створення…' : 'Створити кімнату' }}
                    </button>
                </form>
            </template>
            <p v-else-if="user && !user.guest && chatSettings" class="text-sm leading-relaxed text-[var(--rp-text-muted)]">
                Щоб створювати кімнати, потрібна кількість ваших публічних повідомлень
                <strong>більша</strong> за
                {{ chatSettings.room_create_min_public_messages }}
                (правила на сервері; див. налаштування чату).
            </p>
            <p v-else-if="user && !user.guest" class="text-sm text-[var(--rp-text-muted)]">
                Не вдалося завантажити умови створення кімнат.
            </p>
        </div>
    </RpModal>
</template>

<script>
import RpModal from '../RpModal.vue';

export default {
    name: 'AddRoomModal',
    components: { RpModal },
    props: {
        open: { type: Boolean, default: false },
        user: { type: Object, default: null },
        canCreateRoom: { type: Boolean, default: false },
        chatSettings: { type: Object, default: null },
        creatingRoom: { type: Boolean, default: false },
        formError: { type: String, default: '' },
    },
    data() {
        return {
            createName: '',
            createTopic: '',
        };
    },
    watch: {
        open(v) {
            if (v) {
                this.createName = '';
                this.createTopic = '';
            }
        },
    },
    methods: {
        onClose() {
            this.$emit('close');
        },
        submitCreate() {
            const name = (this.createName || '').trim();
            if (!name) {
                return;
            }
            const topic = (this.createTopic || '').trim();
            this.$emit('create-room', { room_name: name, topic: topic || null });
        },
    },
};
</script>
