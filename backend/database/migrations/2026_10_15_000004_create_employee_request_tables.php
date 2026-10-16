<?php

use App\Enums\RequestStatus;
use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('leave_type', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $this->reviewColumns($table);
        });

        Schema::create('resignation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('resignation_date');
            $table->text('reason');
            $this->reviewColumns($table);
        });

        Schema::create('subject_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code', 20);
            $table->string('subject_name', 100);
            $table->unsignedTinyInteger('units');
            $this->reviewColumns($table);
        });

        Schema::create('task_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('task_title', 150);
            $table->text('task_description')->nullable();
            $table->date('due_date');
            $table->enum('task_status', TaskStatus::values())->default(TaskStatus::Pending->value);
            $this->reviewColumns($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_requests');
        Schema::dropIfExists('subject_requests');
        Schema::dropIfExists('resignation_requests');
        Schema::dropIfExists('leave_requests');
    }

    /**
     * Columns shared by every request an administrator approves or rejects.
     */
    private function reviewColumns(Blueprint $table): void
    {
        $table->enum('status', RequestStatus::values())->default(RequestStatus::Pending->value)->index();
        $table->text('admin_comment')->nullable();
        $table->foreignId('reviewed_by')->nullable()->constrained('employees')->nullOnDelete();
        $table->timestamp('reviewed_at')->nullable();
        $table->timestamps();
    }
};
