@extends('frontend.layouts.single')
@section('title', lang('Pricing', 'pages'))
@section('content')
{!! ads_other_pages_top() !!}
<div class="section-header text-center mb-5">
    <h1 class="mb-3">{{ lang('Pricing', 'pages') }}</h1>
    <p class="fw-light text-muted mb-0">
        {{ lang('Pricing description', 'pages') }}
    </p>
</div>
<div class="plans">
    <div class="d-flex flex-wrap justify-content-center align-items-stretch g-3">
        @foreach ($weeklyPlans as $plan)
            <div class="card plan-card p-4 {{ $plan->isFeatured() ? 'bg-primary text-white' : '' }} me-4">
                <div class="plan {{ $plan->isFeatured() ? 'pro' : '' }}">
                    <div class="plan-header">
                        <h3 class="text-white">{{ $plan->name }}</h3>
                    </div>
                    <div class="plan-body">
                        <hr />
                        <div class="plan-price d-flex justify-content-center align-items-center">
                            @if ($plan->isFree())
                                <p class="mb-0">{{ lang('Free', 'plans') }}</p>
                            @else
                                <p class="mb-0">{{ priceSymbol($plan->price) }}</p>
                                <span>/{{ formatInterval($plan->interval) }}</span>
                            @endif
                        </div>
                        <hr />
                    </div>
                    <div class="plan-footer">
                        {!! planButton($plan) !!}
                    </div>
                </div>
            </div>
        @endforeach
        @foreach ($monthlyPlans as $plan)
            <div class="card plan-card p-4 {{ $plan->isFeatured() ? 'bg-primary text-white' : '' }} me-4">
                <div class="plan {{ $plan->isFeatured() ? 'pro' : '' }}">
                    <div class="plan-header">
                        <h3 class="text-white">{{ $plan->name }}</h3>
                    </div>
                    <div class="plan-body">
                        <hr />
                        <div class="plan-price d-flex justify-content-center align-items-center">
                            @if ($plan->isFree())
                                <p class="mb-0">{{ lang('Free', 'plans') }}</p>
                            @else
                                <p class="mb-0">{{ priceSymbol($plan->price) }}</p>
                                <span>/{{ formatInterval($plan->interval) }}</span>
                            @endif
                        </div>
                        <hr />
                    </div>
                    <div class="plan-footer">
                        {!! planButton($plan) !!}
                    </div>
                </div>
            </div>
        @endforeach
        @foreach ($halfYearlyPlans as $plan)
            <div class="card plan-card p-4 {{ $plan->isFeatured() ? 'bg-primary text-white' : '' }} me-4">
                <div class="plan {{ $plan->isFeatured() ? 'pro' : '' }}">
                    <div class="plan-header">
                        <h3 class="text-white">{{ $plan->name }}</h3>
                    </div>
                    <div class="plan-body">
                        <hr />
                        <div class="plan-price d-flex justify-content-center align-items-center">
                            @if ($plan->isFree())
                                <p class="mb-0">{{ lang('Free', 'plans') }}</p>
                            @else
                                <p class="mb-0">{{ priceSymbol($plan->price) }}</p>
                                <span>/{{ formatInterval($plan->interval) }}</span>
                            @endif
                        </div>
                        <hr />
                    </div>
                    <div class="plan-footer">
                        {!! planButton($plan) !!}
                    </div>
                </div>
            </div>
        @endforeach


        @foreach ($yearlyPlans as $plan)
            <div class="card plan-card p-4 {{ $plan->isFeatured() ? 'bg-primary text-white' : '' }} me-4">
                <div class="plan {{ $plan->isFeatured() ? 'pro' : '' }}">
                    <div class="plan-header">
                        <h3 class="text-white">{{ $plan->name }}</h3>
                    </div>
                    <div class="plan-body">
                        <hr />
                        <div class="plan-price d-flex justify-content-center align-items-center">
                            @if ($plan->isFree())
                                <p class="mb-0">{{ lang('Free', 'plans') }}</p>
                            @else
                                <p class="mb-0">{{ priceSymbol($plan->price) }}</p>
                                <span>/{{ formatInterval($plan->interval) }}</span>
                            @endif
                        </div>
                        <hr />
                    </div>
                    <div class="plan-footer">
                        {!! planButton($plan) !!}
                    </div>
                </div>
            </div>
        @endforeach


    </div>
</div>
{!! ads_other_pages_bottom() !!}
@endsection