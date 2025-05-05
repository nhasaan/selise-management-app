<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md">
      <div class="container mx-auto px-6 py-3">
        <div class="flex justify-between items-center">
          <div class="font-bold text-xl text-primary-600">
            Selise Management
          </div>
          <div class="space-x-6">
            <router-link
              to="/"
              class="text-gray-700 hover:text-primary-500 transition-colors"
              active-class="text-primary-500 font-semibold"
            >
              Dashboard
            </router-link>
            <router-link
              to="/employees"
              class="text-gray-700 hover:text-primary-500 transition-colors"
              active-class="text-primary-500 font-semibold"
            >
              Employees
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="container mx-auto px-6 py-8">
      <router-view></router-view>
    </main>
  </div>
</template>

<script setup>
// Global app setup
</script>
