<script type="text/javascript">
    "use strict";
    const BASE_URL = "{{ url(adminPath()) }}";
    const PRIMARY_COLOR = "{{ $settings->colors->primary_color }}";
    const SECONDARY_COLOR = "{{ $settings->colors->secondary_color }}";
</script>
@stack('top_scripts')
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/tabler/js/tabler.min.js') }}"></script>
<script>
    // Tabler bundles Bootstrap; keep the usual global for page scripts and plugins.
    window.bootstrap = window.bootstrap || tabler.bootstrap;
    // bootstrap-colorpicker calls Popover#getTipElement(), public before Bootstrap 5.3.
    if (bootstrap.Popover && !bootstrap.Popover.prototype.getTipElement) {
        bootstrap.Popover.prototype.getTipElement = function () {
            return this._getTipElement();
        };
    }
</script>
<script src="{{ asset('assets/vendor/libs/toastr/toastr.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/toggle-master/bootstrap-toggle.min.js') }}"></script>
@stack('scripts_libs')
<script src="{{ asset('assets/js/application.js') }}"></script>
<script src="{{ asset('assets/js/panel-theme.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<script>
    const dtable = $(".dtable").DataTable();
    if (dtable) {
        $('.table-responsive').on('show.bs.dropdown', function () {
            $('.table-responsive').css("overflow", "inherit");
        });
        $('.table-responsive').on('hide.bs.dropdown', function () {
            $('.table-responsive').css("overflow", "auto");
        })
    }
</script>
@toastr_render
@stack('scripts')
