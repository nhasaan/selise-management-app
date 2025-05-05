import { defineStore } from 'pinia';
import EmployeeService from '@/services/employeeService';

export const useEmployeeStore = defineStore('employee', {
  state: () => ({
    employees: [],
    totalEmployees: 0,
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    loading: false,
    error: null,
    filters: {
      search: '',
      department_id: null,
      min_salary: null,
      max_salary: null,
      sort_by: 'name',
      sort_dir: 'asc',
    },
  }),

  actions: {
    // Fetch employees with optional filters
    async fetchEmployees(page = 1) {
      this.loading = true;
      this.error = null;

      try {
        const params = {
          page,
          per_page: this.perPage,
          search: this.filters.search || undefined,
          department_id: this.filters.department_id || undefined,
          min_salary: this.filters.min_salary || undefined,
          max_salary: this.filters.max_salary || undefined,
          sort_by: this.filters.sort_by || 'name',
          sort_dir: this.filters.sort_dir || 'asc',
        };

        // Remove undefined values to avoid sending empty params
        Object.keys(params).forEach(
          (key) => params[key] === undefined && delete params[key]
        );

        const response = await EmployeeService.fetchEmployees(params);

        if (response && response.data) {
          this.employees = response.data;

          if (response.meta) {
            this.totalEmployees = response.meta.total || 0;
            this.currentPage = response.meta.current_page || 1;
            this.lastPage = response.meta.last_page || 1;
            this.perPage = response.meta.per_page || 15;
          }
        } else {
          console.warn('Invalid response format from API:', response);
          this.employees = [];
          this.totalEmployees = 0;
        }
      } catch (error) {
        console.error('Error fetching employees:', error);
        this.error = error.message || 'Failed to fetch employees';
        this.employees = [];
        this.totalEmployees = 0;
      } finally {
        this.loading = false;
      }
    },

    // Create a new employee
    async createEmployee(employeeData) {
      this.loading = true;
      this.error = null;

      try {
        const newEmployee = await EmployeeService.createEmployee(employeeData);
        await this.fetchEmployees(this.currentPage);
        return newEmployee;
      } catch (error) {
        console.error('Error creating employee:', error);
        this.error = error.message || 'Failed to create employee';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Update an existing employee
    async updateEmployee(id, employeeData) {
      this.loading = true;
      this.error = null;

      try {
        const updatedEmployee = await EmployeeService.updateEmployee(
          id,
          employeeData
        );

        // Update the employee in the list if it exists
        const index = this.employees.findIndex((emp) => emp.id === id);
        if (index !== -1) {
          this.employees[index] = updatedEmployee;
        }

        return updatedEmployee;
      } catch (error) {
        console.error('Error updating employee:', error);
        this.error = error.message || 'Failed to update employee';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Delete an employee
    async deleteEmployee(id) {
      this.loading = true;
      this.error = null;

      try {
        await EmployeeService.deleteEmployee(id);

        // Refresh the list to ensure accurate data
        await this.fetchEmployees(
          // If we're on the last page and it's now empty, go to previous page
          this.employees.length === 1 && this.currentPage > 1
            ? this.currentPage - 1
            : this.currentPage
        );

        return true;
      } catch (error) {
        console.error('Error deleting employee:', error);
        this.error = error.message || 'Failed to delete employee';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Set filters
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
      // Fetch employees with new filters (always start from page 1 when filters change)
      this.fetchEmployees(1);
    },

    // Reset filters
    resetFilters() {
      this.filters = {
        search: '',
        department_id: null,
        min_salary: null,
        max_salary: null,
        sort_by: 'name',
        sort_dir: 'asc',
      };
      this.fetchEmployees(1);
    },
  },

  getters: {
    // Pagination details
    paginationInfo: (state) => ({
      currentPage: state.currentPage,
      totalPages: state.lastPage,
      totalEmployees: state.totalEmployees,
      perPage: state.perPage,
    }),

    // Check if data is being loaded
    isLoading: (state) => state.loading,

    // Get any error message
    errorMessage: (state) => state.error,
  },
});
