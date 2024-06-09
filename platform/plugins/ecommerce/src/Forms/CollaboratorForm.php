<?php

namespace Botble\Ecommerce\Forms;

use BaseHelper;
use Botble\Base\Forms\FormAbstract;
use Botble\Ecommerce\Enums\CustomerStatusEnum;
use Botble\Ecommerce\Http\Requests\CustomerCreateRequest;
use Botble\Ecommerce\Models\Customer;

class CollaboratorForm extends FormAbstract
{
    public function buildForm()
    {
       $this
           ->setupModel(new Customer)
           ->setValidatorClass(CustomerCreateRequest::class)
           ->withCustomFields()
           ->add('name', 'text', [
               'label'      => trans('core/base::forms.name'),
               'label_attr' => ['class' => 'control-label required'],
               'attr'       => [
                   'placeholder'  => trans('core/base::forms.name_placeholder'),
                   'data-counter' => 120,
               ],
           ])
           ->add('email', 'text', [
               'label'      => trans('plugins/ecommerce::customer.email'),
               'label_attr' => ['class' => 'control-label'],
               'attr'       => [
                   'placeholder'  => trans('plugins/ecommerce::customer.email_placeholder'),
                   'data-counter' => 60,
               ],
           ])
           ->add('phone', 'text', [
               'label'      => trans('plugins/ecommerce::customer.phone'),
               'label_attr' => ['class' => 'control-label'],
               'attr'       => [
                   'placeholder'  => trans('plugins/ecommerce::customer.phone_placeholder'),
                   'data-counter' => 20,
               ],
           ])
           ->add('dob', 'date', [
               'label'      => trans('plugins/ecommerce::customer.dob'),
               'label_attr' => ['class' => 'control-label'],
               'attr'       => [
                   'data-date-format' => config('core.base.general.date_format.js.date'),
               ],
               'default_value' => BaseHelper::formatDate(now()),
           ])
           ->add('area', 'text', [
               'label'      => 'Khu vực hoạt động',
               'label_attr' => ['class' => 'control-label'],
           ])
           ->add('is_change_password', 'checkbox', [
               'label'      => trans('plugins/ecommerce::customer.change_password'),
               'label_attr' => ['class' => 'control-label'],
               'value'      => 1,
           ])
           ->add('password', 'password', [
               'label'      => 'Mật khẩu',
               'label_attr' => ['class' => 'control-label required'],
               'attr'       => [
                   'data-counter' => 60,
               ],
               'wrapper'    => [
                   'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ($this->getModel()->id ? ' hidden' : null),
               ],
           ])
           ->add('password_confirmation', 'password', [
               'label'      => 'Nhập lại mật khẩu',
               'label_attr' => ['class' => 'control-label required'],
               'attr'       => [
                   'data-counter' => 60,
               ],
               'wrapper'    => [
                   'class' => $this->formHelper->getConfig('defaults.wrapper_class') . ($this->getModel()->id ? ' hidden' : null),
               ],
           ])
           ->add('status', 'customSelect', [
               'label'      => trans('core/base::tables.status'),
               'label_attr' => ['class' => 'control-label required'],
               'choices'    => CustomerStatusEnum::labels(),
           ])
           ->add('identification_number', 'number', [
               'label'      => 'Số CMT / CCCD',
               'label_attr' => ['class' => 'control-label'],
           ])
           ->add('identification_date', 'date', [
               'label'      => 'Ngày cấp CMT / CCCD',
               'attr'       => [
                   'data-date-format' => config('core.base.general.date_format.js.date'),
               ],
               'default_value' => BaseHelper::formatDate(now()),
           ])
           ->add('card_front', 'mediaImage', [
               'label'      => 'CMT/CCCD mặt trước',
               'label_attr' => ['class' => 'control-label'],
           ])
           ->add('card_back', 'mediaImage', [
               'label'      => 'CMT/CCCD mặt sau',
               'label_attr' => ['class' => 'control-label'],
           ])
           ->setBreakFieldPoint('status');
    }
}