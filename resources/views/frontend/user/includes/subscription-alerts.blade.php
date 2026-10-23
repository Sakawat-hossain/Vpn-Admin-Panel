@php $alertSubscription = auth()->user()->subscription; @endphp
@if (!request()->routeIs('checkout.*') && $alertSubscription && $alertSubscription->plan)
    @if ($alertSubscription->isCancelled())
        <div class="alert alert-danger" role="alert">
            <div class="d-flex">
                <i class="ti ti-alert-triangle alert-icon"></i>
                <div>{{ lang('subscription cancelled notice', 'account') }}</div>
            </div>
        </div>
    @elseif ($alertSubscription->isExpired())
        <div class="alert alert-danger" role="alert">
            <div class="d-flex">
                <i class="ti ti-alert-triangle alert-icon"></i>
                <div>
                    <h4 class="alert-title">{{ lang('Action Required!', 'account') }}</h4>
                    <div class="text-secondary">{{ lang('subscription expired notice', 'account') }}</div>
                </div>
            </div>
        </div>
    @elseif ($alertSubscription->isAboutToExpire())
        <div class="alert alert-warning" role="alert">
            <div class="d-flex">
                <i class="ti ti-clock-exclamation alert-icon"></i>
                <div>
                    <h4 class="alert-title">{{ lang('Action Required!', 'account') }}</h4>
                    <div class="text-secondary">{{ lang('subscription about to expire notice', 'account') }}</div>
                </div>
            </div>
        </div>
    @endif
@endif
