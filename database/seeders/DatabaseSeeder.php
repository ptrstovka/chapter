<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->author()->create([
            'name' => 'Peter Stovka',
            'email' => 'peter@peterstovka.com',
        ]);

        $categories = [
            'Web Development',
            'Digital Marketing',
            'Graphic Design',
            'Data Science',
            'Photography & Videography',
            'Personal Finance',
            'Health & Wellness',
            'Music Production',
            'Leadership & Management',
            'Language Learning',
            'Artificial Intelligence & Machine Learning',
            'Cooking & Culinary Arts',
        ];

        foreach ($categories as $category) {
            Category::factory()->create([
                'title' => $category,
                'slug' => Str::slug($category),
            ]);
        }

        Author::factory(30)->create();

        Course::factory(30)->random()->create();
    }
}
