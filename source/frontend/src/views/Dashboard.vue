<template>
  <div class="dashboard">
    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <!-- Total Employees Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-2">Total Employees</h3>
        <p class="text-2xl font-bold">{{ totalEmployees }}</p>
      </div>

      <!-- Departments Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-2">Departments</h3>
        <p class="text-2xl font-bold">{{ departmentCount }}</p>
      </div>

      <!-- Average Salary Card -->
      <div class="bg-white shadow-md rounded-lg p-6">
        <h3 class="text-lg font-semibold mb-2">Average Salary</h3>
        <p class="text-2xl font-bold">{{ avgSalary }}</p>
      </div>
    </div>

    <!-- Recent Employees Section -->
    <div class="bg-white shadow-md rounded-lg p-6">
      <h3 class="text-lg font-semibold mb-4">Recent Employees</h3>
      <table class="w-full">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 text-left">Name</th>
            <th class="px-4 py-2 text-left">Email</th>
            <th class="px-4 py-2 text-left">Department</th>
            <th class="px-4 py-2 text-left">Joined Date</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="employee in recentEmployees"
            :key="employee.id"
            class="border-b"
          >
            <td class="px-4 py-2">{{ employee.name }}</td>
            <td class="px-4 py-2">{{ employee.email }}</td>
            <td class="px-4 py-2">{{ employee.department?.name }}</td>
            <td class="px-4 py-2">{{ formatDate(employee.joined_date) }}</td>
          </tr>
          <tr v-if="!recentEmployees.length">
            <td colspan="4" class="text-center py-4 text-gray-500">
              No employees found
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useEmployeeStore } from '@/store/employeeStore';
import { useDepartmentStore } from '@/store/departmentStore';

// Stores
const employeeStore = useEmployeeStore();
const departmentStore = useDepartmentStore();

// Computed Properties
const totalEmployees = computed(() => employeeStore.totalEmployees || 0);
const departmentCount = computed(
  () => departmentStore.departments?.length || 0
);
const recentEmployees = computed(() => {
  if (!employeeStore.employees || !employeeStore.employees.length) return [];

  return [...employeeStore.employees]
    .sort((a, b) => new Date(b.joined_date) - new Date(a.joined_date))
    .slice(0, 5);
});

const avgSalary = computed(() => {
  if (!employeeStore.employees || !employeeStore.employees.length) return 0;

  const total = employeeStore.employees.reduce(
    (sum, emp) => sum + (emp.salary || 0),
    0
  );
  return `$${(total / employeeStore.employees.length).toFixed(2)}`;
});

// Format date method
const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Lifecycle Hooks
onMounted(async () => {
  // Load data on component mount
  try {
    await employeeStore.fetchEmployees();
    await departmentStore.fetchDepartments();
  } catch (error) {
    console.error('Error loading dashboard data:', error);
  }
});
</script>
