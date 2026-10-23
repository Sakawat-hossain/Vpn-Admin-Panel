@extends('frontend.user.layouts.app')
@section('title', lang('Payment History', 'account'))
@section('page-pretitle', lang('Billing', 'account'))
@section('page-title', lang('Payment History', 'account'))
@section('content')
    <div class="card">
        @if ($transactions->count() > 0)
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>{{ lang('ID', 'account') }}</th>
                            <th>{{ lang('Price', 'account') }}</th>
                            <th class="d-none d-lg-table-cell">{{ lang('Payment Method', 'account') }}</th>
                            <th>{{ lang('Date', 'account') }}</th>
                            <th class="text-end">{{ lang('Status', 'account') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td class="text-secondary">#{{ $transaction->id }}</td>
                                <td class="fw-medium">{{ priceSymbol($transaction->total) }}</td>
                                <td class="d-none d-lg-table-cell">{{ $transaction->gateway->name ?? '--' }}</td>
                                <td>{{ dateFormat($transaction->created_at) }}</td>
                                <td class="text-end">
                                    @include('frontend.user.includes.transaction-status', ['transaction' => $transaction])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($transactions->hasPages())
                <div class="card-footer d-flex align-items-center">
                    {{ $transactions->links() }}
                </div>
            @endif
        @else
            <div class="card-body">
                <div class="empty">
                    <div class="empty-icon"><i class="ti ti-receipt-off icon"></i></div>
                    <p class="empty-title">{{ lang('Empty Section', 'account') }}</p>
                    <p class="empty-subtitle text-secondary">
                        {{ lang('This section is currently empty and has no content, please check it later.', 'account') }}
                    </p>
                </div>
            </div>
        @endif
    </div>
@endsection
