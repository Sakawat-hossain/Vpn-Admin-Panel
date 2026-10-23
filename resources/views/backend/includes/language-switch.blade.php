<div class="dropdown">
    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ti ti-language icon"></i>{{ $active }}
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        @foreach ($adminLanguages as $adminLanguage)
            <a class="dropdown-item @if ($adminLanguage->name == $active) active @endif"
                href="?lang={{ $adminLanguage->code }}">{{ $adminLanguage->name }}</a>
        @endforeach
    </div>
</div>
