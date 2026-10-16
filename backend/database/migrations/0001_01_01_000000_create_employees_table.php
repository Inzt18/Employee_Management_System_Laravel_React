<?php

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50);
            $table->date('birth_date');
            $table->enum('gender', Employee::GENDERS);
            $table->string('email', 100)->unique();
            $table->string('department', 100);
            $table->text('address');
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->enum('role', Role::values())->default(Role::Employee->value);
            $table->enum('employment_status', EmploymentStatus::values())->default(EmploymentStatus::Active->value);
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
