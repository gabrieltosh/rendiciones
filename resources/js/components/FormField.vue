<template>
    <div class="form-field" :class="{ 'form-field--error': hasError }">
        <label v-if="label" class="form-label" :for="inputId">
            {{ label }}
            <span v-if="required" class="text-negative" aria-hidden="true">*</span>
        </label>
        <slot />
        <div v-if="hasError" class="form-field__error" role="alert">
            <q-icon name="eva-alert-circle-outline" size="14px" />
            <span>{{ message }}</span>
        </div>
        <div v-else-if="hint" class="form-field__hint">{{ hint }}</div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    label: String,
    required: Boolean,
    hint: String,
    // Mensajes de validación de Laravel para el campo (arreglo o texto)
    error: [Array, String],
    inputId: String,
});

const message = computed(() =>
    Array.isArray(props.error) ? props.error[0] : props.error
);
const hasError = computed(() => !!message.value);
</script>
