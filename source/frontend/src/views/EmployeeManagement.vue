<template>
  <div class="employee-management">
    <h1 class="text-3xl font-bold mb-6">Employee Management</h1>

    <!-- Search and Filter Component -->
    <search-filter @filter-change="handleFilterChange" />

    <!-- Add Employee Button -->
    <div class="flex justify-between items-center mb-4">
      <button @click="openEmployeeModal" class="btn btn-primary">
        Add New Employee
      </button>

      <div v-if="loading" class="text-primary-500">Loading...</div>
    </div>

    <!-- Employee List Component -->
    <employee-list
      :employees="employees"
      :sort-key="sortKey"
      :sort-direction="sortDirection"
      @edit="editEmployee"
      @delete="deleteEmployee"
      @sort="handleSort"
    />

    <!-- Pagination Component -->
    <div class="mt-6">
      <pagination
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalEmployees"
        :per-page="perPage"
        @page-change="changePage"
      />
    </div>

    <!-- Employee Modal/Form -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">
            {{ currentEmployee ? 'Edit Employee' : 'Add New Employee' }}
          </h2>
          <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <employee-form
          :employee="currentEmployee"
          @submit="saveEmployee"
          @cancel="closeModal"
        />
      </div>
    </div>

    <!-- Loading Spinner -->
    <loading-spinner :is-visible="loading" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useEmployeeStore } from '@/store/employeeStore';
import { useDepartmentStore } from '@/store/departmentStore';

// Import components
import SearchFilter from '@/components/SearchFilter.vue';
import EmployeeList from '@/components/EmployeeList.vue';
import EmployeeForm from '@/components/EmployeeForm.vue';
import Pagination from '@/components/Pagination.vue';
import LoadingSpinner from '@/components/LoadingSpinner.vue';

// Stores
const employeeStore = useEmployeeStore();
const departmentStore = useDepartmentStore();

// Reactive State
const showModal = ref(false);
const currentEmployee = ref(null);
const sortKey = ref('name');
const sortDirection = ref('asc');
const currentPage = ref(1);
const perPage = ref(15);

// Computed Properties
const employees = computed(() => employeeStore.employees);
const totalPages = computed(() => employeeStore.paginationInfo.totalPages);
const totalEmployees = computed(
  () => employeeStore.paginationInfo.totalEmployees
);
const loading = computed(() => employeeStore.isLoading);

// Methods
const openEmployeeModal = () => {
  currentEmployee.value = null;
  showModal.value = true;
};

const editEmployee = (employee) => {
  currentEmployee.value = { ...employee };
  showModal.value = true;
};

const saveEmployee = async (employeeData) => {
  try {
    if (currentEmployee.value) {
      // Update existing employee
      await employeeStore.updateEmployee(
        currentEmployee.value.id,
        employeeData
      );
    } else {
      // Create new employee
      await employeeStore.createEmployee(employeeData);
    }
    closeModal();
    await fetchEmployees();
  } catch (error) {
    console.error('Error saving employee:', error);
    // Could add toast notification here
  }
};

const deleteEmployee = async (employeeId) => {
  if (confirm('Are you sure you want to delete this employee?')) {
    try {
      await employeeStore.deleteEmployee(employeeId);
      await fetchEmployees();
    } catch (error) {
      console.error('Error deleting employee:', error);
      // Could add toast notification here
    }
  }
};

const closeModal = () => {
  showModal.value = false;
  currentEmployee.value = null;
};

const handleFilterChange = (filters) => {
  employeeStore.setFilters(filters);
};

const handleSort = (key) => {
  if (sortKey.value === key) {
    // Toggle sort direction
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDirection.value = 'asc';
  }

  // Update store filters
  employeeStore.setFilters({
    sort_by: sortKey.value,
    sort_dir: sortDirection.value,
  });
};

const changePage = (page) => {
  currentPage.value = page;
  fetchEmployees(page);
};

const fetchEmployees = async (page = currentPage.value) => {
  await employeeStore.fetchEmployees(page);
};

// Lifecycle Hooks
onMounted(async () => {
  // Fetch initial data
  try {
    await departmentStore.fetchDepartments();
    await fetchEmployees();
  } catch (error) {
    console.error('Error initializing employee management:', error);
  }
});
</script>
