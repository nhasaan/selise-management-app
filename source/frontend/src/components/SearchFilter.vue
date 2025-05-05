<template>
  <div class="search-filter grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <!-- Search Input -->
    <div class="relative">
      <input
        v-model="searchTerm"
        @input="emitFilters"
        placeholder="Search by name, email or designation..."
        class="input w-full pl-10 border border-gray-300 rounded-md px-3 py-2"
      />
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5 absolute left-3 top-3 text-gray-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="{2}"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
        />
      </svg>
    </div>

    <!-- Department Filter -->
    <select
      v-model="selectedDepartment"
      @change="emitFilters"
      class="input w-full border border-gray-300 rounded-md px-3 py-2"
    >
      <option value="">All Departments</option>
      <option
        v-for="department in departments"
        :key="department.id"
        :value="department.id"
      >
        {{ department.name }}
      </option>
    </select>

    <!-- Salary Range -->
    <div class="flex space-x-2">
      <input
        v-model.number="minSalary"
        @input="emitFilters"
        type="number"
        placeholder="Min Salary"
        class="input w-full border border-gray-300 rounded-md px-3 py-2"
      />
      <input
        v-model.number="maxSalary"
        @input="emitFilters"
        type="number"
        placeholder="Max Salary"
        class="input w-full border border-gray-300 rounded-md px-3 py-2"
      />
    </div>

    <!-- Reset Filters Button -->
    <button
      @click="resetFilters"
      class="btn btn-secondary flex items-center justify-center space-x-2"
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
          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
        />
      </svg>
      <span>Reset</span>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, defineProps, defineEmits, onMounted } from 'vue';
import { useDepartmentStore } from '@/store/departmentStore';

// Props
const props = defineProps({
  initialSearch: {
    type: String,
    default: '',
  },
  initialDepartment: {
    type: [String, Number],
    default: '',
  },
  initialMinSalary: {
    type: Number,
    default: null,
  },
  initialMaxSalary: {
    type: Number,
    default: null,
  },
});

// Emits
const emit = defineEmits(['filter-change']);

// Departments Store
const departmentStore = useDepartmentStore();
const departments = computed(() => departmentStore.departments || []);

// Reactive State
const searchTerm = ref(props.initialSearch);
const selectedDepartment = ref(props.initialDepartment);
const minSalary = ref(props.initialMinSalary);
const maxSalary = ref(props.initialMaxSalary);

// Methods
const emitFilters = () => {
  emit('filter-change', {
    search: searchTerm.value,
    department_id: selectedDepartment.value || null,
    min_salary: minSalary.value || null,
    max_salary: maxSalary.value || null,
  });
};

const resetFilters = () => {
  searchTerm.value = '';
  selectedDepartment.value = '';
  minSalary.value = null;
  maxSalary.value = null;

  emit('filter-change', {
    search: '',
    department_id: null,
    min_salary: null,
    max_salary: null,
  });
};

// Load departments on mount if not already loaded
onMounted(async () => {
  if (!departments.value.length) {
    await departmentStore.fetchDepartments();
  }
});
</script>
