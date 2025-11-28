<template>
    <RouterLink
        :to="to"
        :class="[
            'flex items-center gap-3 rounded-xl px-3 py-2 transition',
            isActive ? 'bg-emerald-500/10 text-emerald-300' : 'text-slate-300 hover:bg-white/5 hover:text-emerald-200',
        ]"
        v-bind="$attrs"
    >
        <component :is="iconComponent" class="h-5 w-5" />
        <span>{{ label }}</span>
        <span
            v-if="badge"
            class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full bg-emerald-500/20 px-2 text-xs font-semibold text-emerald-300"
        >
            {{ badge }}
        </span>
    </RouterLink>
</template>

<script setup>
import * as icons from '@heroicons/vue/24/outline';
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const props = defineProps({
    to: {
        type: [String, Object],
        required: true,
    },
    icon: {
        type: String,
        default: 'CircleStackIcon',
    },
    label: {
        type: String,
        required: true,
    },
    exact: {
        type: Boolean,
        default: false,
    },
    badge: {
        type: [String, Number],
        default: null,
    },
});

const route = useRoute();

const iconComponent = computed(() => icons[props.icon] ?? icons.CircleStackIcon);

const isActive = computed(() => {
    if (typeof props.to === 'string') {
        return props.exact ? route.path === props.to : route.path.startsWith(props.to);
    }
    if (props.to?.name) {
        return props.exact ? route.name === props.to.name : route.name === props.to.name;
    }
    return false;
});
</script>
