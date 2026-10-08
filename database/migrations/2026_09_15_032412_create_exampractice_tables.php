<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('role')->default('student')->index();
            $table->boolean('active')->default(true);
            $table->string('locale', 5)->default('en');
            $table->boolean('device_lock')->default(false);
            $table->string('device_hash', 64)->nullable();
            $table->unsignedInteger('session_version')->default(1);
        });
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover')->default('japanese');
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('practice_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(20);
            $table->unsignedTinyInteger('passing_score')->default(70);
            $table->boolean('show_explanations')->default(true);
            $table->boolean('copy_protection')->default(false);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practice_package_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('option_a');
            $table->text('option_b');
            $table->text('option_c');
            $table->text('option_d');
            $table->char('correct_answer', 1);
            $table->text('explanation')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->unsignedInteger('reading_minutes')->default(5);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource_type', 30);
            $table->unsignedBigInteger('resource_id');
            $table->unique(['user_id', 'resource_type', 'resource_id']);
        });
        Schema::create('material_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_material_id')->constrained()->cascadeOnDelete();
            $table->dateTime('completed_at');
            $table->unique(['user_id', 'learning_material_id']);
        });
        Schema::create('practice_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practice_package_id')->nullable()->constrained()->nullOnDelete();
            $table->json('snapshot');
            $table->json('answers');
            $table->dateTime('started_at');
            $table->dateTime('ends_at')->index();
            $table->dateTime('submitted_at')->nullable();
            $table->string('status')->default('in_progress')->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('answered_count')->default(0);
            $table->timestamps();
        });
        Schema::create('device_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_hash', 64);
            $table->string('label');
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('last_seen_at');
            $table->dateTime('revoked_at')->nullable();
            $table->unique(['user_id', 'device_hash']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['device_records', 'practice_attempts', 'material_completions', 'access_grants', 'learning_materials', 'questions', 'practice_packages', 'modules', 'programs'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['username', 'role', 'active', 'locale', 'device_lock', 'device_hash', 'session_version']));
    }
};
