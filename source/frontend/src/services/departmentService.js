import axios from 'axios';

const API_BASE_URL = '/api/departments';

class DepartmentService {
  // Fetch all departments
  async fetchDepartments() {
    try {
      const response = await axios.get(API_BASE_URL);
      return response.data;
    } catch (error) {
      console.error('Error fetching departments:', error);
      throw error;
    }
  }

  // Create a new department
  async createDepartment(departmentData) {
    try {
      const response = await axios.post(API_BASE_URL, departmentData);
      return response.data;
    } catch (error) {
      console.error('Error creating department:', error);
      throw error;
    }
  }

  // Update an existing department
  async updateDepartment(id, departmentData) {
    try {
      const response = await axios.put(`${API_BASE_URL}/${id}`, departmentData);
      return response.data;
    } catch (error) {
      console.error('Error updating department:', error);
      throw error;
    }
  }

  // Delete a department
  async deleteDepartment(id) {
    try {
      await axios.delete(`${API_BASE_URL}/${id}`);
    } catch (error) {
      console.error('Error deleting department:', error);
      throw error;
    }
  }
}

export default new DepartmentService();
