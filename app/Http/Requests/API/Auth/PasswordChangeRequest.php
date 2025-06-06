<?php

namespace App\Http\Requests\API\Auth;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class PasswordChangeRequest extends FormRequest
{
    use ApiResponse;
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
        return [
            "email" => "required|email|exists:users,email",
            'password' => "required|confirmed",
        ];
    }



    /**
     * Define custom validation messages for the email and password fields.
     *
     * @return array The custom error messages for the validation rules.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'This email address is not registered in our system.',

            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
        ];
    }




    /**
     * Handles failed validation by formatting the validation errors and throwing a ValidationException.
     *
     * This method is called when validation fails in a form request. It uses the `error` method
     * from the `ApiResponse` trait to generate a standardized error response with the validation
     * error messages and a 422 HTTP status code. It then throws a `ValidationException` with the
     * formatted response.
     *
     * @param Validator $validator The validator instance containing the validation errors.
     *
     * @return void Throws a ValidationException with a formatted error response.
     *
     * @throws ValidationException The exception is thrown to halt further processing and return validation errors.
     */
    protected function failedValidation(Validator $validator):never
    {
        $emailErrors = $validator->errors()->get('email') ?? null;
        $passwordErrors = $validator->errors()->get('password') ?? null;

        if ($emailErrors) {
            $message = $emailErrors[0];
        } else if ($passwordErrors) {
            $message = $passwordErrors[0];
        }

        $response = $this->error(
            422,
            $message,
            $validator->errors(),
        );

        throw new ValidationException($validator, $response);
    }
}
