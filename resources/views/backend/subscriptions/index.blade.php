@extends('backend.layouts.grid')
@section('title', admin_lang('Subscriptions'))
@section('add_modal', admin_lang('Add New'))
@section('content')
<div class="alert alert-danger d-flex align-items-center alert-dismissible" role="alert">
      <span class="alert-icon text-danger me-2">
        <i class="ti ti-info-circle ti-xs"></i>
      </span>
      {{ admin_lang('We recommend not deleting users who have expired and canceled so that the apps run well. ') }}
     
    </div>
    
    <div class="card custom-card">
    <div class="card custom-card custom-tabs mb-3">
        <div class="card-body">
            <ul class="nav nav-pills" role="tablist">
                <li role="presentation">
                    <button class="nav-link active me-2" id="active-tab" data-bs-toggle="tab" data-bs-target="#active"
                        type="button" role="tab" aria-controls="active" aria-selected="true"><i
                            class="ti ti-circle-check me-2"></i>{{ admin_lang('Active') }}
                        ({{ $activeSubscriptions->count() }})
                    </button>
                </li>
                <li role="presentation">
                    <button class="nav-link me-2" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired"
                        type="button" role="tab" aria-controls="expired" aria-selected="false"><i
                            class="ti ti-clock me-2"></i>{{ admin_lang('Expired') }}
                        ({{ $expiredSubscriptions->count() }})
                    </button>
                </li>
                @if ($canceledSubscriptions->count() > 0)
                    <li role="presentation">
                        <button class="nav-link" id="canceled-tab" data-bs-toggle="tab" data-bs-target="#canceled"
                            type="button" role="tab" aria-controls="canceled" aria-selected="false"><i
                                class="far fa-times-circle me-2"></i>{{ admin_lang('Canceled') }}
                            ({{ $canceledSubscriptions->count() }})
                        </button>
                    </li>
                @endif
            </ul>
            @hasSection('link')
            <li role="presentation">
                        <button class="nav-link" id="canceled-tab" data-bs-toggle="tab" data-bs-target="#canceled"
                            type="button" role="tab" aria-controls="canceled" aria-selected="false"><i
                                class="far fa-times-circle me-2"></i> <a href="@yield('link')" class="btn btn-primary ms-2"><i class="fa fa-plus"></i></a>
                        </button>
                    </li>
                               
                            @endif
            
        </div>
    </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
                <table class="dtable table w-100">
                    <thead>
                        <tr>
                            <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                            <th class="tb-w-20x">{{ admin_lang('User details') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Plan') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Subscribe at') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Expiring at') }}</th>
                            <th class="tb-w-3x">{{ admin_lang('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activeSubscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->id }}</td>
                                <td>
                                    <div class="d-flex justify-content-first align-items-center">
                                        <a class="me-2"
                                        href="{{ route('admin.users.edit', $subscription->user->id) }}">
                                        <img src="{{ asset($subscription->user->avatar) }}" alt="User" width="35"/>
                                        </a>
                                        <div>
                                            <a class="text-reset"
                                            href="{{ route('admin.users.edit', $subscription->user->id) }}">
                                                {{ $subscription->user->name }}</a>
                                            <p class="text-muted mb-0">{{ $subscription->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td><a href="{{ route('admin.plans.edit', $subscription->plan->id) }}"><i
                                            class="ti ti-diamond me-2"></i>
                                        {{ $subscription->plan->name }}
                                    </a>
                                </td>
                                <td>{{ dateFormat($subscription->created_at) }}</td>
                                <td>
                                    <span class="{{ $subscription->isExpired() ? 'text-danger' : 'text-dark' }}">
                                        {{ dateFormat($subscription->expiry_at) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($subscription->isExpired())
                                        <span class="badge bg-danger">{{ admin_lang('Expired') }}</span>
                                    @elseif($subscription->isActive())
                                        <span class="badge bg-success">{{ admin_lang('Active') }}</span>
                                    @else
                                        <span class="badge bg-lg-4">{{ admin_lang('Canceled') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                            aria-expanded="true">
                                            <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg"
                                            data-popper-placement="bottom-end">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.subscriptions.edit', $subscription->id) }}"><i
                                                        class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.users.edit', $subscription->user->id) }}"><i
                                                        class="fa fa-user me-2"></i>{{ admin_lang('User details') }}</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider" />
                                            </li>
                                            <li>
                                                <form
                                                    action="{{ route('admin.subscriptions.destroy', $subscription->id) }}"
                                                    method="POST">
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
            <div class="tab-pane fade" id="expired" role="tabpanel" aria-labelledby="expired-tab">
                <table class="dtable table w-100">
                    <thead>
                        <tr>
                            <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                            <th class="tb-w-20x">{{ admin_lang('User details') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Plan') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Subscribe at') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Expiring at') }}</th>
                            <th class="tb-w-3x">{{ admin_lang('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expiredSubscriptions as $subscription)
                            <tr>
                                <td>{{ $subscription->id }}</td>
                                <td>
                                    <div class="d-flex justify-content-first align-items-center">
                                        <a class="me-2"
                                        href="{{ route('admin.users.edit', $subscription->user->id) }}">
                                        <img src="{{ asset($subscription->user->avatar) }}" alt="User" width="35"/>
                                        </a>
                                        <div>
                                            <a class="text-reset"
                                            href="{{ route('admin.users.edit', $subscription->user->id) }}">
                                                {{ $subscription->user->name }}</a>
                                            <p class="text-muted mb-0">{{ $subscription->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td><a href="{{ route('admin.plans.edit', $subscription->plan->id) }}"><i
                                            class="ti ti-diamond me-2"></i>
                                        {{ $subscription->plan->name }}
                                    </a>
                                </td>
                                <td>{{ dateFormat($subscription->created_at) }}</td>
                                <td>
                                    <span class="{{ $subscription->isExpired() ? 'text-danger' : 'text-dark' }}">
                                        {{ dateFormat($subscription->expiry_at) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($subscription->isExpired())
                                        <span class="badge bg-danger">{{ admin_lang('Expired') }}</span>
                                    @elseif($subscription->isActive())
                                        <span class="badge bg-success">{{ admin_lang('Active') }}</span>
                                    @else
                                        <span class="badge bg-lg-4">{{ admin_lang('Canceled') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                            aria-expanded="true">
                                            <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg"
                                            data-popper-placement="bottom-end">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.subscriptions.edit', $subscription->id) }}"><i
                                                        class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.users.edit', $subscription->user->id) }}"><i
                                                        class="fa fa-user me-2"></i>{{ admin_lang('User details') }}</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider" />
                                            </li>
                                            <li>
                                                <form
                                                    action="{{ route('admin.subscriptions.destroy', $subscription->id) }}"
                                                    method="POST">
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
            @if ($canceledSubscriptions->count() > 0)
                <div class="tab-pane fade" id="canceled" role="tabpanel" aria-labelledby="canceled-tab">
                    <table class="dtable table w-100">
                        <thead>
                            <tr>
                                <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                                <th class="tb-w-20x">{{ admin_lang('User details') }}</th>
                                <th class="tb-w-7x">{{ admin_lang('Plan') }}</th>
                                <th class="tb-w-7x">{{ admin_lang('Subscribe at') }}</th>
                                <th class="tb-w-7x">{{ admin_lang('Expiring at') }}</th>
                                <th class="tb-w-3x">{{ admin_lang('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($canceledSubscriptions as $subscription)
                                <tr>
                                    <td>{{ $subscription->id }}</td>
                                    <td>
                                    <div class="d-flex justify-content-first align-items-center">
                                        <a class="me-2"
                                        href="{{ route('admin.users.edit', $subscription->user->id) }}">
                                        <img src="{{ asset($subscription->user->avatar) }}" alt="User" width="35"/>
                                        </a>
                                        <div>
                                            <a class="text-reset"
                                            href="{{ route('admin.users.edit', $subscription->user->id) }}">
                                                {{ $subscription->user->name }}</a>
                                            <p class="text-muted mb-0">{{ $subscription->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                    <td><a href="{{ route('admin.plans.edit', $subscription->plan->id) }}"
                                            style="color: {{ $subscription->plan->color }}"><i
                                                class="ti ti-diamond me-2"></i>
                                            {{ $subscription->plan->name }}
                                        </a>
                                    </td>
                                    <td>{{ dateFormat($subscription->created_at) }}</td>
                                    <td>
                                        <span class="{{ $subscription->isExpired() ? 'text-danger' : 'text-dark' }}">
                                            {{ dateFormat($subscription->expiry_at) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($subscription->isExpired())
                                            <span class="badge bg-danger">{{ admin_lang('Expired') }}</span>
                                        @elseif($subscription->isActive())
                                            <span class="badge bg-success">{{ admin_lang('Active') }}</span>
                                        @else
                                            <span class="badge bg-lg-4">{{ admin_lang('Canceled') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                                aria-expanded="true">
                                                <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg"
                                                data-popper-placement="bottom-end">
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.subscriptions.edit', $subscription->id) }}"><i
                                                            class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.users.edit', $subscription->user->id) }}"><i
                                                            class="fa fa-user me-2"></i>{{ admin_lang('User details') }}</a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider" />
                                                </li>
                                                <li>
                                                    <form
                                                        action="{{ route('admin.subscriptions.destroy', $subscription->id) }}"
                                                        method="POST">
                                                        @csrf @method('DELETE')
                                                        <button
                                                            class="billiongroup-able-to-delete dropdown-item text-danger"><i
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
            @endif
        </div>
    </div>
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-3">
                    <h6 class="modal-title" id="addModalLabel">{{ admin_lang('New Subscription') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.subscriptions.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ admin_lang('User') }} : <span class="text-danger">*</span></label>
                            <select id="billiongroup-select-user" name="user" class="form-select select2Modal" required>
                                <option></option>
                                @foreach ($users as $user)
                                    @if (!$user->isSubscribed())
                                        <option value="{{ $user->id }}"
                                            {{ old('user') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ admin_lang('Plan') }} : <span class="text-danger">*</span></label>
                            <select name="plan" class="form-select" required>
                                <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                        {{ old('plan') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                        {{ $plan->interval == 1 ? admin_lang('(Monthly)') : admin_lang('(Yearly)') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary">{{ admin_lang('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
