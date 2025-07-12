<template>
  <select
    :id="id"
    :value="modelValue"
    @input="handleInput"
    :class="[
      'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors',
      'file:border-0 file:bg-transparent file:text-sm file:font-medium',
      'placeholder:text-muted-foreground',
      'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
      'disabled:cursor-not-allowed disabled:opacity-50',
      'appearance-none bg-no-repeat bg-right bg-[length:16px_16px] pr-8',
      className
    ]"
    :style="{
      backgroundImage: `url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQgNkw4IDEwTDEyIDYiIHN0cm9rZT0iIzZBNzI4MCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8L3N2Zz4K')`
    }"
    v-bind="$attrs"
  >
    <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
    <option
      v-for="option in options"
      :key="option.value"
      :value="option.value"
    >
      {{ option.label }}
    </option>
  </select>
</template>

<script setup lang="ts">
interface Option {
  label: string;
  value: string;
}

interface Props {
  modelValue: string;
  options: Option[];
  placeholder?: string;
  id?: string;
  className?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Selecione uma opção',
  className: ''
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const handleInput = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  emit('update:modelValue', target.value);
};
</script>