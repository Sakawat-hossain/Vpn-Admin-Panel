<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\User;
use App\Models\UserLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Validator;

class UserController extends Controller
{
    public function index(Request $request, $search = null)
    {
        $unviewedUsers = User::where('is_viewed', 0)->get();
        if ($unviewedUsers->count() > 0) {
            foreach ($unviewedUsers as $unviewedUser) {
                $unviewedUser->is_viewed = 1;
                $unviewedUser->save();
            }
        }
        $activeUsersCount = User::where('status', 1)->get()->count();
        $bannedUserscount = User::where('status', 0)->get()->count();

        $plans = Plan::orderBy('name', 'asc')->get();

        return view('backend.users.index', [
            'plans' => $plans,
            'activeUsersCount' => $activeUsersCount,
            'bannedUserscount' => $bannedUserscount,
        ]);
    }

    public function ajaxData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // total number of rows per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        $searchValue = $search_arr['value']; // Search value

        // param plan
        $planparam = $columnName_arr[3]['search']['value'];

        // Total records
        $totalRecords = User::select('count(*) as allcount')
            ->orWhere(function ($query) use ($searchValue) {
                $query->where('users.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.firstname', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.lastname', 'like', '%' . $searchValue . '%');
            })
            ->leftJoin('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id');

        if ($planparam != "") {
            $totalRecords->where('plans.id', '=', $planparam);
        }
        $totalRecords = $totalRecords->count();

        // total with filter
        $totalRecordswithFilter = User::select('count(*) as allcount')
            ->orWhere(function ($query) use ($searchValue) {
                $query->where('users.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.firstname', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.lastname', 'like', '%' . $searchValue . '%');
            })
            ->leftJoin('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id');

        if ($planparam != "") {
            $totalRecordswithFilter->where('plans.id', '=', $planparam);
        }
        $totalRecordswithFilter = $totalRecordswithFilter->count();

        // Get records, also we have included search filter as well
        $q = User::orderBy($columnName, $columnSortOrder)
            ->orWhere(function ($query) use ($searchValue) {
                $query->where('users.name', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.firstname', 'like', '%' . $searchValue . '%')
                    ->orWhere('users.lastname', 'like', '%' . $searchValue . '%');
            })
            ->leftJoin('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id');

        if ($planparam != "") {
            $q->where('plans.id', '=', $planparam);
        }

        $records = $q->select('users.*', 'subscriptions.id as subscription_id', 'plans.id as plan_id', 'plans.name as plan')
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = array();

        foreach ($records as $record) {
            // subscription
            $subscription = "";
            if ($record->isSubscribed()) {
                if ($record->subscription->isCancelled()) {
                    $subscription = 'Canceled';
                } elseif ($record->subscription->isExpired()) {
                    $subscription = 'Expired';
                } else {
                    $subscription = 'Subscribed';
                }
            } else {
                $subscription = 'Unsubscribed';
            }

            // email status
            if ($record->email_verified_at) {
                $email_status = '<span class="badge bg-girl">' . admin_lang('Verified') . '</span>';
            } else {
                $email_status = '<span class="badge bg-secondary">' . admin_lang('Unverified') . '</span>';
            }

            // account status
            if ($record->status) {
                $account_status = '<span class="badge bg-success">' . admin_lang('Active') . '</span>';
            } else {
                $account_status = '<span class="badge bg-danger">' . admin_lang('Banned') . '</span>';
            }
            $data_arr[] = array(
                "id" => $record->id,
                "name" => $record->name,
                "subscription" => $subscription,
                "plan_id" => $record->plan_id ? route('admin.plans.edit', $record->plan_id) : '',
                "plan" => $record->plan,
                "email" => $record->email,
                "avatar" => $record->avatar,
                "email_verified_at" => $record->email_verified_at,
                "email_status" => $email_status,
                "account_status" => $account_status,
                "subs_label" => admin_lang($subscription),
                "link_detail" => route('admin.users.edit', $record->id),
                "link_subs" => route('admin.subscriptions.edit', $record->subscription_id),
                "link_destroy" => route('admin.users.destroy', $record->id)
            );
        }

        $response = array(
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecordswithFilter,
            "data" => $data_arr,
        );

        echo json_encode($response);
    }

    public function create()
    {
        $password = Str::random(16);
        return view('backend.users.create', ['password' => $password]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'string', 'max:100', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back()->withInput();
        }
        if ($request->has('avatar')) {
            $avatar = imageUpload($request->file('avatar'), 'images/avatars/users/', '110x110');
        } else {
            $avatar = "images/avatars/default.png";
        }

        // get random free server
        $server = Server::inRandomOrder()->where('status', 1)->where('is_premium', 0)->first();

        $user = User::create([
            'name' => $request->firstname . ' ' . $request->lastname,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'avatar' => $avatar,
            'password' => Hash::make($request->password),
            'api_token' => hash('sha256', Str::random(60)),
            'server_id' => $server->id ?? null,
            'dns' => '1.1.1.1'
        ]);
        if ($user) {
            if (settings('actions')->email_verification_status) {
                $user->forceFill(['email_verified_at' => Carbon::now()])->save();
            }

            // auto subs ke free plan
            $plan = Plan::find(13);// id plan harus 13
            if (is_null($plan)) {
                return response422(['plan' => [__(admin_lang('Plan not exists'))]]);
            }
            if ($plan->interval == 1) {
                $expiry_at = Carbon::now()->addMonth();
            } else {
                $expiry_at = Carbon::now()->addYear();
            }
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'expiry_at' => $expiry_at,
                'is_viewed' => 0,
            ]);

            toastr()->success(admin_lang('Created Successfully'));
            return redirect()->route('admin.users.edit', $user->id);
        }
    }

