@extends('backend.layouts.form')
@section('title', admin_lang('Edit Server for ' . $server->country))
@section('back', route('admin.servers.index'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
@section('content')
    <form id="billiongroup-submited-form" action="{{ route('admin.servers.update', $server->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card p-2">
            <div class="card-body">
                <div class="mb-4">
                    <label class="form-label">{{ admin_lang('Country') }} : <span class="text-danger">*</span></label>
                    <select name="country" id="country" class="form-select" required>
                        <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                        @foreach ($form->countries as $key => $country)
                            <option value="{{ $country }}">
                                {{ $country }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ admin_lang('State') }} : <span class="text-danger">*</span></label>
                    <select name="state" id="state" class="form-select" required>
                        <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                    </select>
                </div>
                <div class="d-flex">
                    <div class="col-xl-6 col-md-6 col-sm-6 mb-4">
                        <label class="form-label">{{ admin_lang('Latitude') }} : <span class="text-danger">*</span></label>
                        <input type="text" name="latitude" class="form-control" value="{{$server->latitude}}" required/>
                    </div>
                    <div class="col-xl-6 col-md-6 col-sm-6 mb-4">
                        <label class="form-label">{{ admin_lang('Longitude') }} : <span class="text-danger">*</span></label>
                        <input type="text" name="longitude" class="form-control" value="{{$server->longitude}}" required/>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ admin_lang('Status') }} : <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                        @foreach ($form->statusOptions as $key => $row)
                            <option value="{{ $key }}"
                            {{ $server->status == $key ? 'selected' : '' }}>
                                {{ $row }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ admin_lang('IP Address') }} : <span class="text-danger">*</span></label>
                    <input type="text" name="ip_address" class="form-control" value="{{$server->ip_address}}" required/>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ admin_lang('Recommended') }} : <span class="text-danger">*</span></label>
                    <select name="recommended" id="recommended" class="form-select" required>
                        <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                        @foreach ($form->recommendOptions as $key => $row)
                            <option value="{{ $key }}" 
                            {{ $server->recommended == $key ? 'selected' : '' }}>
                                {{ $row }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">{{ admin_lang('Server Type') }} : <span class="text-danger">*</span></label>
                    <select name="is_premium" id="is_premium" class="form-select" required>
                        <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                        @foreach ($form->serverOptions as $key => $row)
                            <option value="{{ $key }}"
                            {{ $server->is_premium == $key ? 'selected' : '' }}>
                                {{ $row }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @php
                    $vpnType = $server->isManagedOpenVpn() ? 'openvpn' : ($server->isOpenVpn() ? 'openvpn_profile' : 'wireguard');
                @endphp
                <div class="mb-4">
                    <label class="form-label" for="vpn_type">{{ admin_lang('Protocol') }} : <span class="text-danger">*</span></label>
                    <select name="vpn_type" id="vpn_type" class="form-select" required>
                        <option value="wireguard" {{ $vpnType === 'wireguard' ? 'selected' : '' }}>{{ admin_lang('WireGuard (wg-easy)') }}</option>
                        <option value="openvpn" {{ $vpnType === 'openvpn' ? 'selected' : '' }}>{{ admin_lang('OpenVPN, managed by the panel (users sign in with their account)') }}</option>
                        <option value="openvpn_profile" {{ $vpnType === 'openvpn_profile' ? 'selected' : '' }}>{{ admin_lang('OpenVPN, pasted profile (one shared profile)') }}</option>
                    </select>
                </div>
                <div class="mb-4 ovpn">
                    <label class="form-label">{{ admin_lang('OVPN Config') }} :</label>
                    <textarea name="ovpn_config" id="ovpn_config" class="form-control" rows="10">{{ $server->ovpn_config }}</textarea>
                </div>
            </div>
        </div>
    </form>
    @if (!$server->isOpenVpn() || $server->isManagedOpenVpn())
        <form action="{{ route('admin.servers.deploy', $server->id) }}" method="POST" class="mt-4">
            @csrf
            <div class="card p-2">
                <div class="card-body">
                    @if ($server->isManagedOpenVpn())
                        <h6 class="mb-1">{{ admin_lang('Install / redeploy OpenVPN') }}</h6>
                        <p class="text-muted small mb-3">
                            {{ admin_lang('Installs OpenVPN with the panel certificate and the account login check. Redeploying keeps users\' logins working; it only replaces the server certificate and settings.') }}
                        </p>
                    @else
                        <h6 class="mb-1">{{ admin_lang('Redeploy wg-easy') }}</h6>
                        <p class="text-muted small mb-3">
                            {{ admin_lang('Reinstalls wg-easy on this server with an API password and a firewall that only lets the panel reach the API. Existing peers are kept. Run this once on servers deployed before this update.') }}
                        </p>
                    @endif
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ admin_lang('SSH Port') }} : <span class="text-danger">*</span></label>
                            <input type="number" name="ssh_port" class="form-control" value="22" min="1" max="65535" required/>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ admin_lang('VPS Username') }} : <span class="text-danger">*</span></label>
                            <input type="text" name="vps_username" class="form-control" value="root" required/>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">{{ admin_lang('VPS Password') }} : <span class="text-danger">*</span></label>
                            <input type="password" name="vps_password" class="form-control" autocomplete="new-password" required/>
                        </div>
                    </div>
                    <button class="btn btn-secondary">{{ admin_lang('Redeploy') }}</button>
                </div>
            </div>
        </form>
    @endif
@push('scripts_libs')
<script>
  let g_country = '{{ $country = $server->country ?? old('country') }}'
  let g_state = '{{ $state = $server->state ?? old('state')}}'

  $(function() {
    // The pasted profile is only used by the "pasted profile" type.
    function toggleType() {
        const pasted = $("#vpn_type").val() === 'openvpn_profile'
        $('.ovpn').toggle(pasted)
        $("#ovpn_config").prop("required", pasted)
    }
    $("#vpn_type").on('change', toggleType)
    toggleType()
  })

  //get state
  $("#country").on('change', function() {
    if (this.value == '') return
    const param = this.value
    $.ajax({
      url: "{{ config('app.url') }}/assets/data/countries_v1.json",
      type: "GET",
      success: function(countries) {
        // Mencari indeks elemen dengan attribute "name"
        const index = countries.findIndex(country => country.name === param);
        
        // Mendapatkan array key dari objek dengan attribute "name" bernilai "Indo"
        // const keysArray = index !== -1 ? Object.keys(countries[index]) : [];

        const states = countries[index]['states'];

        $('#state').html("");
        // mapping value ke select
        $.each(states, function(key, value) {
          $('#state')
            .append($('<option></option>')
            .attr('value', value.name)
            .text(value.name));
        });
        
        // jika state tidak kosong
        if (g_state != '') {
          $('#state').val(g_state)
          g_state = ''
        }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        // terjadi error
        console.log("Error: " + textStatus);
      }
    });
  })

  //autoload
  const country = $("#country").val(g_country)
  if (country != '') {
    $("#country").trigger("change")
  }
</script>
@endpush
@endsection
