<footer class="footer footer-transparent d-print-none">
    <div class="container-xl">
        <div class="row text-center align-items-center">
            <div class="col-12">
                &copy; {{ date('Y') }}
                <a href="{{ route('home') }}" class="link-secondary">{{ $settings->general->site_name }}</a>
                <span class="text-secondary">&middot; {{ lang('Powered by') }} OVPN<sup>+</sup></span>
            </div>
        </div>
    </div>
</footer>
