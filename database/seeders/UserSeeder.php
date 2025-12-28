<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // تعطيل فحص الـ foreign keys مؤقتًا (آمن جدًا في مرحلة الـ seeding)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // حذف كل المستخدمين القدامى باستخدام truncate (سريع ونظيف)
        DB::table('users')->truncate();

        // 1 - الأدمن
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@zte.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '0123456789',
            'bio' => 'مدير المنصة',
            'subscription_type' => 'premium',
            'course_limit' => 999,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2 - 10 معلمين
        for ($i = 1; $i <= 10; $i++) {
            DB::table('users')->insert([
                'name' => 'Instructor ' . $i,
                'email' => 'instructor' . $i . '@zte.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'instructor',
                'phone' => '077000000' . $i,
                'bio' => 'معلم رقم ' . $i,
                'subscription_type' => ($i % 2 == 0) ? 'pro' : 'free',
                'course_limit' => ($i % 2 == 0) ? 50 : 10,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3 - 50 طالب
        for ($i = 1; $i <= 50; $i++) {
            DB::table('users')->insert([
                'name' => 'Student ' . $i,
                'email' => 'student' . $i . '@zte.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => ($i % 3 == 0) ? null : '079000000' . $i,
                'bio' => null,
                'subscription_type' => 'free',
                'course_limit' => 3,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // إعادة تفعيل فحص الـ foreign keys
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✅ تم إنشاء 61 مستخدم بنجاح!');
    }
}