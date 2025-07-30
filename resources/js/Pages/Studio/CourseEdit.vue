<template>
  <div>
    <h1>Tu editujem kurz</h1>
    <p>{{ course.title }}</p>

    <div v-if="course.author">
      <h2>{{ course.author.name }}</h2>
      <p>{{ course.author.bio }}</p>
    </div>

    <form @submit.prevent="submit">
        <Tabs default-value="informations">
            <TabsList >
                <TabsTrigger value="informations">
                    Basic Informations
                </TabsTrigger>
                <TabsTrigger value="lessons">
                    Manage Lessons
                </TabsTrigger>
            </TabsList>
            <TabsContent value="informations">
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

                <div class="mb-4">
                    <label for="cover">Cover Image:</label>
                    <input type="file" @change="uploadFile" />
                </div>

                <div class="mb-4">
                    <label for="cover">Thrailer video:</label>
                    <input type="file" @change="uploadVideo" />
                </div>

                <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded">
                    Save
                </button>
            </TabsContent>
        </Tabs>


    </form>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {    
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger
} from "@/Components/Tabs";

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
  cover_image_file_path: '',
  video_path: ''
});

function submit() {
  form.patch(`/studio/course/${props.course.id}/update`);
}

async function uploadFile(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);

  try {
    const response = await axios.post<{ path: string }>('/studio/upload-cover', formData)

    form.cover_image_file_path = response.data.path

    console.log('Obrázok uložený');
  } catch (error) {
    console.error('Chyba pri nahrávaní obrázka:', error);
  }
}

async function uploadVideo(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
        const response = await axios.post<{ path: string }>('/studio/upload-video', formData)

        form.video_path = response.data.path;

        console.log('Video uložene');
    } catch (error) {
        console.error('Chyba pri nahravani obrazka', error);
    }
}
</script>
