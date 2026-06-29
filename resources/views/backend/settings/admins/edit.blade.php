@extends('backend.layouts.form')
@section('title', 'Admin | ' . $admin->firstname . ' ' . $admin->lastname)
@section('section', admin_lang('Settings'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('back', route('admin.settings.admins.index'))
@section('content')
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ admin_lang('User details') }}</h5>
    </div>
    <div class="card-body">
      <form id="billiongroup-submited-form" action="{{ route('admin.settings.admins.update', $admin->id) }}" method="POST"
        enctype="multipart/form-data">
      @csrf
      @method('PUT')
      @csrf
        <div class="mb-3">
          <div class="d-flex justify-content-center align-items-center flex-column">
            <img id="filePreview" src="{{ asset($admin->avatar) }}" width="140" height="140">
            <button id="selectFileBtn" type="button"
              class="btn btn-secondary d-flex btn-lg m-auto">{{ admin_lang('Choose Image') }}</button>
            <input id="selectedFileInput" type="file" name="avatar" accept="image/png, image/jpg, image/jpeg"
              hidden>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="mb-3">
              <label class="form-label">{{ admin_lang('First Name') }} : <span class="text-danger">*</span></label>
              <input type="firstname" class="form-control" name="firstname" value="{{ $admin->firstname }}"
                required>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="mb-3">
              <label class="form-label">{{ admin_lang('Last Name') }} : <span class="text-danger">*</span></label>
              <input type="lastname" class="form-control" name="lastname" value="{{ $admin->lastname }}"
                required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ admin_lang('Email Address') }} : <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" value="{{ $admin->email }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">{{ admin_lang('New Password') }} : </label>
            <input type="text" class="form-control" name="password">
            <small
              class="text-muted">{{ admin_lang("Leave it empty if you don't want to change password") }}</small>
          </div>
        </div>
      </form>
    </div>
  </div>
@endsection
