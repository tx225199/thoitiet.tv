<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'id' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:admins,email,' . ($this->id ?? 'NULL'),
            'password' => 'nullable|min:6',
            'status' => 'required|string',
        ];
    }

    /**
     * Customize error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên không được để trống.',
            'email.required' => 'Email không được để trống.',
            'email.unique' => 'Email này đã tồn tại.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'status.required' => 'Trạng thái không được để trống.',
        ];
    }
}
