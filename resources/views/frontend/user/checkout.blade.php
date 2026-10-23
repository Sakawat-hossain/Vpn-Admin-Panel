@extends('frontend.user.layouts.app')
@section('title', lang('Checkout', 'checkout'))
@section('page-pretitle', lang('Billing', 'account'))
@section('page-title', lang('Checkout', 'checkout'))
@section('content')
    @if ($transaction->type == 4 || $transaction->type == 3)
        <div class="alert alert-info" role="alert">
            <div class="d-flex">
                <i class="ti ti-info-circle alert-icon"></i>
                <div>
                    <h4 class="alert-title">{{ lang('Important Notice !', 'checkout') }}</h4>
                    <div class="text-secondary">
                        {{ $transaction->type == 4 ? lang('downgrading notice', 'checkout') : lang('upgrading notice', 'checkout') }}
                    </div>
                </div>
            </div>
        </div>
    @endif
    @php
        $payButton = $transaction->total != 0 ? lang('Pay Now', 'checkout') : lang('Continue', 'checkout');
    @endphp
    <div class="row g-3">
        <div class="col-12 col-lg-7 col-xl-8 order-2 order-lg-1">
            <form id="checkoutForm" action="{{ route('checkout.proccess', $transaction->checkout_id) }}" method="POST">
                @csrf
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ lang('Payment Methods', 'checkout') }}</h3>
                    </div>
                    <div class="card-body">
                        @if ($transaction->total != 0)
                            @if ($paymentGateways->count() > 0)
                                <div class="form-selectgroup form-selectgroup-boxes d-flex flex-column flex-md-row flex-wrap">
                                    @foreach ($paymentGateways as $paymentGateway)
                                        <label class="form-selectgroup-item flex-fill payment-option">
                                            <input type="radio" name="payment_method" value="{{ $paymentGateway->id }}"
                                                id="{{ $paymentGateway->alias }}" class="form-selectgroup-input"
                                                {{ $loop->first ? 'checked' : '' }}>
                                            <div class="form-selectgroup-label">
                                                <span class="form-selectgroup-check"></span>
                                                <img src="{{ asset($paymentGateway->logo) }}" alt="{{ $paymentGateway->name }}">
                                                <span class="fw-medium">{{ $paymentGateway->name }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    {{ lang('No payment methods available right now please try again later.', 'checkout') }}
                                </div>
                            @endif
                        @else
                            <div class="alert alert-info mb-0">{{ lang('No payment method needed.', 'checkout') }}</div>
                        @endif
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ lang('Billing address', 'checkout') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">{{ lang('First Name', 'forms') }}</label>
                                <input type="text" class="form-control" placeholder="{{ lang('First Name', 'forms') }}"
                                    value="{{ $user->firstname }}" readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">{{ lang('Last Name', 'forms') }}</label>
                                <input type="text" class="form-control" placeholder="{{ lang('Last Name', 'forms') }}"
                                    value="{{ $user->lastname }}" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">{{ lang('Address line 1', 'forms') }}</label>
                                <input type="text" name="address_1" class="form-control" value="{{ @$user->address->address_1 }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ lang('Address line 2', 'forms') }}</label>
                                <input type="text" name="address_2" class="form-control"
                                    placeholder="{{ lang('Apartment, suite, etc. (optional)', 'checkout') }}" value="{{ @$user->address->address_2 }}">
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
                </div>
            </form>
            <button form="checkoutForm" class="btn btn-primary btn-lg w-100 mt-3 d-lg-none">{{ $payButton }}</button>
        </div>
        <div class="col-12 col-lg-5 col-xl-4 order-1 order-lg-2">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">{{ lang('Order Summary', 'checkout') }}</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ $transaction->plan->name . ' ' . lang('Plan', 'checkout') }} ({{ formatInterval($transaction->plan->interval) }})</span>
                        <span class="fw-medium">{{ priceSymbol($transaction->details_before_discount->price) }}</span>
                    </div>
                    @if ($transaction->details_before_discount->tax != 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ lang('Tax', 'checkout') }}</span>
                            <span class="fw-medium">+{{ priceSymbol($transaction->details_before_discount->tax) }}</span>
                        </div>
                    @endif
                    @if ($transaction->coupon)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ lang('Subtotal', 'checkout') }}</span>
                            <span class="fw-medium">{{ priceSymbol($transaction->details_before_discount->total) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>{{ lang('Discount', 'checkout') }} ({{ $transaction->coupon->percentage }}%)</span>
                            <span class="fw-medium">-{{ priceSymbol($transaction->details_before_discount->total - $transaction->total) }}</span>
                        </div>
                    @endif
                    <div class="hr my-3"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h3 mb-0">{{ lang('Total', 'checkout') }}</span>
                        <span class="h3 mb-0 text-primary">{{ priceSymbol($transaction->total) }}</span>
                    </div>
                    @if ($transaction->total != 0)
                        <div class="alert alert-warning mb-0 mt-3">{{ lang('Payment gateways may charge extra fees', 'checkout') }}</div>
                    @endif
                </div>
            </div>
            @if (!$transaction->plan->isFree())
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">{{ lang('Coupon Code', 'checkout') }}</h3>
                    </div>
                    <div class="card-body">
                        @if (!$transaction->coupon)
                            <form action="{{ route('checkout.coupon.apply', $transaction->checkout_id) }}" method="POST">
                                @csrf
                                <div class="input-group">
                                    <input type="text" name="coupon_code" class="form-control"
                                        placeholder="{{ lang('Enter coupon code', 'checkout') }}" maxlength="20"
                                        value="{{ old('coupon_code') }}" required>
                                    <button class="btn btn-primary">{{ lang('Apply', 'checkout') }}</button>
                                </div>
                            </form>
                        @else
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary-lt fs-5"><i class="ti ti-ticket me-1"></i>{{ $transaction->coupon->code }}</span>
                                <form action="{{ route('checkout.coupon.remove', $transaction->checkout_id) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-ghost-danger btn-icon btn-sm action-confirm" title="{{ lang('Remove', 'checkout') }}">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            <div class="card mb-3">
                <div class="card-body d-flex">
                    <i class="ti ti-shield-lock fs-1 text-success me-3"></i>
                    <div>
                        <div class="fw-bold text-uppercase mb-1">{{ lang('SSL Secure Payment', 'checkout') }}</div>
                        <div class="text-secondary">{{ lang('Your information is protected by 256-bit SSL encryption', 'checkout') }}</div>
                    </div>
                </div>
            </div>
            <button form="checkoutForm" class="btn btn-primary btn-lg w-100 d-none d-lg-block">{{ $payButton }}</button>
        </div>
    </div>
@endsection
