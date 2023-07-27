<?php
namespace App\Http\Requests;

class CallBackRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        // 根据不同的情况, 添加不同的验证规则
        switch (Request::route()->getActionMethod()) {
            case 'user':
                return [
                    'id'    => 'required',
                ];
            default : return [];
        }
    }

    /**
     * 获取验证错误的自定义属性
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute不能为空',
        ];
    }

    /**
     * 属性命名
     *
     * @return array
     */
    public function attributes():array
    {
        return [

        ];
    }
}
