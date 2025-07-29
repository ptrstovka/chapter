<template>
  <div>
    <h1>Tu editujem kurz</h1>
    <p>{{ course.title }}</p>

    <div v-if="course.author">
      <h2>{{ course.author.name }}</h2>
      <p>{{ course.author.bio }}</p>
    </div>

    <form @submit.prevent="submit">
      <div class="mb-4">
        <label for="title">Title:</label>
        <input id="title" type="text" v-model="form.title" class="w-full p-2 border" />
        <div v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</div>
      </div>

      <div class="mb-4">
        <label for="description">Description:</label>
        <textarea id="description" v-model="form.description" class="w-full p-2 border"></textarea>
        <div v-if="form.errors.description" class="text-sm text-red-500">{{ form.errors.description }}</div>
      </div>

      <div>
        <input type="file" /> 
      </div>

      <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded">
        Save
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface Course {
    id: number;
    title: string;
    description: string;
    author: {
        name: string;
        bio: string;
    }
}


const props = defineProps<{
  course: Course;
}>();

const form = useForm({
  title: props.course.title || '',
  description: props.course.description || '',
});

function submit() {
  form.patch(`/studio/course/${props.course.id}/update`);
}
</script>
