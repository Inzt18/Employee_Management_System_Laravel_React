<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->lastName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(Employee::GENDERS),
            'email' => fake()->unique()->safeEmail(),
            'department' => fake()->randomElement(Employee::DEPARTMENTS),
            'address' => fake()->address(),
            'username' => fake()->unique()->userName(),
            'password' => 'password123',
            'role' => Role::Employee,
            'employment_status' => EmploymentStatus::Active,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    public function inactive(): static
    {
        return $this->state(['employment_status' => EmploymentStatus::Inactive]);
    }
}
