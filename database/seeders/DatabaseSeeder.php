<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Course;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // تسجيل كل الـ Seeders بالترتيب المنطقي
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,   
            CourseSectionSeeder::class,
            
            // أولاً المستخدمين (طلاب، معلمين، أدمن)
            // لو عندك ReviewSeeder، أضفه هنا
            // ReviewSeeder::class,
        ]);

        $this->command->info('تم تشغيل جميع الـ Seeders بنجاح!');
    }
}