<footer class="footer footer-transparent d-print-none">
    <div class="container-xl">
        <div class="row text-center align-items-center">
            <div class="col-12">
                &copy; {{ date('Y') }}
                <a href="{{ route('home') }}" class="link-secondary">{{ $settings->general->site_name }}</a>.
                {{ lang('All rights reserved') }}.
            </div>
        </div>
    </div>
</footer>
