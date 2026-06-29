@extends('backend.layouts.application')
@section('title', admin_lang('Dashboard'))
@section('access', admin_lang('Quick Access'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('content')
  @if (!$settings->smtp->status)
    <div class="alert alert-danger d-flex align-items-center alert-dismissible" role="alert">
      <span class="alert-icon text-danger me-2">
        <i class="ti ti-info-circle ti-xs"></i>
      </span>
      {{ admin_lang('SMTP is not enabled, set it now to be able to recover the password and use all the features that needs to send an email. ') }}
      <a href="{{ route('admin.settings.smtp.index') }}">{{ admin_lang('Take Action') }}</a>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
  <div class="row mb-4">
    <div class="col-md-6 col-lg-4"> 
      <div class="card mb-4 h-100">
        <div class="card-header d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Recently transactions') }}</h5>
          </div>
          <div class="dropdown">
            <a class="" href="{{ route('admin.transactions.index') }}">{{ admin_lang('View All') }}</a>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive text-nowrap">
            @if (count($transactions) > 0)
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ admin_lang('Transaction') }}</th>
                    <th>{{ admin_lang('Date & Time') }}</th>
                    <th>{{ admin_lang('Amount') }}</th>
                  </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                  @forelse ($transactions as $transaction)
                    <tr>
                      <td>
                        <a href="{{ route('admin.transactions.edit', $transaction->id) }}">
                          {{ $transaction->user->name }}
                        </a>
                      </td>
                      <td>{{ $transaction->created_at->diffforhumans() }}</td>
                      <td><strong>{{ priceSymbol($transaction->total) }}</strong></td>
                    </tr>
                  @empty
                  @endforelse
                </tbody>
              </table>
            @else
              @include('backend.includes.emptysmall')
            @endif
          </div>
        </div>
      </div>
    </div>
   
    <div class="col-md-6 col-lg-8">
      <div class="card">
      <div class="card-header d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Recently transactions') }}</h5>
          </div>
          <div class="dropdown">
            <a class="" href="{{ route('admin.transactions.index') }}">{{ admin_lang('View All') }}</a>
          </div>
        </div>
        <div class="card-body">
          <div id="revenue-chart"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="row mb-4">
    <div class="col-lg-3 col-sm-6 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ priceSymbol($widget['total_earnings']) }}</h5>
          <small>{{ admin_lang('Total Earnings') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-primary rounded-pill p-2">
            <i class='ti ti-report-money ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ priceSymbol($widget['current_month_earnings']) }}</h5>
          <small>{{ admin_lang('Current Month Earnings') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-success rounded-pill p-2">
            <i class='ti ti-report-money ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ number_format($widget['total_users']) }}</h5>
          <small>{{ admin_lang('Total Users') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-danger rounded-pill p-2">
            <i class='ti ti-users ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-sm-6 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ number_format($widget['current_month_users']) }}</h5>
          <small>{{ admin_lang('Current Month Users') }}</small>
        </div>
        <div class="card-icon">
          <span class="badge bg-label-warning rounded-pill p-2">
            <i class='ti ti-users ti-sm'></i>
          </span>
        </div>
      </div>
    </div>
  </div>
  </div>
  <div class="row mb-4">
    <div class="col-md-6 col-lg-8">
      <div class="card mb-4 h-100">
      <div class="card-header">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Users Statistics For This Week') }}</h5>
          </div>
        </div>
        <div class="card-body">
          <div id="area-chart"></div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4"> 
      <div class="card mb-4 h-100">
        <div class="card-header d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Recently registered') }}</h5>
          </div>
          <div class="dropdown">
            <a class="" href="{{ route('admin.users.index') }}">{{ admin_lang('View All') }}</a>
          </div>
        </div>
        <div class="card-body">
          <ul class="p-0 m-0">
            @forelse ($users as $user)
              <li class="d-flex align-items-center mb-4">
                <div class="avatar flex-shrink-0 me-3">
                  <img src="{{ asset($user->avatar) }}" />
                </div>
                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                  <div class="me-2">
                    <div class="d-flex align-items-center">
                      <h6 class="mb-0 me-1">
                        <a class=""
                          href="{{ route('admin.users.edit', $user->id) }}">{{ $user->name }}</a>
                      </h6>
                    </div>
                    <small class="text-muted">{{ $user->created_at->diffforhumans() }}</small>
                  </div>
                  <div class="user-progress">
                    <a href="{{ route('admin.users.edit', $user->id) }}"
                      class="btn btn-primary btn-sm"><i class="ti ti-eye ti-xs"></i></a>
                  </div>
                </div>
              </li>
            @empty
              <li>{{ admin_lang('No data found') }}</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-6 col-lg-4">
      <div class="card mb-4 h-100">
        <div class="card-header d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Login Statistics - Browsers') }}</h5>
          </div>
          <div>
            <small class="text-muted ms-auto">({{ carbon()->now()->format('F') }})</small>
          </div>
        </div>
        <div class="card-body">
          @if ($countUsersLogs)
            <div class="py-6" id="pie-browsers"></div>
          @else
            @include('backend.includes.emptysmall')
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="card mb-4 h-100">
        <div class="card-header d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Login Statistics - Country') }}</h5>
          </div>
          <div>
            <small class="text-muted ms-auto">({{ carbon()->now()->format('F') }})</small>
          </div>
        </div>
        <div class="card-body">
          @if ($countUsersLogs)
            <div class="py-6" id="pie-country"></div>
          @else
            @include('backend.includes.emptysmall')
          @endif
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-4">
      <div class="card mb-4 h-100">
        <div class="card-header d-flex justify-content-between">
          <div class="card-title mb-0">
            <h5 class="mb-0">{{ admin_lang('Login Statistics - OS') }}</h5>
          </div>
          <div>
            <small class="text-muted ms-auto">({{ carbon()->now()->format('F') }})</small>
          </div>
        </div>
        <div class="card-body">
          @if ($countUsersLogs)
            <div class="py-6" id="pie-os"></div>
          @else
            @include('backend.includes.emptysmall')
          @endif
        </div>
      </div>
    </div>
  </div>
  @push('styles_libs')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}" />
  @endpush
  @push('top_scripts')
    <script type="text/javascript">
      "use strict";
      const CURRENCY_CODE = "{{ $settings->currency->symbol }}";
      const CURRENCY_POSITION = "{{ $settings->currency->position }}";
    </script>
  @endpush
  @push('scripts_libs')
    <script src="{{ asset('assets/vendor/libs/chartjs/chartjs.js') }}"></script>
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
  @endpush
  @push('scripts')
    <script src="{{ asset('assets/js/apexcharts.js') }}"></script>
  @endpush
@endsection
