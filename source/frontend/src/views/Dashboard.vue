<template>
  <div class="dashboard">
    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Total Employees Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-2">Total Employees</h3>
        <p class="text-2xl font-bold">{{ totalEmployees }}</p>
      </div>

      <!-- Departments Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-2">Departments</h3>
        <department-select
          v-model="selectedDepartment"
          label="Select Department"
        />
      </div>

      <!-- Recent Employees Section -->
      <employee-list :employees="recentEmployees" :read-only="true" />
    </div>

    <!-- Charts and Additional Widgets -->
    <!-- ... -->
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useEmployeeStore } from '@/stores/employeeStore';
import { useDepartmentStore } from '@/stores/departmentStore';

// Import components
import DepartmentSelect from '@/components/DepartmentSelect.vue';
import EmployeeList from '@/components/EmployeeList.vue';

// Stores
const employeeStore = useEmployeeStore();
const departmentStore = useDepartmentStore();

// Reactive State
const selectedDepartment = ref('');

// Computed Properties
const totalEmployees = computed(() => employeeStore.totalEmployees);
const recentEmployees = computed(() =>
  employeeStore.employees
    .sort((a, b) => new Date(b.joined_date) - new Date(a.joined_date))
    .slice(0, 5)
);

// Lifecycle Hooks
onMounted(() => {
  employeeStore.fetchEmployees();
  departmentStore.fetchDepartments();
});
</script>
