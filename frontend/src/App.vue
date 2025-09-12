<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div id="app">
    <RouterView />
  </div>
</template>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  line-height: 1.6;
  color: #2c3e50;
  background: #f8f9fa;
}

#app {
  min-height: 100vh;
}

main.with-navigation {
  padding-top: 70px; /* Hauteur de la navigation */
}

/* Styles pour les messages d'erreur */
.error-message {
  background: #fee;
  color: #c33;
  padding: 16px;
  border-radius: 6px;
  border: 1px solid #fcc;
  margin: 20px;
  text-align: center;
}

.success-message {
  background: #efe;
  color: #363;
  padding: 16px;
  border-radius: 6px;
  border: 1px solid #cfc;
  margin: 20px;
  text-align: center;
}
</style>
