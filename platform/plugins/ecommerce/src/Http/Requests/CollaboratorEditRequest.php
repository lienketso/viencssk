<?php

namespace Botble\Ecommerce\Http\Requests;
use Botble\Support\Http\Requests\Request;
class CollaboratorEditRequest extends Request
{
    public function rules()
    {
        $rules = [
            'name'  => 'required|max:120|min:2',
            'email' => 'required',
        ];

        if ($this->input('is_change_password') == 1) {
            $rules['password'] = 'required|min:6';
            $rules['password_confirmation'] = 'required|same:password';
        }

        return $rules;
    }
}