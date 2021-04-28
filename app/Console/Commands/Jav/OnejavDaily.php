<?php

namespace App\Console\Commands\Jav;

use App\Models\Onejav;
use App\Models\XCrawlerLog;
use App\Services\Crawler\OnejavCrawler;
use App\Services\OnejavService;
use Carbon\Carbon;
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
        $service = app(OnejavService::class);
        $items = $crawler->daily();

        $items->each(function ($item) use ($service) {
            $service->create($item->get('url'), $item->toArray(), 'daily');
        });

        XCrawlerLog::create([
            'url' => Onejav::HOMEPAGE_URL . '/' . Carbon::now()->format(Onejav::DAILY_FORMAT),
            'payload' => array_merge_recursive(
                ['items' => $items->map(function ($item) {
                    return $item->get('url');
                })],
                ['count' => $items->count()]
            ),
            'source' => 'onejav.daily',
            'succeed' => !$items->isEmpty()
        ]);
    }
}
