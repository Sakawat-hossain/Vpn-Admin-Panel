<script type="text/javascript">
    "use strict";
    const BASE_URL = "{{ url(adminPath()) }}";
    const PRIMARY_COLOR = "{{ $settings->colors->primary_color }}";
    const SECONDARY_COLOR = "{{ $settings->colors->secondary_color }}";
</script>

@stack('top_scripts')
<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->

<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<!-- <script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script> -->
<!-- <script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script> -->
<script src="{{ asset('assets/vendor/libs/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/node-waves/node-waves.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/toggle-master/bootstrap-toggle.min.js') }}"></script>
@stack('scripts_libs')
<script src="{{ asset('assets/js/application.js') }}"></script>
<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
const dtable = $(".dtable").DataTable();
if (dtable) {
    $('.table-responsive').on('show.bs.dropdown', function () {
        $('.table-responsive').css( "overflow", "inherit" );
    });

    $('.table-responsive').on('hide.bs.dropdown', function () {
        $('.table-responsive').css( "overflow", "auto" );
    })
}
</script>

<!-- Page JS -->
@toastr_render
@stack('scripts')
