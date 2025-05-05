<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    v-if="isVisible"
  >
    <div
      class="spinner w-16 h-16 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"
    ></div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
});
</script>

<style scoped>
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
