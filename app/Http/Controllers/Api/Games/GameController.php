<?php

namespace App\Http\Controllers\Api\Games;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameExclusive;
use App\Models\GameExclusive2;
use App\Models\GameFavorite;
use App\Models\GameLike;
use App\Models\GameSpins;
use App\Models\Provider;
use App\Models\User;
use App\Models\Wallet;
use App\Traits\Providers\AggrTrait;
use App\Traits\Providers\DrakonTrait;
use App\Traits\Providers\FiversTrait;
use App\Traits\Providers\PlayFiverTrait;
use Illuminate\Http\Request;

class GameController extends Controller
{
    use AggrTrait,
        DrakonTrait,
        FiversTrait,
        PlayFiverTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $providers = Provider::with(['games', 'games.provider'])
            ->whereHas('games')
            ->orderBy('position', 'asc')
            ->where('status', 1)
            ->get(); // Isso retorna uma Collection, não um paginador

        // Modifica os games de cada provider
        $providers->transform(function ($provider) {
            // Acessa a relação "games" do provider e modifica cada jogo
            $provider->games->transform(function ($game) {
                $game->game_code = str_replace('/', '-', $game->game_code);

                return $game;
            });

            return $provider;
        });

        return response()->json(['providers' => $providers]);
    }

    public function index_old()
    {
        $providers = Provider::with(['games', 'games.provider'])
            ->whereHas('games')
            ->orderBy('name', 'desc')
            ->where('status', 1)
            ->get();

        return response()->json(['providers' => $providers]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function featured()
    {
        $featured_games = Game::with(['provider'])
            ->where('is_featured', 1)
            ->where('status', 1)
            ->orderBy('views', 'desc') // Ordenar por número de visualizações em ordem decrescente
            ->get(); // Isso retorna uma Collection, não um objeto Paginator

        // Modificação correta usando transform() diretamente na Collection
        $featured_games->transform(function ($game) {
            $game->game_code = str_replace('/', '-', $game->game_code);

            return $game;
        });

        return response()->json(['featured_games' => $featured_games]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function slots()
    {
        $slot_games = Game::with(['provider'])
            ->where('is_slot', 1)
            ->where('status', 1)
            ->orderBy('views', 'desc')
            ->get();

        $slot_games->transform(function ($game) {
            $game->game_code = str_replace('/', '-', $game->game_code);
            return $game;
        });

        return response()->json(['slot_games' => $slot_games]);
    }

    public function source()
    {
        try {
            $dados_user = User::where('id', auth('api')->id())->firstOrFail();

            if ($dados_user->is_demo_agent == 1) {
                $source_games = Game::with(['provider'])
                    ->where('only_demo', 1)
                    ->where('status', 1)
                    ->get();

                $source_games->getCollection()->transform(function ($game) {
                    $game->game_code = str_replace('/', '-', $game->game_code);

                    return $game;
                });

                return response()->json(['source_games' => $source_games]);
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    public function exclusive()
    {
        $user = auth()->user();
        $isInfluencer = $user && $user->is_demo_agent;

        $games = GameExclusive::where('active', 1)
            ->where('visible_in_home', 1)
            ->select([
                'id',
                'uuid as game_code',
                'name as game_name',
                'cover',
                'active',
                'description',
                'category_id',
                'velocidade',
                'influencer_velocidade',
                'winLength',
                'influencer_winLength',
                'loseLength',
                'influencer_loseLength',
                'xmeta',
                'influencer_xmeta',
                'coin_value',
                'influencer_coin_value',
                'views',
            ])
            ->get()
            ->map(function ($game) use ($isInfluencer) {
                $game->distribution = 'exclusive';
                $game->is_featured = 0;

                // Se o usuário for um influencer e os campos de influencer não forem nulos, use-os
                if ($isInfluencer) {
                    if (! is_null($game->influencer_winLength)) {
                        $game->winLength = $game->influencer_winLength;
                    }
                    if (! is_null($game->influencer_loseLength)) {
                        $game->loseLength = $game->influencer_loseLength;
                    }
                    if (! is_null($game->influencer_velocidade)) {
                        $game->velocidade = $game->influencer_velocidade;
                    }
                    if (! is_null($game->influencer_xmeta)) {
                        $game->xmeta = $game->influencer_xmeta;
                    }
                    if (! is_null($game->influencer_coin_value)) {
                        $game->coin_value = $game->influencer_coin_value;
                    }
                }

                return $game;
            });

        return response()->json(['exclusive_games' => $games]);
    }

    public function exclusive2()
    {
        $user = auth()->user();
        $isInfluencer = $user && $user->is_demo_agent;

        $games = GameExclusive2::where('active', 1)
            ->where('visible_in_home', 1)
            ->select([
                'id',
                'uuid as game_code',
                'name as game_name',
                'cover',
                'active',
                'description',
                'category_id',
                'game_type',
                'lives',
                'coin_rate',
                'meta_multiplier',
                'ghost_points',
                'difficulty',
                'jetpack_difficulty',
                'coin_multiplier',
                'game_difficulty',
                // Influencer fields
                'influencer_lives',
                'influencer_coin_rate',
                'influencer_meta_multiplier',
                'influencer_ghost_points',
                'influencer_jetpack_difficulty',
                'influencer_coin_multiplier',
                'influencer_game_difficulty',
                'influencer_difficulty',
                'views',
            ])
            ->get()
            ->map(function ($game) use ($isInfluencer) {
                $game->distribution = 'exclusive2';
                $game->is_featured = 0;

                // Se o usuário for um influencer e os campos de influencer não forem nulos, use-os
                if ($isInfluencer) {
                    if (! is_null($game->influencer_difficulty)) {
                        $game->difficulty = $game->influencer_difficulty;
                    }
                    if (! is_null($game->influencer_jetpack_difficulty)) {
                        $game->jetpack_difficulty = $game->influencer_jetpack_difficulty;
                    }
                    if (! is_null($game->influencer_game_difficulty)) {
                        $game->game_difficulty = $game->influencer_game_difficulty;
                    }
                    if (! is_null($game->influencer_lives)) {
                        $game->lives = $game->influencer_lives;
                    }
                    if (! is_null($game->influencer_coin_rate)) {
                        $game->coin_rate = $game->influencer_coin_rate;
                    }
                    if (! is_null($game->influencer_meta_multiplier)) {
                        $game->meta_multiplier = $game->influencer_meta_multiplier;
                    }
                    if (! is_null($game->influencer_ghost_points)) {
                        $game->ghost_points = $game->influencer_ghost_points;
                    }
                    if (! is_null($game->influencer_coin_multiplier)) {
                        $game->coin_multiplier = $game->influencer_coin_multiplier;
                    }
                }

                return $game;
            });

        return response()->json(['exclusive2_games' => $games]);
    }

    public function aggrGames()
    {
        $game = GameSpins::where('active', 1)->where('show_home', 1)
            ->select(['id', 'name as game_name', 'id_hash as game_code', 'image_long as cover', 'category as distribution'])
            ->get()
            ->map(function ($game) {
                $game->game_code = str_replace('/', '-', $game->game_code); // Substitui "/" por "-"

                return $game;
            });

        return response()->json(['aggr_games' => $game]);
    }

    /*** Source Provider
     *

     * @param Request $request
     * @param $token
     * @param $action
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function sourceProvider(Request $request, $token, $action)
    {
        $tokenOpen = \Helper::DecToken($token);
        $validEndpoints = ['session', 'icons', 'spin', 'freenum'];

        if (in_array($action, $validEndpoints)) {
            if (isset($tokenOpen['status']) && $tokenOpen['status']) {
                $game = Game::whereStatus(1)->where('game_code', $tokenOpen['game'])->first();
                if (! empty($game)) {
                    $controller = \Helper::createController($game->game_code);

                    switch ($action) {
                        case 'session':
                            return $controller->session($token);
                        case 'spin':
                            return $controller->spin($request, $token);
                        case 'freenum':
                            return $controller->freenum($request, $token);
                        case 'icons':
                            return $controller->icons();
                    }
                }
            }
        } else {
            return response()->json([], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function toggleFavorite($id)
    {
        if (auth('api')->check()) {
            $checkExist = GameFavorite::where('user_id', auth('api')->id())->where('game_id', $id)->first();
            if (! empty($checkExist)) {
                if ($checkExist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removido com sucesso']);
                }
            } else {
                $gameFavoriteCreate = GameFavorite::create([
                    'user_id' => auth('api')->id(),
                    'game_id' => $id,
                ]);

                if ($gameFavoriteCreate) {
                    return response()->json(['status' => true, 'message' => 'Criado com sucesso']);
                }
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function toggleLike($id)
    {
        if (auth('api')->check()) {
            $checkExist = GameLike::where('user_id', auth('api')->id())->where('game_id', $id)->first();
            if (! empty($checkExist)) {
                if ($checkExist->delete()) {
                    return response()->json(['status' => true, 'message' => 'Removido com sucesso']);
                }
            } else {
                $gameLikeCreate = GameLike::create([
                    'user_id' => auth('api')->id(),
                    'game_id' => $id,
                ]);

                if ($gameLikeCreate) {
                    return response()->json(['status' => true, 'message' => 'Criado com sucesso']);
                }
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $game = Game::with(['categories', 'provider'])->whereStatus(1)->find($id);

        if (! empty($game)) {
            if (auth('api')->check()) {
                $wallet = Wallet::where('user_id', auth('api')->user()->id)->first();

                // Verificar se o usuário tem o bloqueio de jogos ao vivo ativado
                if (auth('api')->user()->block_live_games) {
                    // Verificar se o jogo é de um dos provedores bloqueados (Evolution, Creedz, Sagaming)
                    $blockedProviders = ['evolution', 'creedz', 'sagaming'];
                    $providerName = strtolower($game->provider->name);
                    $providerCode = strtolower($game->provider->code);

                    foreach ($blockedProviders as $blockedProvider) {
                        if (str_contains($providerName, $blockedProvider) || str_contains($providerCode, $blockedProvider)) {
                            return response()->json([
                                'error' => 'Acesso bloqueado a jogos ao vivo deste provedor',
                                'status' => false,
                                'action' => 'blocked',
                            ], 403);
                        }
                    }
                }

                if ($wallet->total_balance > 0 || $game->id == 1580) {
                    $game->increment('views');

                    $token = \Helper::MakeToken([
                        'id' => auth('api')->user()->id,
                        'game' => $game->game_code,
                    ]);

                    switch ($game->distribution) {

                        case 'source':
                            return response()->json([
                                'game' => $game,
                                'gameUrl' => url('/originals/'.$game->game_code.'/index.html?token='.$token),
                                'token' => $token,
                            ]);
                        case 'drakon':
                            // Verificar se o usuário é influencer
                            if (auth('api')->user()->is_demo_agent) {
                                return response()->json([
                                    'error' => 'Não disponível para influenciadores',
                                    'status' => false,
                                    'action' => 'restricted',
                                ], 200);
                            }

                            $gameLauncher = self::GameLaunchDrakon($game);

                            if ($gameLauncher) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $gameLauncher,
                                    'token' => $token,
                                ]);
                            } else {
                                return response()->json();
                            }
                        case 'fivers':
                            // Verificar se o usuário é influencer
                            if (auth('api')->user()->is_demo_agent) {
                                return response()->json([
                                    'error' => 'Não disponível para influenciadores',
                                    'status' => false,
                                    'action' => 'restricted',
                                ], 200);
                            }

                            $fiversLaunch = self::GameLaunchFivers($game->provider->code, $game->game_id, 'pt', auth('api')->user()->id);

                            if (isset($fiversLaunch['launch_url'])) {
                                return response()->json([
                                    'game' => $game,
                                    'gameUrl' => $fiversLaunch['launch_url'],
                                    'token' => $token,
                                ]);
                            }

                            return response()->json(['error' => $fiversLaunch, 'status' => false], 400);
                        case 'play_fiver':
                            $playfiver = self::playFiverLaunch($game->game_id, $game->only_demo);

                            return response()->json([
                                'game' => $game,
                                'gameUrl' => $playfiver['launch_url'],
                                'token' => $token,
                            ]);
                        case 'spin':
                            // Verificar se o usuário é influencer
                            if (auth('api')->user()->is_demo_agent) {
                                return response()->json([
                                    'error' => 'Não disponível para influenciadores',
                                    'status' => false,
                                    'action' => 'restricted',
                                ], 200);
                            }

                            $aggregtr = self::startGameAggregtr(auth('api')->user()->id, auth('api')->user()->name, $game->game_code);

                            return [
                                'game' => $game,
                                'gameUrl' => $aggregtr['launch_url'],
                                'token' => '',
                            ];
                    }
                }

                return response()->json(['error' => 'É preciso ter saldo para jogar, faça uma recarga', 'status' => false, 'action' => 'deposit'], 200);
            }

            return response()->json(['error' => 'Você precisa tá autenticado para jogar', 'status' => false], 400);
        }

        return response()->json(['error' => '', 'status' => false], 400);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function allGames(Request $request)
    {
        $query = Game::query();

        $query->with(['provider', 'categories']);

        // Filtro por provedor
        if (! empty($request->provider) && $request->provider != 'all') {
            $query->where('provider_id', $request->provider);
        }

        // Filtro por categoria
        if (! empty($request->category) && $request->category != 'all') {
            $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->category);
            });
        }

        // Filtro por termo de busca
        if (isset($request->searchTerm) && ! empty($request->searchTerm) && strlen($request->searchTerm) > 2) {
            $query->whereLike(['game_code', 'game_name', 'distribution', 'provider.name'], $request->searchTerm);
        } else {
            $query->orderBy('views', 'desc');
        }

        // Executa a consulta e obtém os resultados paginados
        $games = $query
            ->where('status', 1)
            ->where('provider_id', '<>', 9)
            ->paginate(12)
            ->appends(request()->query());

        // Aplica a transformação nos itens da coleção
        $games->getCollection()->transform(function ($game) {
            $game->game_code = str_replace('/', '-', $game->game_code);

            return $game;
        });

        return response()->json(['games' => $games]);
    }

    public function allGames_old(Request $request)
    {
        $query = Game::query();

        $query->with(['provider', 'categories']);

        if (! empty($request->category) && $request->category != 'all') {
            $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->where('slug', $request->category);
            });
        }

        if (isset($request->searchTerm) && ! empty($request->searchTerm) && strlen($request->searchTerm) > 2) {
            $query->whereLike(['game_code', 'game_name', 'distribution', 'provider.name'], $request->searchTerm);
        } else {
            $query->orderBy('views', 'desc');
        }

        $games = $query
            ->where('status', 1)
            ->where('provider_id', '<>', 9)
            ->paginate(12)->appends(request()->query());

        return response()->json(['games' => $games]);
    }

    public function webhookPlayFiver(Request $request)
    {
        return self::webhookPlayFiverAPI($request);
    }

    public function webhookGoldApiMethod(Request $request)
    {
        return self::WebhooksFivers($request);
    }

    public function getGamesByProvider(Request $request)
    {
        $providerId = $request->input('provider_id');
        $perPage = $request->input('per_page', 6);
        $games = Game::where('provider_id', $providerId)->where('status', 1)->paginate($perPage);

        return response()->json($games);
    }

    /**
     * @return mixed
     */
    public function webhookDrakonMethod(Request $request)
    {
        return self::WebhookDrakon($request);
    }

    public function webhookUserBalanceMethod(Request $request)
    {
        return self::GetBalanceInfo($request);
    }

    public function webhookAggrTrait(Request $request)
    {
        return self::WebhooksAggrTrait($request);
    }

    /*public function webhookGameCallbackMethod(Request $request)
    {
        return self::GameCallbackWorldSlot($request);
    }*/
}
