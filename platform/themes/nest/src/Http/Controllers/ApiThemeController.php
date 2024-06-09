<?php

namespace Theme\Nest\Http\Controllers;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Location\Http\Resources\StateResource;
use Botble\Location\Models\State;
use Botble\Location\Repositories\Interfaces\StateInterface;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Http\Request;

class ApiThemeController extends PublicController
{
    public function __construct(StateInterface $stateRepository)
    {
        $this->stateRepository = $stateRepository;
    }
    public function getListDistrict(Request $request, BaseHttpResponse $response){
        $params = [
            'select'    => ['states.id', 'states.name'],
            'condition' => [
                'states.status' => BaseStatusEnum::PUBLISHED,
            ],
            'order_by'  => ['order' => 'ASC', 'created_at' => 'DESC'],
        ];

        if ($request->input('country_id') && $request->input('country_id') != 'null') {
            $params['condition']['states.country_id'] = $request->input('country_id');
        }

        $data = $this->stateRepository->advancedGet($params);

        $data->prepend(new State(['id' => 0, 'name' => trans('plugins/location::city.select_state')]));

        return $response->setData(StateResource::collection($data));
    }
}