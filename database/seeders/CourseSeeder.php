<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // جلب المدربين
        $instructors = User::where('role', 'instructor')->pluck('id')->toArray();

        // جلب IDs التصنيفات
        $categoryIds = Category::pluck('id')->toArray();

        // تشخيص فوري
        $this->command->info("عدد المدربين المتاحين: " . count($instructors));
        $this->command->info("عدد التصنيفات المتاحة: " . count($categoryIds));

        if (empty($instructors) || empty($categoryIds)) {
            $this->command->error('خطأ: لا يوجد مدربين أو تصنيفات كافية.');
            return;
        }

        $courses = [
            ['title' => 'Complete Laravel 11 From Scratch', 'level' => 'Beginner', 'price' => 99.99, 'published' => true],
            ['title' => 'Advanced Vue.js 3 Mastery', 'level' => 'Advanced', 'price' => 149.99, 'published' => true],
            ['title' => 'React.js & Next.js Full Course', 'level' => 'Intermediate', 'price' => 129.99, 'published' => true],
            ['title' => 'PHP 8 & MySQL Professional', 'level' => 'Intermediate', 'price' => 79.99, 'published' => true],
            ['title' => 'Flutter & Dart - Build iOS & Android Apps', 'level' => 'Beginner', 'price' => 119.99, 'published' => true],
            ['title' => 'React Native Masterclass', 'level' => 'Intermediate', 'price' => 109.99, 'published' => true],
            ['title' => 'Python for Data Science & Machine Learning', 'level' => 'Beginner', 'price' => 149.99, 'published' => true],
            ['title' => 'Deep Learning with TensorFlow', 'level' => 'Advanced', 'price' => 199.99, 'published' => true],
            ['title' => 'Adobe Photoshop Complete Guide', 'level' => 'Beginner', 'price' => 89.99, 'published' => true],
            ['title' => 'UI/UX Design with Figma', 'level' => 'Intermediate', 'price' => 99.99, 'published' => true],
            ['title' => 'Digital Marketing & SEO Mastery', 'level' => 'Beginner', 'price' => 79.99, 'published' => true],
            ['title' => 'Facebook & Instagram Ads Professional', 'level' => 'Intermediate', 'price' => 109.99, 'published' => true],
            ['title' => 'Ethical Hacking From Zero to Hero', 'level' => 'Intermediate', 'price' => 159.99, 'published' => true],
            ['title' => 'JavaScript Modern ES6+ Complete', 'level' => 'Beginner', 'price' => 69.99, 'published' => true],
            ['title' => 'Python Programming Advanced', 'level' => 'Advanced', 'price' => 89.99, 'published' => false],
            ['title' => 'Unity 3D Game Development', 'level' => 'Intermediate', 'price' => 129.99, 'published' => true],
        ];

        $createdCount = 0;

        foreach ($courses as $courseData) {
            try {
                $slug = Str::slug($courseData['title']);

                Course::create([
                    'title'             => $courseData['title'],
                    'slug'              => $slug,
                    'short_description' => $faker->paragraph(2),
                    'description'       => $faker->paragraphs(5, true),
                    'instructor_id'     => $faker->randomElement($instructors),
                    'category_id'       => $faker->randomElement($categoryIds),
                    'price'             => $courseData['price'],
                    'thumbnail'         => $faker->imageUrl(800, 450, 'education', true, 'course'),
                    'level'             => $courseData['level'],
                    'is_published'      => $courseData['published'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                $createdCount++;
            } catch (\Exception $e) {
                $this->command->error("خطأ أثناء إنشاء كورس '{$courseData['title']}': " . $e->getMessage());
            }
        }

        $total = Course::count();
        $this->command->info("تم إنشاء {$createdCount} كورس جديد، إجمالي الكورسات الآن: {$total}");
    }
}