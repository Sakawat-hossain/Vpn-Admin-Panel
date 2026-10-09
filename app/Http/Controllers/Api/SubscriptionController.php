<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IapPurchase;
use App\Models\Plan;
use App\Models\Subscription as Subs;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Imdhemy\Purchases\Facades\Subscription;
use Validator;

/**
 * In-app purchase validation.
 *
 * Rules enforced for both stores:
 *  - the purchased product must belong to the plan being unlocked;
 *  - the subscription must not be expired (or, on Google, still pending);
 *  - one purchase (App Store original_transaction_id / Google order) can only
 *    ever be linked to one account, so a receipt can't be shared.
 */
class SubscriptionController extends Controller
{
    /**
     * List the available plans.
     *
     * @return JsonResponse
     */
    public function plans()
    {
        $plans = Plan::orderBy('is_free', 'desc')->orderBy('price')->get();
        return response200($plans, __('Successfully retrieved plans'));
    }

    /**
     * Validate and update subscription
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateAndUpdateSubscription(Request $request)
    {
        $platform = $request->input('platform');

        if (!in_array($platform, ['itunes', 'googleplay'], true)) {
            return response()->json(['error' => 'Invalid platform specified'], 400);
        }

        $validator = Validator::make($request->all(), [
            'receipt_data' => ['required', 'string'],
            'plan' => ['required', 'integer', 'exists:plans,id'],
            'payment_gateway_id' => ['required', 'integer', 'exists:payment_gateways,id'],
            'product_id' => ['nullable', 'string', 'max:150'],
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::findOrFail($request->input('plan'));
        if ($plan->isFree() || empty($plan->productIds())) {
            return responseError(422, __('This plan cannot be purchased in the app'));
        }

        return $platform === 'itunes'
            ? $this->validateAndUpdateITunesSubscription($request, $plan)
            : $this->validateAndUpdateGooglePlaySubscription($request, $plan);
    }

    /**
     * Validate and update iTunes subscription
     *
     * @return JsonResponse
     */
    private function validateAndUpdateITunesSubscription(Request $request, Plan $plan)
    {
        try {
            $receiptResponse = Subscription::appStore()->receiptData($request->input('receipt_data'))->verifyReceipt();
            $receiptStatus = $receiptResponse->getStatus();
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => __('Could not verify the receipt with the App Store')], 502);
        }

        if (!$receiptStatus->isValid()) {
            return response()->json([
                'valid' => false,
                'result_code' => $receiptResponse->getStatus()
            ]);
        }

        // Reject receipts issued to another app.
        $bundleId = config('liap.appstore_bundle_id');
        $receiptBundleId = optional($receiptResponse->getReceipt())->getBundleId();
        if ($bundleId && $receiptBundleId !== $bundleId) {
            return responseError(422, __('This receipt does not belong to this app'));
        }

        // Latest transaction of a product that belongs to this plan.
        $productIds = $plan->productIds();
        $receiptInfo = collect($receiptResponse->getLatestReceiptInfo() ?? [])
            ->filter(fn ($info) => in_array($info->getProductId(), $productIds, true) && $info->getExpiresDate())
            ->sortByDesc(fn ($info) => $info->getExpiresDate()->toCarbon()->getTimestamp())
            ->first();
        if (!$receiptInfo) {
            return responseError(422, __('The receipt does not contain a purchase of this plan'));
        }

        $expiresAt = $receiptInfo->getExpiresDate()->toCarbon();
        if ($receiptInfo->getCancellationDate() || $expiresAt->isPast()) {
            return responseError(422, __('This subscription has expired or was refunded'));
        }

        return $this->grant(
            $request->user('api'),
            $plan,
            IapPurchase::PLATFORM_ITUNES,
            $receiptInfo->getOriginalTransactionId(),
            $receiptInfo->getProductId(),
            $expiresAt,
            (bool) $receiptInfo->getIsTrialPeriod(),
            (int) $request->payment_gateway_id,
            $receiptInfo
        );
    }

    /**
     * Validate Google Play subscription
     *
     * @return JsonResponse
     */
    private function validateAndUpdateGooglePlaySubscription(Request $request, Plan $plan)
    {
        $purchaseToken = $request->input('receipt_data');
        $productIds = $plan->productIds();
        $productId = in_array($request->input('product_id'), $productIds, true) ? $request->input('product_id') : $productIds[0];

        try {
            $subscriptionReceipt = Subscription::googlePlay()->id($productId)->token($purchaseToken)->get();
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => __('Could not verify the purchase with Google Play')], 502);
        }

        $expiry = $subscriptionReceipt->getExpiryTime();
        $expiresAt = $expiry ? $expiry->toCarbon() : null;
        if (!$expiresAt || $expiresAt->isPast()) {
            return responseError(422, __('This subscription has expired'));
        }
        // 0 = payment pending, 1 = received, 2 = free trial, 3 = deferred
        $paymentState = $subscriptionReceipt->getPaymentState();
        if ($paymentState === 0 || $paymentState === null) {
            return responseError(422, __('The payment for this subscription is still pending'));
        }

        // Google acknowledges nothing by itself: unacknowledged purchases are refunded after 3 days.
        if ($subscriptionReceipt->getAcknowledgementState() === 0) {
            try {
                Subscription::googlePlay()->id($productId)->token($purchaseToken)->acknowledge();
            } catch (\Exception $e) {
                report($e);
            }
        }

        // Renewals get order ids like GPA.1234-...-56789..0, ..1, ...; the base id identifies the purchase.
        $orderId = (string) $subscriptionReceipt->getOrderId();
        $purchaseId = $orderId !== '' ? preg_replace('/\.\.\d+$/', '', $orderId) : 'token:' . hash('sha256', $purchaseToken);

        return $this->grant(
            $request->user('api'),
            $plan,
            IapPurchase::PLATFORM_GOOGLE_PLAY,
            $purchaseId,
            $productId,
            $expiresAt,
            $paymentState === 2,
            (int) $request->payment_gateway_id,
            $subscriptionReceipt,
            $orderId ?: $purchaseId
        );
    }

    /**
     * Link the purchase to the user (refusing purchases already linked to another
     * account), extend the subscription and record a transaction for each new
     * billing period.
     */
    private function grant(User $user, Plan $plan, string $platform, string $purchaseId, string $productId, Carbon $expiresAt, bool $isTrial, int $paymentGatewayId, $receipt, ?string $paymentId = null)
    {
        return DB::transaction(function () use ($user, $plan, $platform, $purchaseId, $productId, $expiresAt, $isTrial, $paymentGatewayId, $receipt, $paymentId) {
            $purchase = IapPurchase::where('platform', $platform)
                ->where('original_transaction_id', $purchaseId)
                ->lockForUpdate()
                ->first();

            if ($purchase && (int) $purchase->user_id !== (int) $user->id) {
                return responseError(409, __('This purchase is already linked to another account'));
            }

            // Store expiry times carry milliseconds, the database only seconds.
            $isNewPeriod = !$purchase || !$purchase->expires_at
                || $expiresAt->getTimestamp() > $purchase->expires_at->getTimestamp();

            $purchase = $purchase ?: new IapPurchase([
                'platform' => $platform,
                'original_transaction_id' => $purchaseId,
                'user_id' => $user->id,
            ]);
            $purchase->fill([
                'plan_id' => $plan->id,
                'product_id' => $productId,
                'expires_at' => $expiresAt,
            ])->save();

            $subscription = Subs::firstOrNew(['user_id' => $user->id]);
            $subscription->fill([
                'plan_id' => $plan->id,
                'expiry_at' => $expiresAt,
                'status' => Subs::STATUS_ACTIVE,
                'about_to_expire_reminder' => false,
                'expired_reminder' => false,
            ]);
            $subscription->is_viewed = $subscription->is_viewed ?? 0;
            $subscription->save();

            if ($isNewPeriod) {
                $price = $isTrial ? 0 : $plan->price;
                $trx = new Transaction();
                $trx->checkout_id = date("YmdHis") . "-" . $user->id . "-" . $plan->id . "-" . substr(sha1($purchaseId . $expiresAt->timestamp), 0, 8);
                $trx->user_id = $user->id;
                $trx->plan_id = $plan->id;
                $trx->price = $price;
                $trx->total = $price;
                $trx->details_before_discount = (object) [
                    "price" => $price,
                    "tax" => "0.00",
                    "total" => $price,
                ];
                $trx->payment_gateway_id = $paymentGatewayId;
                $trx->payment_id = $paymentId ?? $purchaseId;
                $trx->payer_email = $user->email;
                $trx->type = 1;
                $trx->status = Transaction::STATUS_PAID;
                $trx->is_viewed = 0;
                $trx->save();
            }

            return response()->json(['receipt' => $receipt, 'message' => __('Successfully update subscription data')]);
        });
    }
}
