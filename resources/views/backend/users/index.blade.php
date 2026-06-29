@extends('backend.layouts.grid')
@section('title', admin_lang('Users'))
@section('container', 'container-xxl flex-grow-1 container-p-y')
<!-- @section('link', route('admin.users.create')) -->
@section('content')
<div class="row">
  <div class="col-lg-6 col-sm-6 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-left align-items-center">
        <div class="card-icon me-2">
          <span class="badge bg-label-primary rounded-pill p-2">
            <i class="ti ti-users ti-lg"></i>
          </span>
        </div>
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $activeUsersCount }}</h5>
          <small>{{ admin_lang('Active Users') }}</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6 col-sm-6 mb-4">
    <div class="card h-100">
      <div class="card-body d-flex justify-content-left align-items-center">
        <div class="card-icon me-2">
          <span class="badge bg-label-primary rounded-pill p-2">
            <i class="ti ti-users ti-lg"></i>
          </span>
        </div>
        <div class="card-title mb-0">
          <h5 class="mb-0 me-2">{{ $bannedUserscount }}</h5>
          <small>{{ admin_lang('Banned Users') }}</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-3">Search Filter</h5>
    <div class="d-flex justify-content-between align-items-center row pb-2 gap-3 gap-md-0">
      <div class="col-md-4 user_plan"></div>
      <!-- <div class="col-md-4 user_email_status"></div>
        <div class="col-md-4 user_account_status"></div> -->
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="dtable-ajax table border-top">
      <thead>
        <tr>
          <th>{{ admin_lang('Id') }}</th>
          <th>{{ admin_lang('User details') }}</th>
          <th>{{ admin_lang('Subscription') }}</th>
          <th>{{ admin_lang('Plan') }}</th>
          <th>{{ admin_lang('Email status') }}</th>
          <th>{{ admin_lang('Account status') }}</th>
          <th>{{ admin_lang('Registered date') }}</th>
          <th>Action</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@push('scripts')
  <script>
    const dt_ajax_table = $(".dtable-ajax");
    if (dt_ajax_table.length) {
    var dt_ajax = dt_ajax_table.dataTable({
      columns: [
      { data: 'id' },
      { data: 'name' },
      { data: 'subscription' },
      { data: 'plan' },
      { data: 'email_status' },
      { data: 'account_status' },
      { data: 'email_verified_at' }
      ],
      columnDefs: [
      {
        // For Responsive
        className: '',
        searchable: false,
        orderable: false,
        responsivePriority: 2,
        targets: 0,
        render: function (data, type, full, meta) {
        return data;
        }
      },
      {
        // User full name and email
        targets: 1,
        responsivePriority: 4,
        render: function (data, type, full, meta) {
        var $name = full['name'],
          $email = full['email'],
          $image = full['avatar'];
        if ($image) {
          // For Avatar image
          var $output =
          '<img src="{{ url('') }}/' + $image + '" alt="Avatar" class="rounded-circle">';
        } else {
          // For Avatar badge
          var stateNum = Math.floor(Math.random() * 6);
          var states = ['success', 'danger', 'warning', 'info', 'primary', 'secondary'];
          var $state = states[stateNum],
          $name = full['name'],
          $initials = $name.match(/\b\w/g) || [];
          $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
          $output = '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';
        }

        // Creates full output for row
        var $row_output =
          '<div class="d-flex justify-content-start align-items-center user-name">' +
          '<div class="avatar-wrapper">' +
          '<div class="avatar me-3">' +
          $output +
          '</div>' +
          '</div>' +
          '<div class="d-flex flex-column">' +
          '<a href="' +
          full['link_detail'] +
          '" class="text-body text-truncate"><span class="fw-medium">' +
          $name +
          '</span></a>' +
          '<small class="text-muted">' +
          $email +
          '</small>' +
          '</div>' +
          '</div>';
        return $row_output;
        }
      },
      {
        // Subscription
        targets: 2,
        searchable: false,
        orderable: false,
        responsivePriority: 4,
        render: function (data, type, full, meta) {
        let $bg = "";
        if (data == "Canceled") {
          $bg = "bg-lg-7";
        } else if (data == "Expired") {
          $bg = "bg-danger";
        } else if (data == "Subscribed") {
          $bg = "bg-lg-1";
        } else if (data == "Unsubscribed") {
          $bg = "bg-secondary";
        }
        let dataLabel = "{{ admin_lang('"+ data +"') }}";
        $output = `<span class="badge ${$bg}">${full['subs_label']}</span>`;
        return $output;
        }
      },
      {
        // For Responsive
        className: '',
        searchable: false,
        orderable: false,
        responsivePriority: 2,
        targets: 3,
        render: function (data, type, full, meta) {
        if (full['plan_id'] == "") return '';
        const ret = `<a href="${full['plan_id']}"><i
                  class="ti ti-diamond me-2"></i>
                  ${full['plan']}
                  </a>`;
        return ret;
        }
      },
      {
        // For Responsive
        className: '',
        searchable: false,
        orderable: false,
        responsivePriority: 2,
        targets: 4,
        render: function (data, type, full, meta) {
        return data;
        }
      },
      {
        // actions
        targets: 7,
        responsivePriority: 4,
        render: function (data, type, full, meta) {
        // if subscribed
        let li_subs = '';
        if (full['subscription'] == "Subscribed") {
          li_subs = `<li>
            <a class="dropdown-item"
              href="${full['link_subs']}"><i
              class="ti ti-diamond me-2"></i>{{ admin_lang('Subscription') }}</a>
            </li>`;
        }
        // Creates full output for row
        let $row_output = `<div class="text-end">
            <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
            aria-expanded="true">
            <i class="ti ti-dots-vertical ti-sm text-muted"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-sm-end"
            data-popper-placement="bottom-end">
            <li>
              <a class="dropdown-item"
              href="${full['link_detail']}"><i
                class="ti ti-edit me-2"></i>{{ admin_lang('Edit Details') }}</a>
            </li>
            ${li_subs}
            <li>
              <hr class="dropdown-divider" />
            </li>
            <li>
              <form action="${full['link_destroy']}"
              method="POST">
              @csrf @method('DELETE')
              <button
                class="billiongroup-able-to-delete dropdown-item text-danger"><i
                class="ti ti-trash me-2"></i>{{ admin_lang('Delete') }}</button>
              </form>
            </li>
            </ul>
          </div>`
        return $row_output;
        }
      },
      ],
      processing: true,
      serverSide: true,
      ajax: {
      url: '/admin/users/ajax',
      type: 'POST',
      data: function (d) {
        d._token = $("input[name=_token]").val();
      }
      },
      order: [
      [6, 'desc']
      ],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      "fnDrawCallback": function (oSettings) {
      let ableToDeleteBtn = $('.billiongroup-able-to-delete');
      ableToDeleteBtn.on('click', function (e) {
        e.preventDefault();
        Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'question',
        showCancelButton: true,
        allowOutsideClick: false,
        confirmButtonColor: PRIMARY_COLOR,
        confirmButtonText: 'Yes, delete it!',
        }).then((result) => {
        if (result.isConfirmed) {
          $(this).parents('form')[0].submit();
        }
        })
      });
      }
    });

    // Adding role filter once table initialized
    dt_ajax_table.api()
      .columns(3)
      .every(function () {
      var column = this;
      var select = $(
        '<select id="UserPlan" class="form-select text-capitalize"><option value="">-- Select Plan --</option></select>'
      )
        .appendTo('.user_plan')
        .on('change', function () {
        var val = $.fn.dataTable.util.escapeRegex($(this).val());
        column.search(val ? val : '', true, false).draw();
        });

      @foreach ($plans as $plan)
      select.append('<option value="{{ $plan->id }}">{{ $plan->name }}</option>');
    @endforeach
      });
    }
  </script>
@endpush
@endsection