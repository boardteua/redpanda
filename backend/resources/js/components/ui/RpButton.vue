<template>
    <button
        :type="nativeType"
        :disabled="disabled || loading"
        :aria-busy="loading ? 'true' : undefined"
        class="rp-focusable rp-btn"
        :class="[variantClass, { 'rp-btn--pending': loading }]"
        v-on="$listeners"
    >
        <slot />
    </button>
</template>

<script>
const VARIANTS = ['primary', 'ghost', 'danger', 'secondary', 'outline'];

const VARIANT_CLASS = {
    primary: 'rp-btn-primary',
    ghost: 'rp-btn-ghost',
    danger: 'rp-btn-danger',
    secondary: 'rp-btn-secondary',
    outline: 'rp-btn-outline',
};

export default {
    name: 'RpButton',
    props: {
        /** primary | ghost | danger | secondary | outline */
        variant: {
            type: String,
            default: 'primary',
            validator: (v) => VARIANTS.includes(v),
        },
        nativeType: {
            type: String,
            default: 'button',
            validator: (v) => ['button', 'submit', 'reset'].includes(v),
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        variantClass() {
            return VARIANT_CLASS[this.variant] || VARIANT_CLASS.primary;
        },
    },
};
</script>
