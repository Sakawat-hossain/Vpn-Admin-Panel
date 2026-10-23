@extends('backend.layouts.panel')
@section('page-actions')
    @hasSection('back')
        <a href="@yield('back')" class="btn"><i class="ti ti-arrow-left icon"></i>{{ admin_lang('Back') }}</a>
    @endif
    @hasSection('language')
        @include('backend.includes.language-switch')
    @endif
    @hasSection('modal')
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#viewModal">
            @yield('modal')
        </button>
    @endif
    @hasSection('link')
        <a href="@yield('link')" class="btn btn-primary"><i class="ti ti-plus icon"></i>{{ admin_lang('Add New') }}</a>
    @endif
    <button form="billiongroup-submited-form" class="btn btn-primary @yield('btn_action')" @yield('btn_action')>
        <i class="ti ti-device-floppy icon"></i>{{ admin_lang('Save') }}
    </button>
@endsection
