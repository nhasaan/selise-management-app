<template>
  <form @submit.prevent="submitForm" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Name Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Full Name
        </label>
        <input
          v-model="formData.name"
          type="text"
          required
          class="input w-full border border-gray-300 rounded-md px-3 py-2"
          placeholder="Enter full name"
        />
      </div>

      <!-- Email Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Email Address
        </label>
        <input
          v-model="formData.email"
          type="email"
          required
          class="input w-full border border-gray-300 rounded-md px-3 py-2"
          placeholder="Enter email address"
        />
      </div>

      <!-- Department Select -->
      <div>
        <DepartmentSelect v-model="formData.department_id" label="Department" />
      </div>

      <!-- Designation Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Designation
        </label>
        <input
          v-model="formData.designation"
          type="text"
          required
          class="input w-full border border-gray-300 rounded-md px-3 py-2"
          placeholder="Enter job designation"
        />
      </div>

      <!-- Salary Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Salary
        </label>
        <input
          v-model.number="formData.salary"
          type="number"
          required
          class="input w-full border border-gray-300 rounded-md px-3 py-2"
          placeholder="Enter salary"
          min="0"
        />
      </div>

      <!-- Address Input -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Address
        </label>
        <input
          v-model="formData.address"
          type="text"
          required
          class="input w-full border border-gray-300 rounded-md px-3 py-2"
          placeholder="Enter full address"
        />
      </div>

      <!-- Joined Date Input -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Joined Date
        </label>
        <input
          v-model="formData.joined_date"
          type="date"
          required
          class="input w-full border border-gray-300 rounded-md px-3 py-2"
        />
      </div>
    </div>

    <!-- Form Actions -->
    <div class="flex justify-end space-x-4 mt-6">
      <button type="button" @click="$emit('cancel')" class="btn btn-secondary">
        Cancel
      </button>
      <button type="submit" class="btn btn-primary">
        {{ isEditing ? 'Update' : 'Create' }} Employee
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, defineProps, defineEmits, watch } from 'vue';
import DepartmentSelect from './DepartmentSelect.vue';

const props = defineProps({
  employee: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['submit', 'cancel']);

// Reactive form data
const formData = ref({
  name: '',
  email: '',
  department_id: '',
  designation: '',
  salary: null,
  address: '',
  joined_date: '',
});

// Computed property to check if we're editing
const isEditing = ref(false);

// Watch for changes in the employee prop
watch(
  () => props.employee,
  (newEmployee) => {
    if (newEmployee) {
      // Populate form with existing employee data
      formData.value = { ...newEmployee };
      isEditing.value = true;
    } else {
      // Reset form for new employee
      resetForm();
    }
  },
  { immediate: true }
);

// Method to submit the form
const submitForm = () => {
  emit('submit', formData.value);
};

// Method to reset the form
const resetForm = () => {
  formData.value = {
    name: '',
    email: '',
    department_id: '',
    designation: '',
    salary: null,
    address: '',
    joined_date: '',
  };
  isEditing.value = false;
};
</script>
