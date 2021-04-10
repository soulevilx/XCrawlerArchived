<?php

namespace App\Console\Commands\Jav;

use App\Jobs\OnejavFetchJob;
use App\Models\Onejav;
use App\Models\XCrawlerLog;
use App\Services\Crawler\OnejavCrawler;
use Illuminate\Console\Command;

class OnejavDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jav:onejav-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch Onejav - Daily';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * @var OnejavCrawler $crawler
         */
        $crawler = app(OnejavCrawler::class);
        $items = $crawler->daily();

        $items->each(function ($item) {
            Onejav::firstOrCreate(
                [
                    'url' => $item->get('url'),
                ],
                $item->toArray() + ['source' => 'daily']
            );
        });
    }
}
