<template>
    <h1>My studio page</h1>
    <p>dačo na začiatok, sidebar so stromom</p>

    <button @click="createCourse()">Vytvoriť kurz</button>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

const createCourse = () => {
    router.post(route('studio.course.create'));
}
</script>