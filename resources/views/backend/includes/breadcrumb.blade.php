<div>
  <h4 class="pt-3"><span class="text-muted fw-light capitalize">@yield('title')</h4>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb breadcrumb-style1 capitalize">
      <?php $segments = ''; ?>
        @foreach (request()->segments() as $segment)
        <?php $segments .= '/' . $segment; ?>
        <li class="breadcrumb-item  @if(request()->segment(count(request()->segments())) == $segment) active @endif">
            @if(request()->segment(count(request()->segments())) != $segment)
            <a href="{{ url($segments) }}">{{ $segment }}</a>
        @else
          {{ $segment }}
        @endif
        </li>
      @endforeach
    </ol>
  </nav>
</div>