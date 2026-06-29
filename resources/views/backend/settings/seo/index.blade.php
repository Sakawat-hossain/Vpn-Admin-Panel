@extends('backend.layouts.grid')
@section('title', admin_lang('SEO Configurations'))
@section('section', admin_lang('Settings'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('link', route('admin.settings.seo.create'))
@section('content')
    <div class="custom-card card">
        <div class="card-datatable table-responsive">
            <table id="datatable" class="dtable table w-100">
                <thead>
                    <tr>
                        <th class="tb-w-3x">{{ admin_lang('#') }}</th>
                        <th class="tb-w-3x">{{ admin_lang('Language') }}</th>
                        <th class="tb-w-20x">{{ admin_lang('Site title') }}</th>
                        <th class="tb-w-7x">{{ admin_lang('Created date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($configurations as $configuration)
                        <tr class="item">
                            <td>{{ $configuration->id }}</td>
                            <td><a href="{{ route('admin.settings.languages.translates', $configuration->lang) }}"><i
                                        class="ti ti-language me-2"></i>{{ $configuration->language->name }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.settings.seo.edit', $configuration->id) }}"
                                    class="text-dark">{{ shortertext($configuration->title, 60) }}</a>
                            </td>
                            <td>{{ dateFormat($configuration->created_at) }}</td>
                            <td>
                                <div class="text-end">
                                    <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                        aria-expanded="true">
                                        <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('admin.settings.seo.edit', $configuration->id) }}"><i
                                                    class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <form action="{{ route('admin.settings.seo.destroy', $configuration->id) }}"
                                                method="POST">
                                                @csrf @method('DELETE')
                                                <button class="billiongroup-able-to-delete dropdown-item text-danger"><i
                                                        class="ti ti-trash me-2"></i>{{ admin_lang('Delete') }}</button>
                                            </form>
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
@endsection
