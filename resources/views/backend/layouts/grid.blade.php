@extends('backend.layouts.panel')
@push('styles_libs')
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css') }}" />
@endpush
@section('page-actions')
    @hasSection('back')
        <a href="@yield('back')" class="btn"><i class="ti ti-arrow-left icon"></i>{{ admin_lang('Back') }}</a>
    @endif
    @if (request()->routeIs('admin.advertisements.index'))
        <a href="{{ route('admin.advertisements.edit', $headAd->id) }}" class="btn btn-azure">
            <i class="ti ti-code icon"></i>{{ admin_lang('Head Code') }}</a>
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
    @hasSection('add_modal')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="ti ti-plus icon"></i>@yield('add_modal')
        </button>
    @endif
    @if (request()->routeIs('admin.notifications.index'))
        <a class="vironeer-link-confirm btn btn-outline-success"
            href="{{ route('admin.notifications.readall') }}">{{ admin_lang('Make All as Read') }}</a>
        <form class="d-inline" action="{{ route('admin.notifications.deleteallread') }}" method="POST">
            @csrf
            @method('DELETE')
            <button class="vironeer-able-to-delete btn btn-outline-danger">{{ admin_lang('Delete All Read') }}</button>
        </form>
    @endif
@endsection
