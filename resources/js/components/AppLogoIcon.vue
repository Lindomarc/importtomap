<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 150" width="150" height="150">
    <!-- Fundo do ícone -->
    <rect 
        x="0" 
        y="0" 
        width="150" 
        height="150" 
        rx="16" 
        ry="16" 
        fill="#ffffff" 
        stroke="#dddddd" 
        stroke-width="1"
    />
    <!-- Sombra suave -->
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
        <feGaussianBlur in="SourceAlpha" stdDeviation="4" result="blur"/>
        <feOffset in="blur" dx="2" dy="4" result="offsetBlur"/>
        <feMerge>
            <feMergeNode in="offsetBlur"/>
            <feMergeNode in="SourceGraphic"/>
        </feMerge>
    </filter>
    <rect 
        x="0" 
        y="0" 
        width="150" 
        height="150" 
        rx="16" 
        ry="16" 
        fill="#ffffff" 
        filter="url(#shadow)"
    />
    <!-- Texto "TD" -->
    <text 
        x="75" 
        y="85" 
        font-family="Arial, sans-serif" 
        font-size="40" 
        font-weight="bold" 
        text-anchor="middle" 
        fill="#333333"
    >
        TD
    </text>
</svg>
</template>
