<?php

namespace App\Jobs;

use App\Models\TemporaryUrl;
use App\Models\XCityIdol;
use App\Services\Crawler\XCityIdolCrawler;
use App\Services\XCityIdolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\RateLimitedMiddleware\RateLimited;

class XCityIdolFetchPages implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public int $uniqueFor = 1800;
    public string $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return $this->url;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addDay();
    }

    /**
     * Attempt 1: Release after 60 seconds
     * Attempt 2: Release after 180 seconds
     * Attempt 3: Release after 420 seconds
     * Attempt 4: Release after 900 seconds
     */
    public function middleware()
    {
        if (config('app.env') !== 'testing') {
            $rateLimitedMiddleware = (new RateLimited())
                ->allow(3) // Allow 3 jobs
                ->everySecond()
                ->releaseAfterSeconds(30); // Release back to pool after 30 seconds

            return [$rateLimitedMiddleware];
        }

        return [];
    }

    public function handle()
    {
        $crawler = app(XCityIdolCrawler::class);
        $url = XCityIdol::ENDPOINT_URL . trim($this->url);
        $query = parse_url($url, PHP_URL_QUERY);
        $url = str_replace('?' . $query, '', $url);
        parse_str($query, $query);
        $query['num'] = XCityIdol::PER_PAGE;

        if ($pages = $crawler->getPages($url, $query)) {
            TemporaryUrl::firstOrCreate([
                'url' => $url,
                'source' => XCityIdolService::SOURCE,
                'data' => [
                    'pages' => $pages,
                    'current_page' => 1,
                    'payload' => $query,
                ],
                'state_code' => TemporaryUrl::STATE_INIT
            ]);
        }
    }
}