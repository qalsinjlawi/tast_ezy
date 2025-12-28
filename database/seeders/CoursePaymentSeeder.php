<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\CoursePayment;
use Faker\Factory as Faker;

class CoursePaymentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // جلب الطلاب والكورسات المنشورة
        $students = User::where('role', 'student')->pluck('id')->toArray();
        $courses = Course::where('is_published', true)->get();

        if (empty($students) || $courses->isEmpty()) {
            $this->command->warn('No students or published courses found. Skipping course payments.');
            return;
        }

        $totalCreated = 0;

        foreach ($students as $studentId) {
            // كل طالب يدفع لـ 1–4 كورسات عشوائية
            $paymentCount = $faker->numberBetween(1, 4);
            $selectedCourses = $faker->randomElements($courses->pluck('id')->toArray(), min($paymentCount, $courses->count()));

            foreach ($selectedCourses as $courseId) {
                $course = $courses->find($courseId);

                // تجنب التكرار
                if (CoursePayment::where('user_id', $studentId)->where('course_id', $courseId)->exists()) {
                    continue;
                }

                CoursePayment::create([
                    'user_id'          => $studentId,
                    'course_id'        => $courseId,
                    'amount'           => $course->price,
                    'currency'         => 'USD',
                    'payment_method'   => $faker->randomElement(['credit_card', 'paypal', 'stripe', 'cash']),
                    'transaction_id'   => $faker->uuid(),
                    'status'           => $faker->randomElement(['paid', 'pending', 'failed', 'refunded']),
                    'paid_at'          => $faker->dateTimeBetween('-6 months', 'now'),
                ]);

                $totalCreated++;
            }
        }

        $this->command->info("تم إنشاء {$totalCreated} دفعة كورس بنجاح!");
    }
}