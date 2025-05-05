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
        // Add indexes to employee table for performance
        Schema::table('employees', function (Blueprint $table) {
            // Add index on name for sorting and search
            $table->index('name', 'employees_name_index');
            // Email already has a unique index

            // Add index for department relationship lookups
            // It already has an index as a foreign key
        });

        // Add indexes to employee_details table for performance
        Schema::table('employee_details', function (Blueprint $table) {
            // Add index on designation for searches
            $table->index('designation', 'employee_details_designation_index');

            // Add index on salary for range filtering
            $table->index('salary', 'employee_details_salary_index');

            // Add index on joined_date for sorting
            $table->index('joined_date', 'employee_details_joined_date_index');
        });

        // Add indexes to departments table for performance
        Schema::table('departments', function (Blueprint $table) {
            // Add index on name for searches
            $table->index('name', 'departments_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added indexes
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_name_index');
        });

        Schema::table('employee_details', function (Blueprint $table) {
            $table->dropIndex('employee_details_designation_index');
            $table->dropIndex('employee_details_salary_index');
            $table->dropIndex('employee_details_joined_date_index');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('departments_name_index');
        });
    }
};
