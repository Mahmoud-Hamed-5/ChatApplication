<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('gender', ['Male', 'Female']);
            $table->string('token')->default('');
            $table->integer('connection_id')->default(0);
            $table->enum('user_status', ['Online', 'Offline']);
            $table->string('user_image')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('connection_id');
            $table->dropColumn('user_status');
            $table->dropColumn('user_image');
        });
    }
};
