<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <v-main class="bg-background">
    <v-container class="d-flex align-center justify-center min-h-screen py-12">
      <v-card width="560" rounded="lg" elevation="2">
        <v-card-text class="pa-8 text-center">
          <v-avatar color="success" variant="tonal" size="64" class="mb-5">
            <v-icon icon="mdi-check" size="34" />
          </v-avatar>

          <div class="page-title">Request submitted</div>
          <div class="page-subtitle mt-3">
            Your appointment request is pending. Clinic staff will review it and contact you once it is confirmed.
          </div>

          <v-divider class="my-6" />

          <div class="text-left">
            <div class="d-flex justify-space-between py-2">
              <span class="text-medium-emphasis">Reference</span>
              <span class="font-weight-medium">#{{ route.query.id }}</span>
            </div>
            <div class="d-flex justify-space-between py-2">
              <span class="text-medium-emphasis">Date</span>
              <span class="font-weight-medium">{{ route.query.date }}</span>
            </div>
            <div class="d-flex justify-space-between py-2">
              <span class="text-medium-emphasis">Time</span>
              <span class="font-weight-medium">{{ route.query.time }}</span>
            </div>
          </div>

          <div class="d-flex flex-wrap justify-center ga-3 mt-8">
            <v-btn to="/booking" color="primary" prepend-icon="mdi-calendar-plus">
              Book another
            </v-btn>
            <v-btn to="/login" variant="tonal" color="primary" prepend-icon="mdi-shield-account">
              Staff login
            </v-btn>
          </div>
        </v-card-text>
      </v-card>
    </v-container>
  </v-main>
</template>
