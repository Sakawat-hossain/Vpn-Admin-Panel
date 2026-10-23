@extends('frontend.user.layouts.app')
@section('title', lang('Account details', 'account'))
@section('page-pretitle', lang('Settings', 'account'))
@section('page-title', lang('Account details', 'account'))
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <form id="deatilsForm" class="card" action="{{ route('user.settings.details.update') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="avatar-picker me-3">
                            <span id="avatar_preview" class="avatar avatar-xl"
                                style="background-image: url('{{ asset($user->avatar) }}')"></span>
                            <label for="change_avatar" class="btn btn-icon btn-sm btn-primary rounded-circle"
                                title="{{ lang('Change avatar', 'account') }}">
                                <i class="ti ti-camera"></i>
                            </label>
                            <input id="change_avatar" type="file" name="avatar" accept="image/jpg, image/jpeg, image/png" hidden />
                        </div>
                        <div>
                            <div class="h3 mb-0">{{ $user->name }}</div>
                            <div class="text-secondary">{{ $user->email }}</div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label required">{{ lang('First Name', 'forms') }}</label>
                            <input type="text" name="firstname" class="form-control" placeholder="{{ lang('First Name', 'forms') }}"
                                maxlength="50" value="{{ $user->firstname }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label required">{{ lang('Last Name', 'forms') }}</label>
                            <input type="text" name="lastname" class="form-control" placeholder="{{ lang('Last Name', 'forms') }}"
                                maxlength="50" value="{{ $user->lastname }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">{{ lang('Email address', 'forms') }}</label>
                            <input type="email" name="email" class="form-control" placeholder="{{ lang('Email address', 'forms') }}"
                                value="{{ $user->email }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">{{ lang('Address line 1', 'forms') }}</label>
                            <input type="text" name="address_1" class="form-control" value="{{ @$user->address->address_1 }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ lang('Address line 2', 'forms') }}</label>
                            <input type="text" name="address_2" class="form-control"
                                placeholder="{{ lang('Apartment, suite, etc. (optional)', 'account') }}" value="{{ @$user->address->address_2 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ lang('City', 'forms') }}</label>
                            <input type="text" name="city" class="form-control" value="{{ @$user->address->city }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ lang('State', 'forms') }}</label>
                            <input type="text" name="state" class="form-control" value="{{ @$user->address->state }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">{{ lang('Postal code', 'forms') }}</label>
                            <input type="text" name="zip" class="form-control" value="{{ @$user->address->zip }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">{{ lang('Country', 'forms') }}</label>
                            <select name="country" class="form-select" required>
                                @foreach (countries() as $country)
                                    <option value="{{ $country->id }}" {{ $country->name == @$user->address->country ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-primary">{{ lang('Save Changes', 'account') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
