<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Informations d'admission
            $table->string('admission_number', 50)->nullable()->after('matricule');
            $table->string('section', 10)->nullable()->after('class_id'); // Si class_id existe, sinon après gender
            $table->boolean('large_family')->default(false)->after('status');
            $table->boolean('staff_child')->default(false)->after('large_family');
            $table->string('religion', 50)->nullable()->after('staff_child');
            $table->date('admission_date')->nullable()->after('religion');
            $table->string('receipt_number', 50)->nullable()->after('admission_date');
            $table->string('photo')->nullable()->after('gender'); // Assurez-vous que c'est 'photo' et non 'photo_url'

            // Informations Père
            $table->string('father_name', 100)->nullable()->after('photo');
            $table->string('father_phone', 20)->nullable()->after('father_name');
            $table->string('father_occupation', 100)->nullable()->after('father_phone');

            // Informations Mère
            $table->string('mother_name', 100)->nullable()->after('father_occupation');
            $table->string('mother_phone', 20)->nullable()->after('mother_name');
            $table->string('mother_occupation', 100)->nullable()->after('mother_phone');

            // Informations Tuteur
            $table->string('guardian_type', 20)->nullable()->after('mother_occupation');
            $table->string('guardian_name', 100)->nullable()->after('guardian_type');
            $table->string('guardian_phone', 20)->nullable()->after('guardian_name');
            $table->string('guardian_relation', 50)->nullable()->after('guardian_phone');
            $table->string('guardian_email', 100)->nullable()->after('guardian_relation');
            $table->string('guardian_occupation', 100)->nullable()->after('guardian_email');
            $table->text('guardian_address')->nullable()->after('guardian_occupation');

            // Adresses et Divers
            $table->text('current_address')->nullable()->after('guardian_address');
            $table->text('permanent_address')->nullable()->after('current_address');
            $table->string('previous_school', 255)->nullable()->after('permanent_address');
            $table->text('remarks')->nullable()->after('previous_school');

                        // Adresses et Divers
            $table->text('current_address')->nullable()->after('guardian_address');
            $table->text('permanent_address')->nullable()->after('current_address');
            $table->string('previous_school', 255)->nullable()->after('permanent_address');
            $table->text('remarks')->nullable()->after('previous_school');
            
            // ✅ NOUVEAU : Stockage des 4 documents administratifs (JSON)
            $table->json('documents')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'admission_number', 'section', 'large_family', 'staff_child', 
                'religion', 'admission_date', 'receipt_number', 'photo',
                'father_name', 'father_phone', 'father_occupation',
                'mother_name', 'mother_phone', 'mother_occupation',
                'guardian_type', 'guardian_name', 'guardian_phone', 
                'guardian_relation', 'guardian_email', 'guardian_occupation', 'guardian_address',
                'current_address', 'permanent_address', 'previous_school', 'remarks',
                'documents'
            ]);
        });
    }
};