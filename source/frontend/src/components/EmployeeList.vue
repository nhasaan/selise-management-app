<template>
  <div class="employee-list">
    <table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
      <thead class="bg-gray-100">
        <tr>
          <th
            class="px-4 py-3 text-left cursor-pointer"
            @click="$emit('sort', 'name')"
          >
            Name
            <span v-if="sortKey === 'name'">
              {{ sortDirection === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th
            class="px-4 py-3 text-left cursor-pointer"
            @click="$emit('sort', 'email')"
          >
            Email
            <span v-if="sortKey === 'email'">
              {{ sortDirection === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th class="px-4 py-3 text-left">Department</th>
          <th class="px-4 py-3 text-left">Designation</th>
          <th
            class="px-4 py-3 text-left cursor-pointer"
            @click="$emit('sort', 'joined_date')"
          >
            Joined Date
            <span v-if="sortKey === 'joined_date'">
              {{ sortDirection === 'asc' ? '▲' : '▼' }}
            </span>
          </th>
          <th class="px-4 py-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="employee in employees"
          :key="employee.id"
          class="border-b hover:bg-gray-50 transition-colors"
        >
          <td class="px-4 py-3">{{ employee.name }}</td>
          <td class="px-4 py-3">{{ employee.email }}</td>
          <td class="px-4 py-3">{{ employee.department.name }}</td>
          <td class="px-4 py-3">{{ employee.designation }}</td>
          <td class="px-4 py-3">{{ formatDate(employee.joined_date) }}</td>
          <td class="px-4 py-3 flex justify-center space-x-2">
            <button
              @click="$emit('edit', employee)"
              class="text-primary-600 hover:text-primary-800"
              title="Edit"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                />
              </svg>
            </button>
            <button
              @click="$emit('delete', employee.id)"
              class="text-red-600 hover:text-red-800"
              title="Delete"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
          </td>
        </tr>
        <tr v-if="employees.length === 0">
          <td colspan="6" class="text-center py-4 text-gray-500">
            No employees found
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  employees: {
    type: Array,
    required: true,
  },
  sortKey: {
    type: String,
    default: 'name',
  },
  sortDirection: {
    type: String,
    default: 'asc',
  },
});

const emit = defineEmits(['edit', 'delete', 'sort']);

// Method to format date
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>
