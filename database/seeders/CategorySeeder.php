<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Learn to build websites and web applications using modern technologies.',
                'icon' => 'fas fa-laptop-code',
                'image' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Development',
                'slug' => 'mobile-development',
                'description' => 'Build mobile apps for Android and iOS platforms.',
                'icon' => 'fas fa-mobile-alt',
                'image' => null,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Data Science',
                'slug' => 'data-science',
                'description' => 'Master data analysis, machine learning, and AI.',
                'icon' => 'fas fa-chart-bar',
                'image' => null,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Graphic Design',
                'slug' => 'graphic-design',
                'description' => 'Learn design tools like Photoshop, Illustrator, and Figma.',
                'icon' => 'fas fa-palette',
                'image' => null,
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Digital Marketing',
                'slug' => 'digital-marketing',
                'description' => 'Master SEO, social media, and online advertising.',
                'icon' => 'fas fa-bullhorn',
                'image' => null,
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Cybersecurity',
                'slug' => 'cybersecurity',
                'description' => 'Learn to protect systems and networks from threats.',
                'icon' => 'fas fa-shield-alt',
                'image' => null,
                'order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Programming Languages',
                'slug' => 'programming-languages',
                'description' => 'Deep dive into Python, JavaScript, PHP, and more.',
                'icon' => 'fas fa-code',
                'image' => null,
                'order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Game Development',
                'slug' => 'game-development',
                'description' => 'Create games using Unity, Unreal Engine, and more.',
                'icon' => 'fas fa-gamepad',
                'image' => null,
                'order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('تم إنشاء 8 تصنيفات بنجاح!');
    }
}