@extends('backend.layouts.grid')
@section('title', admin_lang('Servers'))
@section('add_modal', admin_lang('Add New'))
@section('content')
    
    
    <div class="card custom-card">
    <div class="card custom-card custom-tabs mb-3">
        <div class="card-body">
            <ul class="nav nav-pills" role="tablist">
                <li role="presentation">
                    <button class="nav-link active me-2" id="free-tab" data-bs-toggle="tab" data-bs-target="#free"
                        type="button" role="tab" aria-controls="active" aria-selected="true"><i
                            class="fa fa-check me-2"></i>{{ admin_lang('Free') }}
                        ({{ $freeServers->count() }})
                    </button>
                </li>
                <li role="presentation">
                    <button class="nav-link me-2" id="premium-tab" data-bs-toggle="tab" data-bs-target="#premium"
                        type="button" role="tab" aria-controls="premium" aria-selected="false"><i
                            class="fa fa-money-bill me-2"></i>{{ admin_lang('Premium') }}
                        ({{ $premiumServers->count() }})
                    </button>
                </li>
            </ul>
            
        </div>
    </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="free" role="tabpanel" aria-labelledby="free-tab">
                <table class="dtable table w-100">
                    <thead>
                        <tr>
                            <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                            <th class="tb-w-20x">{{ admin_lang('Country') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('State') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Status') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('IP Address') }}</th>
                            <th class="tb-w-3x">{{ admin_lang('Recommend') }}</th>
                            <th class="tb-w-3x">{{ admin_lang('Status Deployment') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($freeServers as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->country }}</td>
                                <td>{{ $row->state }}</td>
                                <td>
                                    <button type="button" class="btn btn-{{ $row->status === 0 ? 'danger' : 'primary' }} rounded-3">
                                        {{ $row->printStatus()}}
                                    </button>
                                </td>
                                <td>{{ $row->ip_address }}</td>
                                <td>{{ $row->printRecommended() }}</td>
                                <td class="text-center">
                                    @if ($row->job_status != "")
                                    <button type="button" class="btn btn-{{ $row->job_status !== 'failed' ? 'primary' : 'danger' }} rounded-3" onclick="view_detail({{ $row->id }})">
                                        {{ $row->job_status}}
                                    </button>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                            aria-expanded="true">
                                            <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg"
                                            data-popper-placement="bottom-end">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.servers.show', $row->id) }}"><i
                                                        class="ti ti-eye me-2"></i>{{ admin_lang('Detail') }}</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.servers.edit', $row->id) }}"><i
                                                        class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider" />
                                            </li>
                                            <li>
                                                <form
                                                    action="{{ route('admin.servers.destroy', $row->id) }}"
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
            <div class="tab-pane fade" id="premium" role="tabpanel" aria-labelledby="premium-tab">
                <table class="dtable table w-100">
                    <thead>
                        <tr>
                            <th class="tb-w-2x">{{ admin_lang('#') }}</th>
                            <th class="tb-w-20x">{{ admin_lang('Country') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('State') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('Status') }}</th>
                            <th class="tb-w-7x">{{ admin_lang('IP Address') }}</th>
                            <th class="tb-w-3x">{{ admin_lang('Recommend') }}</th>
                            <th class="tb-w-3x">{{ admin_lang('Status Deployment') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($premiumServers as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->country }}</td>
                                <td>{{ $row->state }}</td>
                                <td>
                                    <button type="button" class="btn btn-{{ $row->status === 0 ? 'danger' : 'primary' }} rounded-3" onclick="view_detail({{ $row->id }})">
                                        {{ $row->printStatus()}}
                                    </button>
                                </td>
                                <td>{{ $row->ip_address }}</td>
                                <td>{{ $row->printRecommended() }}</td>
                                <td class="text-center">
                                    @if ($row->job_status != "")
                                        <button type="button" class="btn btn-{{ $row->job_status !== 'failed' ? 'primary' : 'danger' }} rounded-3" onclick="view_detail({{ $row->id }})">
                                            {{ $row->job_status}}
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                            aria-expanded="true">
                                            <i class="ti ti-dots-vertical fa-sm text-muted"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg"
                                            data-popper-placement="bottom-end">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.servers.show', $row->id) }}"><i
                                                        class="ti ti-eye me-2"></i>{{ admin_lang('Detail') }}</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ route('admin.servers.edit', $row->id) }}"><i
                                                        class="ti ti-edit me-2"></i>{{ admin_lang('Edit') }}</a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider" />
                                            </li>
                                            <li>
                                                <form
                                                    action="{{ route('admin.servers.destroy', $row->id) }}"
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
    </div>
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-3">
                    <h6 class="modal-title" id="addModalLabel">{{ admin_lang('New Server') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.servers.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">{{ admin_lang('Country') }} : <span class="text-danger">*</span></label>
                            <select name="country" id="country" class="form-select" required>
                                <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                                @foreach ($countries as $key => $country)
                                    <option value="{{ $country }}"
                                        {{ old('country') == $country ? 'selected' : '' }}>
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
                                <input type="text" name="latitude" class="form-control" required/>
                            </div>
                            <div class="col-xl-6 col-md-6 col-sm-6 mb-4">
                                <label class="form-label">{{ admin_lang('Longitude') }} : <span class="text-danger">*</span></label>
                                <input type="text" name="longitude" class="form-control" required/>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ admin_lang('Status') }} : <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                                @foreach ($statusOptions as $key => $row)
                                    <option value="{{ $key }}">
                                        {{ $row }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ admin_lang('IP Address') }} : <span class="text-danger">*</span></label>
                            <input type="text" name="ip_address" class="form-control" required/>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ admin_lang('Recommended') }} : <span class="text-danger">*</span></label>
                            <select name="recommended" id="recommended" class="form-select" required>
                                <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                                @foreach ($recommendOptions as $key => $row)
                                    <option value="{{ $key }}">
                                        {{ $row }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ admin_lang('Server Type') }} : <span class="text-danger">*</span></label>
                            <select name="is_premium" id="is_premium" class="form-select" required>
                                <option value="" selected disabled>{{ admin_lang('Choose') }}</option>
                                @foreach ($serverOptions as $key => $row)
                                    <option value="{{ $key }}">
                                        {{ $row }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="installWgEasy" name="installWgEasy">
                            <label class="form-check-label" for="installWgEasy">Install Wg Easy?</label>
                        </div>
                        <div class="mb-4 install">
                            <label class="form-label">{{ admin_lang('SSH Port') }} : <span class="text-danger">*</span></label>
                            <input type="text" name="ssh_port" id="ssh_port" class="form-control" value="22"/>
                        </div>
                        <div class="mb-4 install">
                            <label class="form-label">{{ admin_lang('VPS Username') }} : <span class="text-danger">*</span></label>
                            <input type="text" name="vps_username" id="vps_username" class="form-control"/>
                        </div>
                        <div class="mb-4 install">
                            <label class="form-label">{{ admin_lang('VPS Password') }} : <span class="text-danger">*</span></label>
                            <input type="text" name="vps_password" id="vps_password" class="form-control"/>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="isOVPN" name="isOVPN">
                            <label class="form-check-label" for="isOVPN">Is OVPN?</label>
                        </div>
                        <div class="mb-4 ovpn">
                            <label class="form-label">{{ admin_lang('OVPN Config') }} :</label>
                            <textarea name="ovpn_config" id="ovpn_config" class="form-control" rows="10"></textarea>
                        </div>
                        <button class="btn btn-primary">{{ admin_lang('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deployModal" tabindex="-1" aria-labelledby="deployModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header py-3">
                    <h6 class="modal-title" id="deployModalLabel">{{ admin_lang('Deployment Detail') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Action</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody id="deployData">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@push('scripts_libs')
<script>
    $(function() {
        // wg easy
        $("#installWgEasy").on('change', function () {
            if ($(this).is(':checked')) {
                $('.install').show()
                $("#ssh_port").attr("required", true)
                $("#vps_username").attr("required", true)
                $("#vps_pasword").attr("required", true)
            } else {
                $('.install').hide()
                $("#ssh_port").removeAttr("required")
                $("#vps_username").removeAttr("required")
                $("#vps_pasword").removeAttr("required")
            }
        })
        $("#installWgEasy").prop("checked", false)
        $('.install').hide()

        // ovpn config
        $("#isOVPN").on('change', function () {
            if ($(this).is(':checked')) {
                $('.ovpn').show()
                $("#ovpn_config").attr("required", true)
            } else {
                $('.ovpn').hide()
                $("#ovpn_config").removeAttr("required")
            }
        })
        $("#isOVPN").prop("checked", false)
        $('.ovpn').hide()
    })
    
    // view deploy
    function view_detail(server_id) {
        $("#deployModal").modal('show');
        $("#deployData").html("<tr><td colspan=\"2\">Loading data..</td></tr>");
        $.ajax({
            url: "{{ config('app.url') }}/admin/servers/"+ server_id + "/deploy",
            type: "GET",
            dataType: "json",
            success: function(data) {
                let html = '';
                if (!data.empty) {
                    let  no = 1;
                    data.data.forEach(element => {
                        html += '<tr>';
                        html += `<td class="text-center">${no}</td>`;
                        html += `<td>${element.action}</td>`;
                        html += `<td>${element.result}</td>`;
                        html += '</tr>';
                        no++;
                    });
                } else {
                    html = "<tr><td colspan=\"2\">Empty data</td></tr>";
                }
                $("#deployData").html(html);
            }
        });
    }
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

        // if ($("#state") instanceof jQuery && $("#state").data('select2')) {
        //   $("#state").select2("destroy");
        // }
        $('#state').html("");
        // mapping value ke select
        $.each(states, function(key, value) {
          $('#state')
            .append($('<option></option>')
            .attr('value', value.name)
            .text(value.name));
        });
        // $("#state").select2();

        // jika state tidak kosong
        // if (g_state != '') {
        //   $('#state').val(g_state).trigger('change')
        //   g_state = ''
        // }
      },
      error: function(jqXHR, textStatus, errorThrown) {
        // terjadi error
        console.log("Error: " + textStatus);
      }
    });
  })
</script>
@endpush
@endsection