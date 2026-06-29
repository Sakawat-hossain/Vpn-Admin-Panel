@extends('backend.layouts.form')

@section('title', admin_lang('Edit Plan') . ' | ' . $plan->name)
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('back', route('admin.plans.index'))

@section('content')
    <form id="billiongroup-submited-form" action="{{ route('admin.plans.update', $plan->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card custom-card mb-4">
            <div class="card-header bg-primary text-white">
                {{ admin_lang('Plan details') }}
            </div>
            <ul id="customFeatures" class="custom-list-group list-group list-group-flush">
                <li class="list-group-item">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-lg-6">
                            <label class="col-form-label"><strong>{{ admin_lang('Plan Name') }} : <span
                                        class="text-danger">*</span></strong></label>
                        </div>
                        <div class="col-12 col-lg-2">
                            <input type="checkbox" name="is_featured" class="form-check-input"
                                {{ $plan->isFeatured() ? 'checked' : '' }}>
                            <label>{{ admin_lang('Featured plan') }}</label>
                        </div>
                        <div class="col col-lg-4">
                            <input type="text" name="name" class="form-control" required value="{{ $plan->name }}"
                                placeholder="{{ admin_lang('Enter plan name') }}" autofocus>
                        </div>
                    </div>
                </li>
                <li class="list-group-item">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-lg-8">
                            <label class="col-form-label d-block"><strong>{{ admin_lang('Product Id') }} : <span
                                        class="text-danger">*</span></strong></label>
                        </div>
                        <div class="col-12 col-lg-4">
                            <textarea name="product_id" class="form-control"
                                required>{{ $plan->product_id }}</textarea>
                        </div>
                    </div>
                </li>
                <li class="list-group-item">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-lg-8">
                            <label class="col-form-label"><strong>{{ admin_lang('Plan Interval') }} :</strong></label>
                        </div>
                        <div class="col col-lg-4">
    <select class="form-select" name="interval" required>
        <option value="1" {{ $plan->interval == 1 ? 'selected' : '' }}>
            {{ admin_lang('Monthly') }}
        </option>
        <option value="2" {{ $plan->interval == 2 ? 'selected' : '' }}>
            {{ admin_lang('Yearly') }}
        </option>
        <option value="3" {{ $plan->interval == 3 ? 'selected' : '' }}>
            {{ admin_lang('Weekly') }}
        </option>
        <option value="4" {{ $plan->interval == 4 ? 'selected' : '' }}>
            {{ admin_lang('Half-Yearly') }}
        </option>
    </select>
</div>

                    </div>
                </li>
                <li class="list-group-item">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-lg-6">
                            <label class="col-form-label"><strong>{{ admin_lang('Plan Price') }} : <span
                                        class="text-danger">*</span></strong></label>
                        </div>
                        <div class="col-12 col-lg-2">
                            <input type="checkbox" name="is_free" class="free-plan-checkbox form-check-input"
                                {{ $plan->isFree() ? 'checked' : '' }}>
                            <label>{{ admin_lang('Free') }}</label>
                        </div>
                        <div class="col col-lg-4">
                            <div class="custom-input-group input-group plan-price">
                                <input type="text" name="price" class="form-control input-price"
                                    value="{{ $plan->price }}" placeholder="0.00" required
                                    {{ $plan->isFree() ? 'disabled' : '' }} />
                                <span
                                    class="input-group-text {{ $plan->isFree() ? 'disabled' : '' }}"><strong>{{ $settings->currency->code }}</strong></span>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="plan-require-login list-group-item {{ $plan->isFree() ? '' : 'd-none' }}">
                    <div class="row align-items-center">
                        <div class="col-8 col-lg-8">
                            <label class="col-form-label d-block"><strong>{{ admin_lang('Require Login') }} :</strong></label>
                            <small>{{ admin_lang('Without login, the guests will be able to generate the images.') }}</small>
                        </div>
                        <div class="col-4 col-lg-4">
                            <input type="checkbox" name="login_require" data-toggle="toggle"
                                data-on="{{ admin_lang('Yes') }}" data-off="{{ admin_lang('No') }}"
                                {{ $plan->login_require ? 'checked' : '' }}>
                        </div>
                    </div>
                </li>
                <li class="list-group-item">
                    <div class="row align-items-center">
                        <div class="col-8 col-lg-8">
                            <label class="col-form-label d-block"><strong>{{ admin_lang('Show advertisements') }} :</strong></label>
                            <small>{{ admin_lang('Show the advertisements (Yes/No)') }}</small>
                        </div>
                        <div class="col-4 col-lg-4">
                            <input type="checkbox" name="advertisements" data-toggle="toggle"
                                data-on="{{ admin_lang('Yes') }}" data-off="{{ admin_lang('No') }}"
                                {{ $plan->advertisements ? 'checked' : '' }}>
                        </div>
                    </div>
                </li>
                @if ($plan->custom_features)
                    @foreach ($plan->custom_features as $key => $value)
                        <li id="customFeature{{ $key }}" class="list-group-item">
                            <div class="row g-2 align-items-center">
                                <div class="col">
                                    <input type="text" name="custom_features[{{ $key }}][name]"
                                        placeholder="{{ admin_lang('Enter name') }}" class="form-control"
                                        value="{{ $value->name }}" required>
                                </div>
                                <div class="col-auto">
                                    <button type="button" data-id="{{ $key }}"
                                        class="removeFeature btn btn-danger"><i class="ti ti-trash"></i></button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>
        <button type="button" id="addCustomFeature" class="btn btn-primary"><i
                class="fa fa-plus me-2"></i>{{ admin_lang('Add custom feature') }}</button>

    </form>

    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/tags-input/bootstrap-tagsinput.css') }}">
    @endpush

    @push('scripts_libs')
        <script src="{{ asset('assets/vendor/libs/jquery/jquery.priceformat.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/tags-input/bootstrap-tagsinput.min.js') }}"></script>
    @endpush

    @push('top_scripts')
        <script>
            "use strict";
            var customFeatureI = {{ $plan->custom_features ? count($plan->custom_features) - 1 : -1 }};
        </script>
    @endpush

    @push('scripts')
        <script>
            "use strict";
            $(function() {
                let tagsInput = $('#tagsInput');
                tagsInput.tagsinput({
                    cancelConfirmKeysOnEmpty: false
                });
                tagsInput.on('beforeItemAdd', function(event) {
                    if (!/^(25[6-9]|[3-9]\d{2}|[1-3]\d{3}|4000)x(25[6-9]|[3-9]\d{2}|[1-3]\d{3}|4000)$/.test(
                            event.item)) {
                        event.cancel = true;
                        toastr.error('{{ admin_lang('The size format is invalid') }}');
                    }
                });
            });
        </script>
    @endpush
@endsection
