<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use App\Traits\APIResponse;
use Illuminate\Foundation\Http\FormRequest;

class RequestDeleteManyCategory extends FormRequest
{
    use APIResponse;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'ids_category' => 'required|array|min:1',
            'ids_category.*' => 'required|integer',
            'category_is_delete'=>'required|in:1,0'
        ];
    }
    public function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->all();

        return $this->responseErrorValidate($errors, $validator); // default
        // return $this->responseErrorValidate($errors, $validator, 400); // custom
    }

    public function messages()
    {
        return [
            'title.required' => 'Title is required',
            'body.required' => 'Body is required',
        ];
    }
}
