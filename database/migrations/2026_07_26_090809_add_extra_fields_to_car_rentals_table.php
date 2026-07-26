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
        Schema::table('car_rentals', function (Blueprint $table) {
            $table->string('duration_capacity')->nullable()->after('capacity');
            $table->string('category')->nullable()->after('duration_capacity');
            $table->json('features')->nullable()->after('category'); // Tipe JSON cocok untuk menyimpan format array seperti ["Manual/AT", "AC"]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_rentals', function (Blueprint $table) {
            //
            $table->dropColumn(['duration_capacity', 'category', 'features']);
        });
    }
};
