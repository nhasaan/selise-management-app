import { createPinia } from 'pinia';

// Import stores
import { useEmployeeStore } from './employeeStore';
import { useDepartmentStore } from './departmentStore';

const pinia = createPinia();

export { pinia, useEmployeeStore, useDepartmentStore };
