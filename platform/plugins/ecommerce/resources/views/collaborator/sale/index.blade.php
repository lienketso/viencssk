@extends('plugins/ecommerce::collaborator.dashboard.layouts.master-sale')
@section('content')
    <div class="max-width-1200" id="main-order">
        <create-order :currency="'{{ get_application_currency()->symbol }}'" :zip_code_enabled="{{ (int)EcommerceHelper::isZipCodeEnabled() }}" :use_location_data="{{ (int)EcommerceHelper::loadCountriesStatesCitiesFromPluginLocation() }}"></create-order>
    </div>
@stop

@push('header')
    <script>
        'use strict';

        window.trans = window.trans || {};

        window.trans.order = JSON.parse('{!! addslashes(json_encode(trans('plugins/ecommerce::order'))) !!}');

    </script>
@endpush
@push('footer')
    <?php echo app('Tightenco\Ziggy\BladeRouteGenerator')->generate(); ?>
@endpush
