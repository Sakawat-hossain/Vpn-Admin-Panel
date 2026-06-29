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
        Schema::create('config_server_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('config_job_id')->nullable()->index('config_server_actions_config_job_id_foreign');
            $table->unsignedInteger('order')->default(0);
            $table->text('action');
            $table->integer('result_code')->default(-1);
            $table->text('result');
            $table->timestamps();
        });
        
        Schema::create('config_server_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('job_id')->nullable()->index('config_server_jobs_job_id_foreign');
            $table->unsignedBigInteger('server_id')->nullable()->index('config_server_jobs_server_id_foreign');
            $table->string('ip', 50);
            $table->integer('ssh_port')->default(1194);
            $table->string('vps_username')->default('');
            $table->string('vps_password')->default('');
            $table->enum('status', ['idle', 'running', 'stopped', 'success', 'failed'])->default('idle');
            $table->text('message')->nullable();
            $table->timestamps();
        });
        
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('config_server_jobs');
        Schema::dropIfExists('config_server_actions');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
    }
};
