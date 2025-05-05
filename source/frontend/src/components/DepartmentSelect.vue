<template>
    <div class="department-select">
      <label 
        v-if="label" 
        class="block text-sm font-medium text-gray-700 mb-2"
      >
        {{ label }}
      </label>
      <select 
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md"
        :class="{ 'border-red-500': error }"
      >
        <option value="">Select Department</option>
        <option 
          v-for="department in departments" 
          :key="department.id" 
          :value="department.id"
        >
          {{ department.name }}
        </option>
      </select>
      <p 
        v-if="error" 
        class="mt-2 text-sm text-red-600"
      >
        {{ error }}
      </p>
    </div>
  </template>
  
  <script setup>
  import { defineProps, defineEmits, computed } from 'vue'
  import { useDepartmentStore } from '@/stores/departmentStore'
  
  const props = defineProps({
    modelValue: {
      type: [String, Number],
      default: ''
    },
    label: {
      type: String,
      default: 'Department'
    },
    error: {
      type: String,
      default: ''
    }
  })
  
  const emit = defineEmits(['update:modelValue'])
  
  const departmentStore = useDepartmentStore()
  const departments = computed(() => departmentStore.departments)
  </script>