<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Integrations\AresSMSService;
use App\Models\AffiliateWithdraw;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AffiliateController extends Controller
{
    /*** Display a listing of the resource.
     */
    public function index()
    {
        $user = auth('api')->user();

        $indicationsSQL = DB::select('SELECT COUNT(DISTINCT ah.user_id) as totalDepositos 
            FROM affiliate_histories ah
            WHERE ah.inviter = ? 
            AND ah.commission_type = "cpa" 
            AND ah.commission_paid > 0', [auth('api')->id()]);
        $indications = $indicationsSQL[0]->totalDepositos;

        // Obtém o total de registros
        $totalRegisters = User::where('inviter', auth('api')->id())->count();

        // Calcula quantos registros devem ser mostrados com base na porcentagem
        $visibleRegisters = floor($totalRegisters * ($user->affiliate_register_percentage / 100));

        // Calcula o total de ganhos CPA (mesma métrica do StatsUserDetailOverview)
        $totalAfiliadosCPA = DB::table('affiliate_histories')
            ->where('inviter', auth('api')->id())
            ->where('commission_type', 'cpa')
            ->sum('commission_paid');

        // Calcula o total de ganhos RevShare (mesma métrica do StatsUserDetailOverview)
        $totalAfiliadosRevshare = DB::table('affiliate_histories')
            ->where('inviter', auth('api')->id())
            ->where('commission_type', 'revshare')
            ->sum('commission_paid');

        // Calcula o total de primeiros depósitos dos indicados (mesma métrica do StatsUserDetailOverview)
        $primeiroDepositoIndicados = DB::table('deposits as d1')
            ->whereIn('d1.user_id', User::where('inviter', auth('api')->id())->where('is_demo_agent', 0)->pluck('id'))
            ->where('d1.status', 1)
            ->whereNotExists(function ($query) {
                $query->from('deposits as d2')
                    ->whereRaw('d1.user_id = d2.user_id')
                    ->whereRaw('d2.created_at < d1.created_at')
                    ->where('d2.status', 1);
            })
            ->sum('d1.amount');

        $walletDefault = Wallet::where('user_id', auth('api')->id())->first();

        return response()->json([
            'status' => true,
            'code' => auth('api')->user()->inviter_code,
            'url' => config('app.url').'/ref/'.auth('api')->user()->inviter_code,
            'indications' => $indications,
            'indications_total' => $visibleRegisters,
            'total_cpa' => $totalAfiliadosCPA,
            'total_revshare' => $totalAfiliadosRevshare,
            'primeiro_deposito_total' => $primeiroDepositoIndicados,
            'wallet' => $walletDefault,
        ]);
    }

    /*** Show the form for creating a new resource.
     */
    public function generateCode()
    {
        $code = $this->gencode();
        $setting = \Helper::getSetting();

        if (! empty($code)) {
            $user = auth('api')->user();
            \DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => 2,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ],
            );

            if (auth('api')->user()->update(['inviter_code' => $code, 'affiliate_revenue_share' => $setting->revshare_percentage])) {
                // Enviar SMS/WhatsApp para novo afiliado
                $payload = [
                    'name' => ! empty($user->name) ? $user->name : null,
                    'email' => ! empty($user->email) ? $user->email : null,
                    'type' => 'pix-paid',
                    'event_identify' => 'Novo Afiliado',
                    'phone' => ! empty($user->phone) ? $user->phone : null,
                    'username' => ! empty($user->username) ? $user->username : null,
                    'checkout' => $code,
                    'value' => 0,
                ];

                AresSMSService::sendSMS($payload);

                return response()->json(['status' => true, 'message' => trans('Successfully generated code')]);
            }

            return response()->json(['error' => ''], 400);
        }

        return response()->json(['error' => ''], 400);
    }

    /*** @return null
     */
    private function gencode()
    {
        $code = \Helper::generateCode(10);

        $checkCode = User::where('inviter_code', $code)->first();
        if (empty($checkCode)) {
            return $code;
        }

        return $this->gencode();
    }

    /*** Store a newly created resource in storage.
     */
    /*** Store a newly created resource in storage.
     */
    public function makeRequest(Request $request)
    {
        // Obtendo as configurações de saque do usuário
        $settings = Setting::where('id', 1)->first();

        // Verificando se as configurações foram encontradas e se os limites de saque foram definidos
        if ($settings) {
            $withdrawalLimit = $settings->withdrawal_limit;
            $withdrawalPeriod = $settings->withdrawal_period;
        } else {
            // Caso as configurações não tenham sido encontradas, defina os valores padrão ou trate conforme necessário
            $withdrawalLimit = null;
            $withdrawalPeriod = null;
        }

        if ($withdrawalLimit !== null && $withdrawalPeriod !== null) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();

            switch ($withdrawalPeriod) {
                case 'daily':
                    break;

                case 'weekly':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'monthly':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
                case 'yearly':
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
            }

            $withdrawalCount = AffiliateWithdraw::where('user_id', auth('api')->user()->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            if ($withdrawalCount >= $withdrawalLimit) {
                return response()->json(['message' => 'Você atingiu o limite de saques para este período'], 400);
            }
        }

        $rules = [
            'amount' => ['required', 'numeric'],
            'pix_type' => 'required',
            'name' => ['required', 'string', 'regex:/^[\p{L}]+ [\p{L}]+/u'], // Valida nome e sobrenome
            'nif' => ['required', 'string', 'min:9'], // Valida NIF
        ];

        switch ($request->pix_type) {
            case 'document':
                $rules['pix_key'] = 'required|cpf_ou_cnpj';
                break;
            case 'email':
                $rules['pix_key'] = 'required|email';
                break;
            default:
                $rules['pix_key'] = 'required';
                break;
        }

        $validator = Validator::make($request->all(), $rules, [
            'name.regex' => 'Por favor, insira nome e sobrenome válidos',
            'nif.required' => 'Por favor, insira o NIF',
            'nif.min' => 'NIF deve ter pelo menos 9 dígitos',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Verificando se o usuário tem saldo suficiente para o saque
        $comission = auth('api')->user()->wallet->refer_rewards;

        if (floatval($comission) >= floatval($request->amount) && floatval($request->amount) > 0) {
            // Criando o registro de saque
            AffiliateWithdraw::create([
                'user_id' => auth('api')->id(),
                'amount' => $request->amount,
                'pix_key' => $request->pix_key,
                'pix_type' => $request->pix_type,
                'currency' => 'EUR',
                'symbol' => '€',
                'name' => $request->name,
                'nif' => $request->nif,
            ]);

            // Decrementando o saldo do usuário
            auth('api')->user()->wallet->decrement('refer_rewards', $request->amount);

            // Retornando mensagem de sucesso
            return response()->json(['message' => trans('Commission withdrawal successfully carried out')], 200);
        }

        // Retornando mensagem de erro se não houver saldo suficiente
        return response()->json(['message' => trans('Commission withdrawal error')], 400);
    }
}
