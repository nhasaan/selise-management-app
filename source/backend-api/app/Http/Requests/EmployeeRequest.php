<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'designation' => ['required', 'string', 'max:255'],
            'salary' => ['required', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:255'],
            'joined_date' => ['required', 'date', 'before_or_equal:today'],
        ];

        // For update requests, make email unique except for the current employee
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['email'][] = Rule::unique('employees')->ignore($this->route('employee'));
        } else {
            $rules['email'][] = 'unique:employees';
        }

        return $rules;
    }
}