    public function show(User $user)
    {
        return abort(404);
    }

    public function edit(User $user)
    {
        return view('backend.users.edit.index', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'firstname' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'string', 'max:100', 'unique:users,email,' . $user->id],
            'address_1' => ['nullable', 'max:255'],
            'address_2' => ['nullable', 'max:255'],
            'city' => ['nullable', 'max:150'],
            'state' => ['nullable', 'max:150'],
            'zip' => ['nullable', 'max:100'],
            'country' => ['nullable', 'integer', 'exists:countries,id'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $country = Country::find($request->country);
        $status = ($request->has('status')) ? 1 : 0;
        $google2fa_status = ($request->has('google2fa_status')) ? 1 : 0;
        $address = [
            'address_1' => $request->address_1,
            'address_2' => $request->address_2,
            'city' => $request->city,
            'state' => $request->state,
            'zip' => $request->zip,
            'country' => $country->name ?? null,
        ];
        $update = $user->update([
            'name' => $request->firstname . ' ' . $request->lastname,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
            'address' => $address,
            'google2fa_status' => $google2fa_status,
            'status' => $status,
        ]);
        if ($update) {
            $emailValue = ($request->has('email_status')) ? Carbon::now() : null;
            $user->forceFill([
                'email_verified_at' => $emailValue,
            ])->save();
            toastr()->success(admin_lang('Updated Successfully'));
            return back();
        }

    }

    public function destroy(User $user)
    {
        deleteAdminNotification(route('admin.users.edit', $user->id));
        if ($user->avatar != "images/avatars/default.png") {
            removeFile($user->avatar);
        }
        $user->delete();
        toastr()->success(admin_lang('Deleted Successfully'));
        return back();
    }

    public function changeAvatar(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                return response()->json(['error' => $error]);
            }
        }
        if ($request->has('avatar')) {
            if ($user->avatar == 'images/avatars/default.png') {
                $avatar = imageUpload($request->file('avatar'), 'images/avatars/users/', '110x110');
            } else {
                $avatar = imageUpload($request->file('avatar'), 'images/avatars/users/', '110x110', null, $user->avatar);
            }
        } else {
            return response()->json(['error' => admin_lang('Upload error')]);
        }
        $update = $user->update([
            'avatar' => $avatar,
        ]);
        if ($update) {
            return response()->json(['success' => admin_lang('Updated Successfully')]);
        }
    }

    public function deleteAvatar(User $user)
    {
        $avatar = "images/avatars/default.png";
        if ($user->avatar != $avatar) {
            removeFile($user->avatar);
        } else {
            toastr()->error(admin_lang('Default avatar cannot be deleted'));
            return back();
        }
        $update = $user->update([
            'avatar' => $avatar,
        ]);
        if ($update) {
            toastr()->success(admin_lang('Removed Successfully'));
            return back();
        }
    }

    public function sendMail(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'subject' => ['required', 'string'],
            'reply_to' => ['required', 'email'],
            'message' => ['required', 'string'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        if (!settings('smtp')->status) {
            toastr()->error(admin_lang('SMTP is not enabled'));
            return back()->withInput();
        }
        try {
            $email = $user->email;
            $subject = $request->subject;
            $replyTo = $request->reply_to;
            $msg = $request->message;
            \Mail::send([], [], function ($message) use ($msg, $email, $subject, $replyTo) {
                $message->to($email)
                    ->replyTo($replyTo)
                    ->subject($subject)
                    ->html($msg);
            });
            toastr()->success(admin_lang('Sent successfully'));
            return back();
        } catch (\Exception $e) {
            toastr()->error(admin_lang('Sent error'));
            return back();
        }
    }

    public function logs(User $user)
    {
        $logs = UserLog::where('user_id', $user->id)->select('id', 'ip', 'location')->orderbyDesc('id')->paginate(6);
        return view('backend.users.edit.logs', ['user' => $user, 'logs' => $logs]);
    }

    public function getLogs(User $user, UserLog $userLog)
    {
        $userLog['ip_link'] = route('admin.users.logsbyip', $userLog->ip);
        return response()->json($userLog);
    }

    public function logsByIp($ip)
    {
        $logs = UserLog::where('ip', $ip)->with('user')->paginate(12);
        if ($logs->isEmpty()) {
            return abort(404);
        }
        return view('backend.users.logs', ['logs' => $logs]);
    }
}
