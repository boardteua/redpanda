<template>
    <div
        v-if="hasAny"
        class="space-y-2 border-t border-[var(--rp-border-subtle)] pt-3 mt-2"
    >
        <p v-if="countryLine">
            <span class="font-medium">Країна: </span>{{ countryLine }}
        </p>
        <p v-if="regionLine">
            <span class="font-medium">Регіон: </span>{{ regionLine }}
        </p>
        <p v-if="ageLine">
            <span class="font-medium">Вік: </span>{{ ageLine }}
        </p>
        <p v-if="sexLine">
            <span class="font-medium">Стать: </span>{{ sexLine }}
        </p>
        <p v-if="occupationLine">
            <span class="font-medium">Рід занять: </span>{{ occupationLine }}
        </p>
        <p v-if="aboutText" class="space-y-1">
            <span class="font-medium block">Про мене:</span>
            <span class="block whitespace-pre-wrap break-words text-[var(--rp-text)]">{{ aboutText }}</span>
        </p>
    </div>
</template>

<script>
import { countryLabelUk, sexLabelUk } from '../utils/userInfoProfileLabels.js';

export default {
    name: 'UserInfoProfileBlock',
    props: {
        profile: {
            type: Object,
            default: null,
        },
    },
    computed: {
        countryLine() {
            const pr = this.profile;

            return pr ? countryLabelUk(pr.country) : null;
        },
        regionLine() {
            const pr = this.profile;
            if (!pr || pr.region == null || String(pr.region).trim() === '') {
                return null;
            }

            return String(pr.region);
        },
        ageLine() {
            const pr = this.profile;
            if (!pr || pr.age == null || pr.age === '' || !Number.isFinite(Number(pr.age))) {
                return null;
            }

            return String(pr.age);
        },
        sexLine() {
            const pr = this.profile;

            return pr ? sexLabelUk(pr.sex) : null;
        },
        occupationLine() {
            const pr = this.profile;
            if (!pr || pr.occupation == null || String(pr.occupation).trim() === '') {
                return null;
            }

            return String(pr.occupation);
        },
        aboutText() {
            const pr = this.profile;
            if (!pr || pr.about == null || String(pr.about).trim() === '') {
                return null;
            }

            return String(pr.about);
        },
        hasAny() {
            return (
                this.countryLine
                || this.regionLine
                || this.ageLine
                || this.sexLine
                || this.occupationLine
                || this.aboutText
            );
        },
    },
};
</script>
