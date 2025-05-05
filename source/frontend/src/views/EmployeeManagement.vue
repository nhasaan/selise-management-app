<template>
    <div class="employee-management">
      <h1 class="text-3xl font-bold mb-6">Employee Management</h1>
  
      <!-- Search and Filter Component -->
      <search-filter 
        @filter-change="handleFilterChange"
      />
  
      <!-- Add Employee Button -->
      <button 
        @click="openEmployeeModal" 
        class="btn btn-primary mb-4"
      >
        Add New Employee
      </button>
  
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
      <pagination 
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalEmployees"
        @page-change="changePage"
      />
  
      <!-- Employee Modal/Form -->
      <div v-if="showModal" class="modal">
        <employee-form
          :employee="currentEmployee"
          @submit="saveEmployee"
          @cancel="closeModal"
        />
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useEmployeeStore } from '@/stores/employeeStore'
  import { useDepartmentStore } from '@/stores/departmentStore'
  
  // Import components
  import SearchFilter from '@/components/SearchFilter.vue'
  import EmployeeList from '@/components/EmployeeList.vue'
  import EmployeeForm from '@/components/EmployeeForm.vue'
  import Pagination from '@/components/Pagination.vue'
  
  // Stores
  const employeeStore = useEmployeeStore()
  const departmentStore = useDepartmentStore()
  
  // Reactive State
  const showModal = ref(false)
  const currentEmployee = ref(null)
  const sortKey = ref('name')
  const sortDirection = ref('asc')
  const currentPage = ref(1)
  
  // Computed Properties
  const employees = computed(() => employeeStore.employees)
  const totalPages = computed(() => employeeStore.paginationInfo.totalPages)
  const totalEmployees = computed(() => employeeStore.paginationInfo.totalEmployees)
  
  // Methods
  const openEmployeeModal = () => {
    currentEmployee.value = null
    showModal.value = true
  }
  
  const editEmployee = (employee) => {
    currentEmployee.value = { ...employee }
    showModal.value = true
  }
  
  const saveEmployee = async (employeeData) => {
    try {
      if (currentEmployee.value) {
        // Update existing employee
        await employeeStore.updateEmployee(
          currentEmployee.value.id, 
          employeeData
        )
      } else {
        // Create new employee
        await employeeStore.createEmployee(employeeData)
      }
      closeModal()
      fetchEmployees()
    } catch (error) {
      // Handle error (show notification, etc.)
      console.error('Error saving employee:', error)
    }
  }
  
  const deleteEmployee = async (employeeId) => {
    if (confirm('Are you sure you want to delete this employee?')) {
      try {
        await employeeStore.deleteEmployee(employeeId)
        fetchEmployees()
      } catch (error) {
        console.error('Error deleting employee:', error)
      }
    }
  }
  
  const closeModal = () => {
    showModal.value = false
    currentEmployee.value = null
  }
  
  const handleFilterChange = (filters) => {
    employeeStore.setFilters(filters)
  }
  
  const handleSort = (key) => {
    if (sortKey.value === key) {
      // Toggle sort direction
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
      sortKey.value = key
      sortDirection.value = 'asc'
    }
    
    // Update store filters
    employeeStore.setFilters({
      sortBy: sortKey.value,
      sortDirection: sortDirection.value
    })
  }
  
  const changePage = (page) => {
    currentPage.value = page
    fetchEmployees(page)
  }
  
  const fetchEmployees = (page = 1) => {
    employeeStore.fetchEmployees(page)
  }
  
  // Lifecycle Hooks
  onMounted(() => {
    // Fetch initial data
    fetchEmployees()
    departmentStore.fetchDepartments()
  })
  </script>