import axios from 'axios';

const API_BASE_URL = '/api/employees';

class EmployeeService {
  // Fetch employees with optional filters
  async fetchEmployees(params = {}) {
    try {
      const response = await axios.get(API_BASE_URL, { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching employees:', error);
      throw error;
    }
  }

  // Create a new employee
  async createEmployee(employeeData) {
    try {
      const response = await axios.post(API_BASE_URL, employeeData);
      return response.data;
    } catch (error) {
      console.error('Error creating employee:', error);
      throw error;
    }
  }

  // Update an existing employee
  async updateEmployee(id, employeeData) {
    try {
      const response = await axios.put(`${API_BASE_URL}/${id}`, employeeData);
      return response.data;
    } catch (error) {
      console.error('Error updating employee:', error);
      throw error;
    }
  }

  // Delete an employee
  async deleteEmployee(id) {
    try {
      await axios.delete(`${API_BASE_URL}/${id}`);
    } catch (error) {
      console.error('Error deleting employee:', error);
      throw error;
    }
  }

  // Get employee by ID
  async getEmployeeById(id) {
    try {
      const response = await axios.get(`${API_BASE_URL}/${id}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching employee:', error);
      throw error;
    }
  }
}

export default new EmployeeService();
