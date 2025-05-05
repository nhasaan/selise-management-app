<?php

namespace App\DTO;

use Illuminate\Http\Request;

class EmployeeData
{
    public function __construct(
        public string $name,
        public string $email,
        public int $department_id,
        public string $designation,
        public float $salary,
        public string $address,
        public string $joined_date,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
            department_id: $request->input('department_id'),
            designation: $request->input('designation'),
            salary: $request->input('salary'),
            address: $request->input('address'),
            joined_date: $request->input('joined_date')
        );
    }
}
