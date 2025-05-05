import apiClient from './apiInterceptor';

const API_ENDPOINT = '/employees';

class EmployeeService {
  // Fetch employees with optional filters
  async fetchEmployees(params = {}) {
    try {
      const response = await apiClient.get(API_ENDPOINT, { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching employees:', error.response || error);
      throw error;
    }
  }

  // Create a new employee
  async createEmployee(employeeData) {
    try {
      const response = await apiClient.post(API_ENDPOINT, employeeData);
      return response.data;
    } catch (error) {
      console.error('Error creating employee:', error.response || error);
      throw error;
    }
  }

  // Update an existing employee
  async updateEmployee(id, employeeData) {
    try {
      const response = await apiClient.put(
        `${API_ENDPOINT}/${id}`,
        employeeData
      );
      return response.data;
    } catch (error) {
      console.error('Error updating employee:', error.response || error);
      throw error;
    }
  }

  // Delete an employee
  async deleteEmployee(id) {
    try {
      const response = await apiClient.delete(`${API_ENDPOINT}/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error deleting employee:', error.response || error);
      throw error;
    }
  }

  // Get employee by ID
  async getEmployeeById(id) {
    try {
      const response = await apiClient.get(`${API_ENDPOINT}/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching employee:', error.response || error);
      throw error;
    }
  }
}

export default new EmployeeService();
