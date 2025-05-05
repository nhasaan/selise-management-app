import { defineStore } from 'pinia';
import DepartmentService from '@/services/departmentService';

export const useDepartmentStore = defineStore('department', {
  state: () => ({
    departments: [],
    loading: false,
    error: null,
  }),

  actions: {
    // Fetch all departments
    async fetchDepartments() {
      this.loading = true;
      this.error = null;

      try {
        const data = await DepartmentService.fetchDepartments();
        this.departments = Array.isArray(data) ? data : data.data || [];
      } catch (error) {
        console.error('Error fetching departments:', error);
        this.error = error.message || 'Failed to fetch departments';
        this.departments = [];
      } finally {
        this.loading = false;
      }
    },

    // Create a new department
    async createDepartment(departmentData) {
      this.loading = true;
      this.error = null;

      try {
        const newDepartment = await DepartmentService.createDepartment(
          departmentData
        );

        if (newDepartment) {
          this.departments.push(newDepartment);
        }

        return newDepartment;
      } catch (error) {
        console.error('Error creating department:', error);
        this.error = error.message || 'Failed to create department';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Update an existing department
    async updateDepartment(id, departmentData) {
      this.loading = true;
      this.error = null;

      try {
        const updatedDepartment = await DepartmentService.updateDepartment(
          id,
          departmentData
        );

        if (updatedDepartment) {
          const index = this.departments.findIndex((dept) => dept.id === id);
          if (index !== -1) {
            this.departments[index] = updatedDepartment;
          }
        }

        return updatedDepartment;
      } catch (error) {
        console.error('Error updating department:', error);
        this.error = error.message || 'Failed to update department';
        throw error;
      } finally {
        this.loading = false;
      }
    },

    // Delete a department
    async deleteDepartment(id) {
      this.loading = true;
      this.error = null;

      try {
        await DepartmentService.deleteDepartment(id);
        this.departments = this.departments.filter((dept) => dept.id !== id);
        return true;
      } catch (error) {
        console.error('Error deleting department:', error);
        this.error = error.message || 'Failed to delete department';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },

  getters: {
    // Get department by ID
    getDepartmentById: (state) => (id) => {
      if (!id) return null;
      return state.departments.find((dept) => dept.id == id) || null;
    },

    // Get department names list
    getDepartmentNames: (state) => {
      return state.departments.map((dept) => dept.name);
    },

    // Check if data is being loaded
    isLoading: (state) => state.loading,

    // Get any error message
    errorMessage: (state) => state.error,
  },
});
