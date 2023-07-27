<?php
namespace App\Http\Requests;

use App\Exceptions\ResponseCode;
use App\Exceptions\ParameterException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

abstract class Request extends FormRequest
{
    /**
     * Instruct the validator to stop validating after the first rule failure.
     * @var bool
     */
    protected $stopOnFirstFailure = true;

    /**
     * 重新定义表单验证错误抛出方法
     * @param  Validator  $validator
     *
     * @throws ParameterException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new ParameterException($validator->errors()->first(),ResponseCode::VALIDATION_ERROR);
    }
}
