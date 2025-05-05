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
        this.departments = await DepartmentService.fetchDepartments();
      } catch (error) {
        this.error = error;
        this.departments = [];
      } finally {
        this.loading = false;
      }
    },

    // Create a new department
    async createDepartment(departmentData) {
      try {
        const newDepartment = await DepartmentService.createDepartment(
          departmentData
        );
        this.departments.push(newDepartment);
        return newDepartment;
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    // Update an existing department
    async updateDepartment(id, departmentData) {
      try {
        const updatedDepartment = await DepartmentService.updateDepartment(
          id,
          departmentData
        );
        const index = this.departments.findIndex((dept) => dept.id === id);
        if (index !== -1) {
          this.departments[index] = updatedDepartment;
        }
        return updatedDepartment;
      } catch (error) {
        this.error = error;
        throw error;
      }
    },

    // Delete a department
    async deleteDepartment(id) {
      try {
        await DepartmentService.deleteDepartment(id);
        this.departments = this.departments.filter((dept) => dept.id !== id);
      } catch (error) {
        this.error = error;
        throw error;
      }
    },
  },

  getters: {
    // Get department by ID
    getDepartmentById: (state) => (id) => {
      return state.departments.find((dept) => dept.id === id);
    },

    // Get department names list
    getDepartmentNames: (state) => {
      return state.departments.map((dept) => dept.name);
    },
  },
});
