import apiClient from './apiInterceptor';

const API_ENDPOINT = '/departments';

class DepartmentService {
  // Fetch all departments
  async fetchDepartments() {
    try {
      const response = await apiClient.get(API_ENDPOINT);
      return response.data;
    } catch (error) {
      console.error('Error fetching departments:', error.response || error);
      throw error;
    }
  }

  // Create a new department
  async createDepartment(departmentData) {
    try {
      const response = await apiClient.post(API_ENDPOINT, departmentData);
      return response.data;
    } catch (error) {
      console.error('Error creating department:', error.response || error);
      throw error;
    }
  }

  // Update an existing department
  async updateDepartment(id, departmentData) {
    try {
      const response = await apiClient.put(
        `${API_ENDPOINT}/${id}`,
        departmentData
      );
      return response.data;
    } catch (error) {
      console.error('Error updating department:', error.response || error);
      throw error;
    }
  }

  // Delete a department
  async deleteDepartment(id) {
    try {
      const response = await apiClient.delete(`${API_ENDPOINT}/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error deleting department:', error.response || error);
      throw error;
    }
  }
}

export default new DepartmentService();
