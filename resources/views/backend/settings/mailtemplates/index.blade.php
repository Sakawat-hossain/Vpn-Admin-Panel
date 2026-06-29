@extends('backend.layouts.grid')
@section('title', $active . ' ' . admin_lang('Mail Templates'))
@section('section', admin_lang('Settings'))
@section('language', true)
@section('content')
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="dtable table ask-datatable w-100">
                <thead>
                    <tr>
                        <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                        <th class="tb-w-2x">{{ admin_lang('Language') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Name') }}</th>
                        <th class="tb-w-7x">{{ admin_lang('Subject') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($mailTemplates as $mailTemplate)
                        <tr class="item">
                            <td>{{ $mailTemplate->id }}</td>
                            <td><a href="{{ route('admin.settings.languages.translates', $mailTemplate->lang) }}"><i
                                        class="ti ti-language me-2"></i>{{ $mailTemplate->language->name }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.settings.mailtemplates.edit', $mailTemplate->id) }}"
                                    class="text-dark"><i class="fas fa-envelope me-2"></i>{{ $mailTemplate->name }}</a>
                            </td>
                            <td>{{ $mailTemplate->subject }}</td>
                            <td>
                                @if ($mailTemplate->status)
                                    <span class="badge bg-success">{{ admin_lang('Active') }}</span>
                                @else
                                    <span class="badge bg-danger">{{ admin_lang('Disabled') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                        aria-expanded="true">
                                        <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.settings.mailtemplates.edit', $mailTemplate->id) }}"><i
                                                    class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @push('styles_libs')
        <link rel="stylesheet" href="{{ asset('assets/vendor/libs/bootstrap-colorpicker/bootstrap-colorpicker.min.css') }}">
    @endpush
    @push('scripts_libs')
        <script src="{{ asset('assets/vendor/libs/autosize/autosize.min.js') }}"></script>
        <script src="{{ asset('assets/vendor/libs/bootstrap-colorpicker/bootstrap-colorpicker.min.js') }}"></script>
    @endpush
    @push('scripts')
        <script>
            $(function() {
                autosize($('textarea'));
                $('.billiongroup-color-picker').colorpicker();
            });
        </script>
    @endpush
@endsection
