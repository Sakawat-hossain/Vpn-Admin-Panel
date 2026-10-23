@if ($transaction->isPaid())
    @if ($transaction->total > 0)
        <span class="badge bg-success-lt">{{ lang('Paid', 'account') }}</span>
    @else
        <span class="badge bg-primary-lt">{{ lang('Done', 'account') }}</span>
    @endif
@else
    <span class="badge bg-danger-lt">{{ lang('Cancelled', 'account') }}</span>
@endif
