<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integrations\AresSMSService;
use App\Models\SpinRuns;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PlayFiverService;
use App\Traits\Affiliates\AffiliateHistoryTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Stevebauman\Location\Facades\Location;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    use AffiliateHistoryTrait;

    /*** Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth.jwt', ['except' => ['login', 'register', 'submitForgetPassword', 'submitResetPassword']]);
    }

    /*** @return \Illuminate\Http\JsonResponse
     */
    public function verify()
    {
        return response()->json(auth('api')->user());
    }

    /*** Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        try {
            $credentials = request(['email', 'password']);

            if (! $token = auth('api')->attempt($credentials)) {
                return response()->json(['error' => trans('Check credentials')], 400);
            }

            // Registrar o IP do usuário na atividade de login
            $user = auth('api')->user();

            // Obter informações de localização do IP
            $ipLocation = Location::get(request()->ip());
            $locationData = [];

            if ($ipLocation) {
                $locationData = [
                    'country_name' => $ipLocation->countryName,
                    'country_code' => $ipLocation->countryCode,
                    'region' => $ipLocation->regionName,
                    'city' => $ipLocation->cityName,
                ];
            }

            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'location' => $locationData,
                ])
                ->log('login');

            // Verificar se o usuário está banido
            $user = auth('api')->user();
            if ($user->banned) {
                auth('api')->logout();

                // Preparar mensagem com motivo do banimento
                $message = 'Sua conta está suspensa.';
                if ($user->ban_reason) {
                    $message .= ' Motivo: '.$user->ban_reason;
                }

                return response()->json([
                    'error' => $message,
                    'ban_reason' => $user->ban_reason,
                    'banned' => true,
                ], 403);
            }

            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token',
            ], 400);
        }
    }

    /*** Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $setting = \Helper::getSetting();

            $rules = [
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => ['required', Rules\Password::min(6)],
                'phone' => 'required',
                // 'cpf'         => 'required',
                'term_a' => 'required',
                // 'agreement' => 'required', // Removido pois não é utilizado no formulário
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $userData = $request->only(['name', 'password', 'email', 'phone', 'cpf']);
            $userData['phone'] = \Helper::soNumero($userData['phone']);

            // / criando dados do afiliado
            $userData['affiliate_revenue_share'] = $setting->revshare_percentage;
            $userData['affiliate_cpa'] = $setting->cpa_value;
            $userData['affiliate_baseline'] = $setting->cpa_baseline;

            // Enviar SMS/WhatsApp para novo registro
            $payload = [
                'name' => ! empty($userData['name']) ? $userData['name'] : null,
                'email' => ! empty($userData['email']) ? $userData['email'] : null,
                'type' => 'new',
                'event_identify' => 'Novo Cadastro',
                'phone' => ! empty($userData['phone']) ? $userData['phone'] : null,
                'username' => ! empty($userData['username']) ? $userData['username'] : null,
                'checkout' => ! empty($userData['checkout']) ? $userData['checkout'] : null,
                'value' => ! empty($userData['value']) ? $userData['value'] : null,
            ];
            AresSMSService::sendSMS($payload);

            if ($user = User::create($userData)) {
                if (isset($request->reference_code) && ! empty($request->reference_code)) {
                    // P20TUKHVRV
                    $checkAffiliate = User::where('inviter_code', $request->reference_code)->first();
                    if (! empty($checkAffiliate)) {
                        if ($checkAffiliate->affiliate_revenue_share > 0 || $checkAffiliate->affiliate_cpa > 0) {
                            $user->update(['inviter' => $checkAffiliate->id]);

                            self::saveAffiliateHistory($user);
                        }
                    }
                }

                if ($setting->disable_spin) {
                    if (! empty($request->spin_token)) {
                        try {
                            $str = base64_decode($request->spin_token);
                            $obj = json_decode($str);

                            $spin_run = SpinRuns::where([
                                'key' => $obj->signature,
                                'nonce' => $obj->nonce,
                            ])->first();

                            $data = $spin_run->prize;
                            $obj = json_decode($data);
                            $value = $obj->value;

                            Wallet::where('user_id', $user->id)->increment('balance_bonus', $value);

                        } catch (\Exception $e) {
                            return response()->json(['error' => $e->getMessage()], 400);
                        }
                    }
                }
                if ($setting->game_free_rounds_active_register) {
                    $dados = [
                        'username' => $user->email,
                        'game_code' => $setting->game_code_rounds_free_register,
                        'rounds' => $setting->rounds_free_register,
                    ];
                    PlayFiverService::RoundsFree($dados);
                }

                // Adicionar crédito inicial à carteira do usuário
                if (isset($setting->initial_credit_active) && $setting->initial_credit_active) {
                    $creditAmount = $setting->initial_credit_amount ?? 0.01;
                    $wallet = Wallet::where('user_id', $user->id)->first();
                    if ($wallet) {
                        $wallet->increment('balance', $creditAmount);

                        // Registrar a transação
                        Transaction::create([
                            'user_id' => $user->id,
                            'payment_id' => 'initial_credit_'.$user->id,
                            'status' => 1,
                            'amount' => $creditAmount,
                            'type' => 'deposit',
                            'gateway' => 'system',
                            'currency' => $setting->currency_code ?? 'EUR',
                            'info' => json_encode(['description' => 'Crédito inicial de registro']),
                        ]);
                    }
                }
                $credentials = $request->only(['email', 'password']);
                $token = auth('api')->attempt($credentials);
                if (! $token) {
                    return response()->json(['error' => 'Unauthorized'], 401);
                }

                // Registrar o IP do usuário na atividade de registro

                // Obter informações de localização do IP
                $ipLocation = Location::get(request()->ip());
                $locationData = [];

                if ($ipLocation) {
                    $locationData = [
                        'country_name' => $ipLocation->countryName,
                        'country_code' => $ipLocation->countryCode,
                        'region' => $ipLocation->regionName,
                        'city' => $ipLocation->cityName,
                    ];
                }

                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'ip' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'action' => 'register',
                        'location' => $locationData,
                    ])
                    ->log('user_registered');

                return $this->respondWithToken($token);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /*** Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /*** Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $user = auth('api')->user();

        // Registrar o IP do usuário na atividade de logout
        if ($user) {
            // Obter informações de localização do IP
            $ipLocation = Location::get(request()->ip());
            $locationData = [];

            if ($ipLocation) {
                $locationData = [
                    'country_name' => $ipLocation->countryName,
                    'country_code' => $ipLocation->countryCode,
                    'region' => $ipLocation->regionName,
                    'city' => $ipLocation->cityName,
                ];
            }

            activity()
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'location' => $locationData,
                ])
                ->log('logout');
        }

        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /*** Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /*** @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitForgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $token = Str::random(5);

        $psr = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (! empty($psr)) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        }

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now(),
        ]);

        try {
            // Aumentar timeout e memória para envio de email
            ini_set('max_execution_time', 120);
            $startMemory = memory_get_usage(true);

            // Tentar com mailer padrão primeiro
            \Mail::send('emails.forget-password', ['token' => $token, 'resetLink' => url('/reset-password/'.$token)], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password - dei.bet');
            });

            // Forçar garbage collection para liberar memória
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }

        } catch (\Swift_TransportException $e) {
            // Erro específico de transporte SMTP/Postmark
            \Log::error('Erro de transporte no envio de email', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).'MB',
                'mailer' => config('mail.default'),
            ]);

            // Tentar fallback para log se configurado
            try {
                config(['mail.default' => 'log']);
                \Mail::send('emails.forget-password', ['token' => $token, 'resetLink' => url('/reset-password/'.$token)], function ($message) use ($request) {
                    $message->to($request->email);
                    $message->subject('Reset Password - dei.bet (Fallback)');
                });

                \Log::info('Email de recuperação enviado via fallback log', ['email' => $request->email]);
            } catch (\Exception $fallbackError) {
                \Log::error('Falha no fallback de email', ['error' => $fallbackError->getMessage()]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Erro temporário no envio do email. Tente novamente em alguns minutos.',
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Erro geral ao enviar email de recuperação de senha', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2).'MB',
                'timeout_set' => ini_get('max_execution_time'),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Erro temporário no envio do email. Tente novamente em alguns minutos.',
            ], 500);
        }

        return response()->json(['status' => true, 'message' => 'We have e-mailed your password reset link!'], 200);
    }

    /*** @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitResetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',
                'token' => 'required',
            ]);

            $checkToken = DB::table('password_reset_tokens')->where('token', $request->token)->first();
            if (! empty($checkToken)) {
                $user = User::where('email', $request->email)->first();
                if (! empty($user)) {
                    if ($user->update(['password' => $request->password])) {
                        DB::table('password_reset_tokens')->where(['email' => $request->email])->delete();

                        return response()->json(['status' => true, 'message' => 'Your password has been changed!'], 200);
                    } else {
                        return response()->json(['error' => 'Erro ao atualizar senha'], 400);
                    }
                } else {
                    return response()->json(['error' => 'Email não é valido!'], 400);
                }
            }

            return response()->json(['error' => 'Token não é valido!'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /*** Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth('api')->user(),
            'expires_in' => time() + 1,
            // 'expires_in' => auth('api')->factory()->getTTL() * 6000000
        ]);
    }
}
