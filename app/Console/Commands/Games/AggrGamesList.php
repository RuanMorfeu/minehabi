<?php

namespace App\Console\Commands\Games;

use App\Traits\Commands\Games\AggrGamesCommandTrait;
use Illuminate\Console\Command;

class AggrGamesList extends Command
{
    use AggrGamesCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aggr:games-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        return self::getGames();
    }
}
