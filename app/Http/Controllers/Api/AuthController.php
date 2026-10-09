<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Jobs\RevokeWireGuardPeer;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\Server;
use App\Models\UserLog;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AuthController extends Controller
{
    /** Wrong verification-code guesses allowed per email before a cooldown. */
    private const MAX_CODE_ATTEMPTS = 5;

    /** Verification/reset emails allowed per email per window. */
    private const MAX_CODE_SENDS = 3;

    /** Failed logins allowed per email + IP per window. */
    private const MAX_LOGIN_ATTEMPTS = 10;

    private const LIMIT_WINDOW_SECONDS = 900;

    /**
     * @var User
     */
    private $usermodel;

    /**
     * constructor method
     *
     * @return void
     */
    public function __construct()
    {
        $this->usermodel = new User();
    }

    public function createRegisterNotify($user)
    {
        $title = $user->name . ' ' . admin_lang('has registered');
        $image = asset($user->avatar);
        $link = route('admin.users.edit', $user->id);
        return adminNotify($title, $image, $link);
    }

    public function createLog(Request $request)
    {
        $user = $request->user();
        $info = ipInfo();

        $newLoginLog = new UserLog();
        $newLoginLog->user_id = $user->id;
        $newLoginLog->ip = $info->ip;
        $newLoginLog->country = $info->location->country;
        $newLoginLog->country_code = $info->location->country_code;
        $newLoginLog->timezone = $info->location->timezone;
        $newLoginLog->location = $info->location->city . ', ' . $info->location->country;
        $newLoginLog->latitude = $info->location->latitude;
        $newLoginLog->longitude = $info->location->longitude;
        $newLoginLog->browser = $info->system->browser;
        $newLoginLog->os = Str::limit((string) $request->input('os'), 60, ''); // OS information from request body
        $newLoginLog->save();

        return response()->json(['message' => 'Log created successfully'], 201);
    }

    /**
     * process login
     *
     * @param LoginRequest $request
     * @return Response
     */
    /**
    *    @OA\Post(
    *       path="/auth/login",
    *       tags={"login"},
    *       operationId="login",
    *       summary="login",
    *       description="login",
    *       @OA\Response(
    *           response="200",
    *           description="Ok",
    *           @OA\JsonContent
    *           (example={"data":{"name":"cakra budiman","email":"user@email.com","token":null},"message":"Successfully entered the system"}),
    *      ),
    *  )
    */
    public function login(LoginRequest $request)
    {
        $limiterKey = 'api-login:' . Str::lower($request->email) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_LOGIN_ATTEMPTS)) {
            return responseError(429, __('Too many login attempts. Please try again in :minutes minutes.', [
                'minutes' => ceil(RateLimiter::availableIn($limiterKey) / 60),
            ]));
        }

        $user = $this->usermodel->where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            RateLimiter::clear($limiterKey);

            if ($user->isBanned()) {
                return responseError(403, __('Your account has been blocked'));
            }
            if (emailVerificationRequired() && is_null($user->email_verified_at)) {
                return responseError(403, __('Please verify your email address first'), ['verification_required' => true]);
            }

            return $this->handleLogin($user, __('Successfully entered the system'));
        }

        RateLimiter::hit($limiterKey, self::LIMIT_WINDOW_SECONDS);

        // If email or password is incorrect
        return response()->json([
            'info' => __('The email or password entered is incorrect')
        ], 422);
    }

    /**
     * process register
     *
     * @param RegisterRequest $request
     * @return Response
     */
    public function register(RegisterRequest $request)
    {
        $plan = freePlan();
        if (is_null($plan)) {
            return response422(['plan' => [__(admin_lang('Plan does not exist'))]]);
        }

        $verification_code = generateVerificationCode();
        // get random free server
        $server = Server::inRandomOrder()->where('status', 1)->where('is_premium', 0)->first();
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'firstname' => "",
            'lastname' => "",
            'avatar' => "images/avatars/default.png",
            'api_token' => hash('sha256', Str::random(60)),
            'verification_code' => $verification_code,
            'verification_code_sent_at' => now(),
            'server_id' => $server->id ?? null,
            'dns' => config('services.wg_easy.default_dns', '1.1.1.1'),
        ];

        DB::beginTransaction();
        try {
            $user = $this->usermodel->create($data);
            $this->createRegisterNotify($user);
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'expiry_at' => Carbon::now(),
                'is_viewed' => 0,
            ]);

            $this->sendCodeMail($user->email, 'Verify Account', __('Please input this code on apps to activate your account immediately.') . '<br/>' . __('Verification Code') . ': ' . $verification_code);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            report($e);
            return response500(null, __('Registration failed, please try again.'));
        }

        // The verification code / token are hidden on the model. The token is only
        // returned here when no email verification is required.
        if (!emailVerificationRequired()) {
            $user->makeVisible('api_token');
        }
        return response200($user, __('Successfully registered and code sent to ') . $request->email);
    }

    /**
     * process resend code
     *
     * @param ForgotPasswordRequest $request
     * @return Response
     */
    public function resendCode(ForgotPasswordRequest $request)
    {
        return $this->issueCode($request->email, 'Verify Account', __('Please input this code on your apps to activate your account immediately.'));
    }

    /**
     * process forgot password
     *
     * @param ForgotPasswordRequest $request
     * @return Response
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        return $this->issueCode($request->email, 'Forgot Password', __('This is your Verification Code'));
    }

    /**
     * process verify account
     *
     * @param Request $request
     * @return Response
     */
    public function verify(ForgotPasswordRequest $request)
    {
        $user = $this->usermodel->where('email', $request->email)->first();
        if ($error = $this->checkCode($user, $request->verification_code)) {
            return $error;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_token' => null,
            'verification_code' => null,
            'verification_code_sent_at' => null,
        ])->save();
        if (empty($user->api_token)) {
            $user->rotateApiToken();
        }

        return response200($user->fresh()->makeVisible('api_token'), __('Successfully verified!'));
    }

    /**
     * process reset password
     *
     * @param ResetPasswordRequest $request
     * @return Response
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $user = $this->usermodel->where('email', $request->email)->first();
        if ($error = $this->checkCode($user, $request->verification_code)) {
            return $error;
        }

        $user->forceFill([
            'password' => bcrypt($request->new_password),
            'email_token' => null,
            'verification_code' => null,
            'verification_code_sent_at' => null,
            // Proving ownership of the mailbox also verifies it.
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
        // Log out every device that used the old token.
        $user->rotateApiToken();

        return response200($user->fresh()->makeVisible('api_token'), __('Password updated successfully'));
    }

    /**
     * handleLogin
     *
     * @param User $user
     * @param string $message
     * @return JsonResponse
     */
    private function handleLogin(User $user, string $message)
    {
        $token = $user->api_token ?: $user->rotateApiToken();
        $userdata = [
            "name" => $user->name,
            "email" => $user->email,
            "token" => $token
        ];
        return response200($userdata, $message);
    }

    /**
     * get profile user
     *
     * @return JsonResponse
     */
    public function profile()
    {
        $user = auth('api')->user();
        $user->load(['subscription.plan', 'servers', 'logs']);
        return response200($user->makeVisible('api_token'), __('Successfully retrieved user data'));
    }

    /**
     * get list logs user
     *
     * @return JsonResponse
     */
    public function listLogs()
    {
        $user = auth('api')->user();
        $listLogs = $user->listLogs()->orderByDesc('id')->get();
        return response200($listLogs, __('Successfully retrieved user data'));
    }

    /**
     * delete one of the authenticated user's logs
     *
     * @return Response
     */
    public function deleteLogs(Request $request, $id)
    {
        $deleted = UserLog::where('id', $id)->where('user_id', $request->user('api')->id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'User logs not found'], 404);
        }

        return response()->json(['message' => 'User logs deleted successfully']);
    }

    /**
     * update profile user login
     *
     * Only the display name and DNS can be changed here; email, status, tokens,
     * server assignment etc. are not user-editable.
     *
     * @param ProfileRequest $request
     * @return Response
     */
    public function updateProfile(ProfileRequest $request)
    {
        $user = auth('api')->user();
        $data = array_filter(
            $request->only(['name', 'firstname', 'lastname', 'dns']),
            fn ($value) => $value !== null
        );
        $user->update($data);
        return response200($user, __('Successfully updated profile'));
    }

    /**
     * delete the authenticated user's own account
     *
     * @return Response
     */
    public function delete(Request $request, $id)
    {
        $user = $request->user('api');
        if ((string) $user->id !== (string) $id) {
            return response()->json(['message' => 'You can only delete your own account'], 403);
        }

        RevokeWireGuardPeer::forUser($user);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * update profile user login
     *
     * @param ProfileRequest $request
     * @return Response
     */
    public function updatePassword(ProfileRequest $request)
    {
        $user = auth('api')->user();
        $user->forceFill(['password' => bcrypt($request->new_password)])->save();
        // Invalidate every other session; this device gets the new token back.
        $token = $user->rotateApiToken();
        return response()->json([
            'data' => true,
            'token' => $token,
            'message' => __('Successfully updated password'),
        ]);
    }

    /**
     * get subscription user
     *
     * @return JsonResponse
     */
    public function subscription()
    {
        $user = auth('api')->user();
        $subs = $user->subscription;
        $subs->plan = $user->subscription->plan;
        return response200($subs, __('Successfully retrieved subscription data'));
    }

    /**
     * payment History
     *
     * @return JsonResponse
     */
    public function paymentHistory()
    {
        $user = auth('api')->user();
        $transactions = Transaction::where('user_id', $user->id)->whereIn('status', [2, 3])->orderbyDesc('id')->paginate(5);
        return response200($transactions, __('Successfully retrieved subscription data'));
    }

    /**
     * post log
     *
     * @return JsonResponse
     */
    public function log()
    {
        logLogin();
        return response200(true, __('Successfully inserted log'));
    }

    /**
     * Email a fresh 6-digit code (rate limited per address).
     */
    private function issueCode(string $email, string $subject, string $intro)
    {
        $limiterKey = 'api-code-send:' . Str::lower($email);
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_CODE_SENDS)) {
            return responseError(429, __('Too many codes requested. Please try again in :minutes minutes.', [
                'minutes' => ceil(RateLimiter::availableIn($limiterKey) / 60),
            ]));
        }

        $user = $this->usermodel->where('email', $email)->first();
        $code = generateVerificationCode();
        try {
            $user->forceFill([
                'email_token' => Str::random(100),
                'verification_code' => $code,
                'verification_code_sent_at' => now(),
            ])->save();
            $this->sendCodeMail($user->email, $subject, $intro . '<br/>' . __('Verification Code') . ': ' . $code);
        } catch (Exception $e) {
            report($e);
            return response500(null, __('Failed to send email, please try again.'));
        }

        RateLimiter::hit($limiterKey, self::LIMIT_WINDOW_SECONDS);
        RateLimiter::clear('api-code-attempts:' . Str::lower($email));

        return response200(null, __('Successfully sent to ') . $email);
    }

    /**
     * Validate an emailed code. Returns an error response, or null when valid.
     * Codes expire after app.verification_code_ttl minutes, and only a few
     * wrong guesses are allowed before a cooldown.
     */
    private function checkCode(?User $user, $code)
    {
        $limiterKey = 'api-code-attempts:' . Str::lower((string) optional($user)->email);
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_CODE_ATTEMPTS)) {
            return responseError(429, __('Too many attempts. Please request a new code.'));
        }

        $sentAt = optional($user)->verification_code_sent_at;
        $expired = !$sentAt || $sentAt->lt(now()->subMinutes(config('app.verification_code_ttl', 30)));
        $valid = $user
            && !empty($user->verification_code)
            && is_scalar($code)
            && hash_equals((string) $user->verification_code, (string) $code);

        if (!$valid) {
            RateLimiter::hit($limiterKey, self::LIMIT_WINDOW_SECONDS);
            return response422(['verification_code' => [__('Incorrect Verification Code')]]);
        }
        if ($expired) {
            return response422(['verification_code' => [__('This code has expired. Please request a new one.')]]);
        }

        RateLimiter::clear($limiterKey);
        return null;
    }

    private function sendCodeMail(string $email, string $subject, string $html): void
    {
        \Mail::send([], [], function ($message) use ($html, $email, $subject) {
            $message->to($email)
                ->subject($subject)
                ->html($html);
        });
    }
}
