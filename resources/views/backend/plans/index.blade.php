@extends('backend.layouts.grid')
@section('title', admin_lang('Pricing plans'))
@section('link', route('admin.plans.create'))
@section('content')
<div class="alert alert-danger d-flex align-items-center alert-dismissible" role="alert">
    <span class="alert-icon text-danger me-2">
        <i class="ti ti-info-circle ti-xs"></i>
    </span>
    {{ admin_lang('Please dont delete the plan below, just edit it. ') }}
</div>
<div class="card custom-card custom-tabs mb-3">
    <div class="card-body">
        <ul class="nav nav-pills" role="tablist">
            <li role="presentation">
                <button class="nav-link active me-2" id="monthly-tab" data-bs-toggle="tab" data-bs-target="#monthly"
                    type="button" role="tab" aria-controls="monthly"
                    aria-selected="true">{{ admin_lang('Monthly plans') }}
                    ({{ count($monthlyPlans ?? []) }})</button>
            </li>
            <li role="presentation">
                <button class="nav-link me-2" id="yearly-tab" data-bs-toggle="tab" data-bs-target="#yearly"
                    type="button" role="tab" aria-controls="yearly"
                    aria-selected="false">{{ admin_lang('Yearly plans') }}
                    ({{ count($yearlyPlans ?? []) }})</button>
            </li>
            <li role="presentation">
                <button class="nav-link me-2" id="weekly-tab" data-bs-toggle="tab" data-bs-target="#weekly"
                    type="button" role="tab" aria-controls="weekly"
                    aria-selected="false">{{ admin_lang('Weekly plans') }}
                    ({{ count($weeklyPlans ?? []) }})</button>
            </li>
            <li role="presentation">
                <button class="nav-link me-2" id="six-month-tab" data-bs-toggle="tab" data-bs-target="#six-month"
                    type="button" role="tab" aria-controls="six-month"
                    aria-selected="false">{{ admin_lang('6-Month plans') }}
                    ({{ count($halfYearlyPlans ?? []) }})</button>
            </li>
        </ul>
    </div>
</div>
<div class="card custom-card">
    <div class="tab-content">
        <div class="tab-pane fade show active" id="monthly" role="tabpanel" aria-labelledby="monthly-tab">
            <table class="dtable table w-100">
                <thead>
                    <tr>
                        <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Name') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Price') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Interval') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Created date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthlyPlans as $plan)
                        <tr class="item">
                            <td>{{ $plan->id }}</td>
                            <td>
                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="text-dark">
                                    {{ $plan->name }}
                                    {{ $plan->isFeatured() ? '(' . admin_lang('Featured') . ')' : '' }}
                                </a>
                            </td>
                            <td>
                                <strong>
                                    @if ($plan->isFree())
                                        <span class="text-success">{{ admin_lang('Free') }}</span>
                                    @else
                                        <span class="text-dark">{{ priceSymbolCode($plan->price) }}</span>
                                    @endif
                                </strong>
                            </td>
                            <td>{{ admin_lang('Monthly') }}</td>
                            <td>{{ dateFormat($plan->created_at) }}</td>
                            <td>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                        aria-expanded="true">
                                        <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.plans.edit', $plan->id) }}"><i
                                                    class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="billiongroup-able-to-delete dropdown-item text-danger"><i
                                                        class="ti ti-trash me-2"></i>{{ admin_lang('Delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade" id="yearly" role="tabpanel" aria-labelledby="yearly-tab">
            <table class="dtable table w-100">
                <thead>
                    <tr>
                        <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Name') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Price') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Interval') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Created date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($yearlyPlans as $plan)
                        <tr class="item">
                            <td>{{ $plan->id }}</td>
                            <td>
                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="text-dark">
                                    {{ $plan->name }}
                                    {{ $plan->isFeatured() ? '(' . admin_lang('Featured') . ')' : '' }}
                                </a>
                            </td>
                            <td>
                                <strong>
                                    @if ($plan->isFree())
                                        <span class="text-success">{{ admin_lang('Free') }}</span>
                                    @else
                                        <span class="text-dark">{{ priceSymbolCode($plan->price) }}</span>
                                    @endif
                                </strong>
                            </td>
                            <td>{{ admin_lang('Yearly') }}</td>
                            <td>{{ dateFormat($plan->created_at) }}</td>
                            <td>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                        aria-expanded="true">
                                        <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.plans.edit', $plan->id) }}"><i
                                                    class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="billiongroup-able-to-delete dropdown-item text-danger"><i
                                                        class="ti ti-trash me-2"></i>{{ admin_lang('Delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade" id="weekly" role="tabpanel" aria-labelledby="weekly-tab">
            <table class="dtable table w-100">
                <thead>
                    <tr>
                        <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Name') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Price') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Interval') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Created date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($weeklyPlans as $plan)
                        <tr class="item">
                            <td>{{ $plan->id }}</td>
                            <td>
                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="text-dark">
                                    {{ $plan->name }}
                                    {{ $plan->isFeatured() ? '(' . admin_lang('Featured') . ')' : '' }}
                                </a>
                            </td>
                            <td>
                                <strong>
                                    @if ($plan->isFree())
                                        <span class="text-success">{{ admin_lang('Free') }}</span>
                                    @else
                                        <span class="text-dark">{{ priceSymbolCode($plan->price) }}</span>
                                    @endif
                                </strong>
                            </td>
                            <td>{{ admin_lang('Weekly') }}</td>
                            <td>{{ dateFormat($plan->created_at) }}</td>
                            <td>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                        aria-expanded="true">
                                        <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.plans.edit', $plan->id) }}"><i
                                                    class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="billiongroup-able-to-delete dropdown-item text-danger"><i
                                                        class="ti ti-trash me-2"></i>{{ admin_lang('Delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="tab-pane fade" id="six-month" role="tabpanel" aria-labelledby="six-month-tab">
            <table class="dtable table w-100">
                <thead>
                    <tr>
                        <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Name') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Price') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Interval') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Created date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($halfYearlyPlans as $plan)
                        <tr class="item">
                            <td>{{ $plan->id }}</td>
                            <td>
                                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="text-dark">
                                    {{ $plan->name }}
                                    {{ $plan->isFeatured() ? '(' . admin_lang('Featured') . ')' : '' }}
                                </a>
                            </td>
                            <td>
                                <strong>
                                    @if ($plan->isFree())
                                        <span class="text-success">{{ admin_lang('Free') }}</span>
                                    @else
                                        <span class="text-dark">{{ priceSymbolCode($plan->price) }}</span>
                                    @endif
                                </strong>
                            </td>
                            <td>{{ admin_lang('6-Month') }}</td>
                            <td>{{ dateFormat($plan->created_at) }}</td>
                            <td>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                        aria-expanded="true">
                                        <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('admin.plans.edit', $plan->id) }}"><i
                                                    class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.plans.destroy', $plan->id) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button class="billiongroup-able-to-delete dropdown-item text-danger"><i
                                                        class="ti ti-trash me-2"></i>{{ admin_lang('Delete') }}</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection