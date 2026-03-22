<template>
    <input
        :id="id"
        :type="type"
        :value="displayValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :autocomplete="autocomplete"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :inputmode="inputmode"
        :aria-invalid="invalid ? 'true' : undefined"
        :aria-describedby="describedBy || undefined"
        class="rp-input rp-focusable"
        v-on="forwardListeners"
    />
</template>

<script>
export default {
    name: 'RpTextField',
    model: {
        prop: 'value',
        event: 'input',
    },
    props: {
        id: {
            type: String,
            default: '',
        },
        value: {
            type: [String, Number],
            default: '',
        },
        type: {
            type: String,
            default: 'text',
        },
        placeholder: {
            type: String,
            default: '',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        required: {
            type: Boolean,
            default: false,
        },
        autocomplete: {
            type: String,
            default: '',
        },
        maxlength: {
            type: [String, Number],
            default: undefined,
        },
        min: {
            type: [String, Number],
            default: undefined,
        },
        max: {
            type: [String, Number],
            default: undefined,
        },
        inputmode: {
            type: String,
            default: '',
        },
        invalid: {
            type: Boolean,
            default: false,
        },
        describedBy: {
            type: String,
            default: '',
        },
    },
    computed: {
        displayValue() {
            const v = this.value;
            if (v === null || v === undefined) {
                return '';
            }
            return v;
        },
        forwardListeners() {
            const { input, ...rest } = this.$listeners;
            return {
                ...rest,
                input: this.onInput,
            };
        },
    },
    methods: {
        onInput(e) {
            const raw = e.target.value;
            if (this.type === 'number') {
                if (raw === '' || raw === '-') {
                    this.$emit('input', null);
                    return;
                }
                const n = Number(raw);
                this.$emit('input', Number.isNaN(n) ? null : n);
                return;
            }
            this.$emit('input', raw);
        },
    },
};
</script>
