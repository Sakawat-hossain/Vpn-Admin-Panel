@extends('backend.layouts.form')
@section('title', admin_lang('Add New Admin'))
@section('section', admin_lang('Settings'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('back', route('admin.settings.admins.index'))
@section('content')
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ admin_lang('User details') }}</h5>
    </div>
    <div class="card-body">
      <form id="billiongroup-submited-form" action="{{ route('admin.settings.admins.store') }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <div class="d-flex justify-content-center align-items-center flex-column">
            <img id="filePreview" src="{{ asset('images/avatars/default.png') }}" width="140" height="140">
            <button id="selectFileBtn" type="button"
              class="btn btn-secondary d-flex btn-lg m-auto">{{ admin_lang('Choose Image') }}</button>
            <input id="selectedFileInput" type="file" name="avatar" accept="image/png, image/jpg, image/jpeg"
              hidden>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">{{ admin_lang('First Name') }} : <span class="text-danger">*</span></label>
            <input type="firstname" name="firstname" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">{{ admin_lang('Last Name') }} : <span class="text-danger">*</span></label>
            <input type="lastname" name="lastname" class="form-control" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">{{ admin_lang('E-mail Address') }} : <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">{{ admin_lang('Password') }} : <span class="text-danger">*</span></label>
          <input type="text" name="password" class="form-control" value="{{ $password }}"
              required>
        </div>
      </form>
    </div>
  </div>
@endsection
