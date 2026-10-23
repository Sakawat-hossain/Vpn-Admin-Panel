@php $only = $only ?? null; @endphp
@if ($only !== 'access')
    @if ($server->isOpenVpn())
        <span class="badge bg-orange-lt">OpenVPN</span>
    @else
        <span class="badge bg-blue-lt">WireGuard</span>
    @endif
@endif
@if ($only !== 'protocol')
    @if ($server->isPremium())
        <span class="badge bg-yellow-lt"><i class="ti ti-crown me-1"></i>{{ lang('Premium', 'account') }}</span>
    @else
        <span class="badge bg-green-lt">{{ lang('Free', 'account') }}</span>
    @endif
@endif
