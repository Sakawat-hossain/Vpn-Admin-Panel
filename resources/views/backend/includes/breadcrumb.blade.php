@php
    $breadcrumbSegments = request()->segments();
    $breadcrumbLast = count($breadcrumbSegments) - 1;
    $breadcrumbPath = '';
@endphp
<ol class="breadcrumb breadcrumb-arrows mb-1 capitalize" aria-label="breadcrumbs">
    @foreach ($breadcrumbSegments as $index => $segment)
        @php $breadcrumbPath .= '/' . $segment; @endphp
        <li class="breadcrumb-item {{ $index === $breadcrumbLast ? 'active' : '' }}">
            @if ($index === $breadcrumbLast)
                {{ $segment }}
            @else
                <a href="{{ url($breadcrumbPath) }}">{{ $segment }}</a>
            @endif
        </li>
    @endforeach
</ol>
