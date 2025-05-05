<template>
  <div id="app">
    <nav>
      <router-link to="/">Dashboard</router-link>
      <router-link to="/employees">Employees</router-link>
    </nav>
    <router-view></router-view>
  </div>
</template>

<script setup>
// Any global setup can go here
</script>

<style>
#app {
  font-family: Arial, sans-serif;
}
</style>
