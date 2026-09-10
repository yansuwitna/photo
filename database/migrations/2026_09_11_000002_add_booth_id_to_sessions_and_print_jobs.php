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
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('booth_id', 50)->default('STAND-01')->after('operator_id')->index();
        });

        Schema::table('print_jobs', function (Blueprint $table) {
            $table->string('booth_id', 50)->default('STAND-01')->after('printer_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('booth_id');
        });

        Schema::table('print_jobs', function (Blueprint $table) {
            $table->dropColumn('booth_id');
        });
    }
};
