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
        Schema::table('organizational_users', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('customer_id')->nullable()->constrained();

            // $table->foreign('customer_id')
            // ->references('id')
            // ->on('users')
            // ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizational_users', function (Blueprint $table) {
            //
        });
    }
};
