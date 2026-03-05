<?php

namespace App\Console\Commands;

use App\Events\MakeAdvsEvent;
use Illuminate\Console\Command;

class RefeshAdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ads:refresh';

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
        event(new MakeAdvsEvent());
    }
}
