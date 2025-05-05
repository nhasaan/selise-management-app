import { defineStore } from 'pinia';
import EmployeeService from '@/services/employeeService';

export const useEmployeeStore = defineStore('employee', {
  state: () => ({
    employees: [],
    totalEmployees: 0,
    currentPage: 1,
    lastPage: 1,
    loading: false,
    error: null,
    filters: {
      search: '',
      department: null,
      minSalary: null,
      maxSalary: null,
      sortBy: 'name',
      sortDirection: 'asc',
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
          search: this.filters.search,
          department_id: this.filters.department,
          min_salary: this.filters.minSalary,
          max_salary: this.filters.maxSalary,
          sort_by: this.filters.sortBy,
          sort_dir: this.filters.sortDirection,
        };

        const response = await EmployeeService.fetchEmployees(params);

        this.employees = response.data;
        this.totalEmployees = response.meta.total;
        this.currentPage = response.meta.current_page;
        this.lastPage = response.meta.last_page;
      } catch (error) {
        this.error = error;
        this.employees = [];
      } finally {
        this.loading = false;
      }
    },

    // Create a new employee
    async createEmployee(employeeData) {
      try {
        const newEmployee = await EmployeeService.createEmployee(employeeData);
        // Optionally, add the new employee to the list or refresh the list
        await this.fetchEmployees(this.currentPage);
        return newEmployee;
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    // Update an existing employee
    async updateEmployee(id, employeeData) {
      try {
        const updatedEmployee = await EmployeeService.updateEmployee(
          id,
          employeeData
        );
        // Update the employee in the list
        const index = this.employees.findIndex((emp) => emp.id === id);
        if (index !== -1) {
          this.employees[index] = updatedEmployee;
        }
        return updatedEmployee;
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    // Delete an employee
    async deleteEmployee(id) {
      try {
        await EmployeeService.deleteEmployee(id);
        // Remove the employee from the list or refresh the list
        await this.fetchEmployees(this.currentPage);
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    // Set filters
    setFilters(filters) {
      this.filters = { ...this.filters, ...filters };
      // Fetch employees with new filters
      this.fetchEmployees(1);
    },

    // Reset filters
    resetFilters() {
      this.filters = {
        search: '',
        department: null,
        minSalary: null,
        maxSalary: null,
        sortBy: 'name',
        sortDirection: 'asc',
      };
      this.fetchEmployees(1);
    },
  },

  getters: {
    // Getter for filtered and sorted employees
    filteredEmployees: (state) => state.employees,

    // Pagination details
    paginationInfo: (state) => ({
      currentPage: state.currentPage,
      totalPages: state.lastPage,
      totalEmployees: state.totalEmployees,
    }),
  },
});
