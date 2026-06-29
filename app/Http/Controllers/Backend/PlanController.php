<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\Plan;
use Illuminate\Http\Request;
use Validator;

class PlanController extends Controller
{
    public function index()
    {
        $weeklyPlans = Plan::where('interval', 3)->get();
        $monthlyPlans = Plan::where('interval', 1)->get();
        $halfYearlyPlans = Plan::where('interval', 4)->get();
        $yearlyPlans = Plan::where('interval', 2)->get();
        return view('backend.plans.index', [
            'weeklyPlans' => $weeklyPlans,
            'monthlyPlans' => $monthlyPlans,
            'halfYearlyPlans' => $halfYearlyPlans,
            'yearlyPlans' => $yearlyPlans,
        ]);
    }

    public function create()
    {
        return view('backend.plans.create');
    }

    public function store(Request $request)
    {
        if (!$request->has('is_free')) {
            $activePaymentMethod = PaymentGateway::where('status', 1)->hasCurrency()->get();
            if (count($activePaymentMethod) < 1) {
                toastr()->error(admin_lang('No active payment method'))->info(admin_lang('Add your payment methods info before you start creating a plan'));
                return back()->withInput();
            }
        }
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['required', 'string', 'max:150'],
            'interval' => ['required', 'integer', 'min:1', 'max:4'], // Adjusted max to 4
            'price' => ['sometimes', 'required', 'numeric', 'regex:/^\d*(\.\d{2})?$/'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back()->withInput();
        }

        if ($request->has('custom_features')) {
            foreach ($request->custom_features as $custom_feature) {
                if (empty($custom_feature['name'])) {
                    toastr()->error(admin_lang('Custom feature cannot be empty'));
                    return back()->withInput();
                }
            }
        }
        if ($request->has('is_free')) {
            $plan = Plan::free()->first();
            if ($plan) {
                toastr()->error(admin_lang('Free plan already exists'));
                return back()->withInput();
            }
            $request->login_require = ($request->has('login_require')) ? 1 : 0;
            $request->price = 0;
            $request->is_free = 1;
        } else {
            $request->is_free = 0;
            $request->login_require = 1;
        }
        $request->is_featured = ($request->has('is_featured')) ? 1 : 0;
        $request->expiration = ($request->has('no_expiration')) ? null : $request->expiration;
        $request->advertisements = ($request->has('advertisements')) ? 1 : 0;
        $plan = Plan::create([
            'name' => $request->name,
            'product_id' => $request->product_id,
            'interval' => $request->interval,
            'price' => $request->price,
            'advertisements' => $request->advertisements,
            'custom_features' => $request->custom_features,
            'login_require' => $request->login_require,
            'is_free' => $request->is_free,
            'is_featured' => $request->is_featured,
        ]);
        if ($plan) {
            if ($request->has('is_featured')) {
                Plan::where([['id', '!=', $plan->id], ['interval', $plan->interval]])->update(['is_featured' => 0]);
            }
            toastr()->success(admin_lang('Created Successfully'));
            return redirect()->route('admin.plans.index');
        }
    }

    public function show(Plan $plan)
    {
        return abort(404);
    }

    public function edit(Plan $plan)
    {
        return view('backend.plans.edit', ['plan' => $plan]);
    }

    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'product_id' => ['required', 'string', 'max:150'],
            'interval' => ['required', 'integer', 'min:1', 'max:4'], // Adjusted max to 4
            'price' => ['sometimes', 'required', 'numeric', 'regex:/^\d*(\.\d{2})?$/'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back()->withInput();
        }

        if ($request->has('custom_features')) {
            foreach ($request->custom_features as $custom_feature) {
                if (empty($custom_feature['name'])) {
                    toastr()->error(admin_lang('Custom feature cannot be empty'));
                    return back()->withInput();
                }
            }
        }
        if ($request->has('is_free')) {
            $freePlan = Plan::free()->first();
            if ($freePlan && $plan->id != $freePlan->id) {
                toastr()->error(admin_lang('Free plan already exists'));
                return back()->withInput();
            }
            $request->login_require = ($request->has('login_require')) ? 1 : 0;
            $request->price = 0;
            $request->is_free = 1;
        } else {
            $request->is_free = 0;
            $request->login_require = 1;
        }
        $request->is_featured = ($request->has('is_featured')) ? 1 : 0;
        $request->expiration = ($request->has('no_expiration')) ? null : $request->expiration;
        $request->advertisements = ($request->has('advertisements')) ? 1 : 0;

        $update = $plan->update([
            'name' => $request->name,
            'product_id' => $request->product_id,
            'interval' => $request->interval,
            'price' => $request->price,
            'advertisements' => $request->advertisements,
            'custom_features' => $request->custom_features,
            'login_require' => $request->login_require,
            'is_free' => $request->is_free,
            'is_featured' => $request->is_featured,
        ]);
        if ($update) {
            if ($request->has('is_featured')) {
                Plan::where([['id', '!=', $plan->id], ['interval', $plan->interval]])->update(['is_featured' => 0]);
            }
            toastr()->success(admin_lang('Updated Successfully'));
            return back();
        }
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions->count() > 0) {
            toastr()->error(admin_lang('Plan has subscriptions, you can delete them then delete the plan'));
            return back();
        }
        if ($plan->transactions->count() > 0) {
            toastr()->error(admin_lang('Plan has transactions, you can delete them then delete the plan'));
            return back();
        }
        $plan->delete();
        toastr()->success(admin_lang('Deleted successfully'));
        return back();
    }
}
