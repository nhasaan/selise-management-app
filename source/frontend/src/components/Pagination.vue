<template>
  <div class="pagination flex justify-between items-center">
    <div class="text-gray-600">
      Showing
      {{ startItem }} -
      {{ endItem }}
      of {{ totalItems }} items
    </div>

    <div class="flex space-x-2">
      <!-- First Page Button -->
      <button
        @click="goToFirstPage"
        :disabled="currentPage === 1"
        class="btn btn-secondary px-3 py-2 rounded disabled:opacity-50"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="{2}"
            d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
          />
        </svg>
      </button>

      <!-- Previous Page Button -->
      <button
        @click="previousPage"
        :disabled="currentPage === 1"
        class="btn btn-secondary px-3 py-2 rounded disabled:opacity-50"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="{2}"
            d="M15 19l-7-7 7-7"
          />
        </svg>
      </button>

      <!-- Page Number Buttons -->
      <div class="flex space-x-1">
        <button
          v-for="pageNumber in visiblePageNumbers"
          :key="pageNumber"
          @click="goToPage(pageNumber)"
          :class="[
            'btn px-3 py-2 rounded',
            currentPage === pageNumber
              ? 'bg-primary-500 text-white'
              : 'btn-secondary',
          ]"
        >
          {{ pageNumber }}
        </button>
      </div>

      <!-- Next Page Button -->
      <button
        @click="nextPage"
        :disabled="currentPage === totalPages"
        class="btn btn-secondary px-3 py-2 rounded disabled:opacity-50"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="{2}"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </button>

      <!-- Last Page Button -->
      <button
        @click="goToLastPage"
        :disabled="currentPage === totalPages"
        class="btn btn-secondary px-3 py-2 rounded disabled:opacity-50"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="{2}"
            d="M13 5l7 7-7 7M5 5l7 7-7 7"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, defineProps, defineEmits } from 'vue';

// Props
const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  totalPages: {
    type: Number,
    required: true,
  },
  totalItems: {
    type: Number,
    required: true,
  },
  perPage: {
    type: Number,
    default: 15,
  },
});

// Emits
const emit = defineEmits(['page-change']);

// Computed Properties
const startItem = computed(() => {
  return (props.currentPage - 1) * props.perPage + 1;
});

const endItem = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.totalItems);
});

const visiblePageNumbers = computed(() => {
  const range = 2; // Number of pages to show on either side of current page
  let pages = [];

  // Always include first and last page
  pages.push(1);

  // Calculate range around current page
  const start = Math.max(2, props.currentPage - range);
  const end = Math.min(props.totalPages - 1, props.currentPage + range);

  // Add pages around current page
  for (let i = start; i <= end; i++) {
    if (!pages.includes(i)) {
      pages.push(i);
    }
  }

  // Always include last page if it's not already in the list
  if (!pages.includes(props.totalPages)) {
    pages.push(props.totalPages);
  }

  // Sort pages
  return pages.sort((a, b) => a - b);
});

// Methods
const previousPage = () => {
  if (props.currentPage > 1) {
    emit('page-change', props.currentPage - 1);
  }
};

const nextPage = () => {
  if (props.currentPage < props.totalPages) {
    emit('page-change', props.currentPage + 1);
  }
};

const goToPage = (pageNumber) => {
  emit('page-change', pageNumber);
};

const goToFirstPage = () => {
  emit('page-change', 1);
};

const goToLastPage = () => {
  emit('page-change', props.totalPages);
};
</script>
