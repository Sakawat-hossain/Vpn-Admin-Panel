<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Jobs\RevokeWireGuardPeer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $unviewedSubscriptions = Subscription::where('is_viewed', 0)->get();
        if ($unviewedSubscriptions->count() > 0) {
            foreach ($unviewedSubscriptions as $unviewedSubscription) {
                $unviewedSubscription->is_viewed = true;
                $unviewedSubscription->save();
            }
        }
        $users = User::where('status', 1)->with('subscription')->get();
        $plans = Plan::all();
        $activeSubscriptions = Subscription::active()->with(['user', 'plan'])->get();
        $expiredSubscriptions = Subscription::expired()->with(['user', 'plan'])->get();
        $canceledSubscriptions = Subscription::cancelled()->with(['user', 'plan'])->get();
        return view('backend.subscriptions.index', [
            'users' => $users,
            'plans' => $plans,
            'activeSubscriptions' => $activeSubscriptions,
            'expiredSubscriptions' => $expiredSubscriptions,
            'canceledSubscriptions' => $canceledSubscriptions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return abort(404);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => ['required', 'integer'],
            'plan' => ['required', 'integer'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $user = User::where('id', $request->user)->with('subscription.plan')->firstOrFail();
        // Every account starts on the free plan, so only a paid, active
        // subscription blocks assigning a new one; a free one is replaced.
        if ($user->hasPremiumAccess()) {
            toastr()->error(admin_lang('User already subscribed'));
            return back();
        }
        $plan = Plan::find($request->plan);
        if (is_null($plan)) {
            toastr()->error(admin_lang('Plan not exists'));
            return back();
        }
        $subscription = $user->subscription ?: new Subscription(['user_id' => $user->id]);
        $subscription->fill([
            'plan_id' => $plan->id,
            'expiry_at' => $plan->periodEnd(Carbon::now()),
            'status' => Subscription::STATUS_ACTIVE,
            'about_to_expire_reminder' => false,
            'expired_reminder' => false,
            'is_viewed' => 1,
        ]);
        $createSubscription = $subscription->save();
        if ($createSubscription) {
            toastr()->success(admin_lang('Added successfully'));
            return back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function show(Subscription $subscription)
    {
        return abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function edit(Subscription $subscription)
    {
        $plans = Plan::all();
        return view('backend.subscriptions.edit', ['subscription' => $subscription, 'plans' => $plans]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Subscription $subscription)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean'],
            'plan' => ['required', 'integer'],
            'expiry_at' => ['required', 'date'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                toastr()->error($error);
            }
            return back();
        }
        $plan = Plan::findOrFail($request->plan);
        $expiry_at = Carbon::parse($request->expiry_at);
        $updateSubscription = $subscription->update([
            'plan_id' => $plan->id,
            'expiry_at' => $expiry_at,
            'status' => $request->status,
        ]);
        if ($updateSubscription) {
            $this->revokePremiumServerAccessIfNeeded($subscription->fresh(['user.servers', 'plan']));
            toastr()->success(admin_lang('Updated successfully'));
            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function destroy(Subscription $subscription)
    {
        $user = $subscription->user()->with('servers')->first();
        $subscription->delete();
        if ($user && $user->servers && $user->servers->isPremium()) {
            RevokeWireGuardPeer::forUser($user);
        }
        toastr()->success(admin_lang('Deleted successfully'));
        return back();
    }

    /**
     * WireGuard configs keep working until the peer is removed, so cut premium
     * server access right away when an admin downgrades or cancels.
     */
    private function revokePremiumServerAccessIfNeeded(Subscription $subscription): void
    {
        $user = $subscription->user;
        if ($user && $user->servers && $user->servers->isPremium() && !$user->hasPremiumAccess()) {
            RevokeWireGuardPeer::forUser($user);
        }
    }
}
