<?php

namespace Database\Seeders;

use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTeacherParentSeeder extends Seeder
{
    public function run(): void
    {
        $schoolId = 1; // École Les Mirabelles (Démo)
        $schoolClassId = 1; // CP1 A
        $schoolYear = SchoolYear::where('school_id', $schoolId)->where('is_active', true)->firstOrFail();

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@schoolmanager.com'],
            [
                'school_id' => $schoolId,
                'first_name' => 'Jean',
                'last_name' => 'Kouassi',
                'password' => 'teacher1234',
                'role' => 'teacher',
            ]
        );

        TeacherAssignment::updateOrCreate(
            [
                'school_id' => $schoolId,
                'school_class_id' => $schoolClassId,
                'user_id' => $teacher->id,
                'school_year_id' => $schoolYear->id,
            ],
            ['is_main_teacher' => true]
        );

        $students = Student::where('school_id', $schoolId)->orderBy('id')->limit(3)->get();

        foreach ($students as $student) {
            DB::table('student_school_class')->updateOrInsert(
                [
                    'student_id' => $student->id,
                    'school_class_id' => $schoolClassId,
                    'school_year_id' => $schoolYear->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $parent = User::updateOrCreate(
            ['email' => 'parent@schoolmanager.com'],
            [
                'school_id' => $schoolId,
                'first_name' => 'Awa',
                'last_name' => 'Traoré',
                'password' => 'parent1234',
                'role' => 'parent',
            ]
        );

        DB::table('parent_student')->updateOrInsert(
            [
                'parent_id' => $parent->id,
                'student_id' => $students->first()->id,
                'school_id' => $schoolId,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✅ Comptes démo enseignant et parent créés avec succès !');
        $this->command->info('📧 Enseignant: teacher@schoolmanager.com / teacher1234');
        $this->command->info('📧 Parent: parent@schoolmanager.com / parent1234 (enfant: '.$students->first()->first_name.' '.$students->first()->last_name.')');
    }
}
