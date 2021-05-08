<?php

namespace Tests\Unit\Command;

use App\Models\TemporaryUrl;
use App\Services\Client\CrawlerClientResponse;
use App\Services\Client\Domain\ResponseInterface;
use App\Services\Client\XCrawlerClient;
use App\Services\Crawler\XCityIdolCrawler;
use App\Services\Crawler\XCityVideoCrawler;
use App\Services\TemporaryUrlService;
use App\Services\XCityVideoService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class XCityVideoTest extends TestCase
{
    use RefreshDatabase;

    private MockObject|XCrawlerClient $mocker;
    /**
     * @var XCityVideoCrawler|Application|mixed
     */
    private mixed $crawler;

    public function setUp(): void
    {
        parent::setUp();
        app()->bind(ResponseInterface::class, CrawlerClientResponse::class);
        $this->mocker = $this->getMockBuilder(XCrawlerClient::class)->getMock();
        $this->mocker->method('init')->willReturnSelf();
        $this->mocker->method('setHeaders')->willReturnSelf();
        $this->mocker->method('setContentType')->willReturnSelf();
        $this->fixtures = __DIR__ . '/../../Fixtures/XCity';
    }

    public function test_videos_command()
    {
        $this->mocker->method('get')->willReturn($this->getSuccessfulMockedResponse('videos.html'));
        app()->instance(XCrawlerClient::class, $this->mocker);
        $this->crawler = app(XCityVideoCrawler::class);
        $service = app(TemporaryUrlService::class);

        $this->artisan('jav:xcity-videos');
        $this->assertEquals(1, $service->getItems(XCityVideoService::SOURCE)->count());
        $this->assertEquals(30, $service->getItems(XCityVideoService::SOURCE_VIDEO, TemporaryUrl::STATE_INIT, 100)->count());

        // Test whenever we completed 1 page
        $temporaryUrl = TemporaryUrl::first();
        $this->artisan('jav:xcity-videos');
        $temporaryUrl->refresh();
        $this->assertEquals(TemporaryUrl::STATE_COMPLETED, $temporaryUrl->state_code);

        $this->artisan('jav:xcity-videos');
        $this->assertEquals(1, $service->getItems(XCityVideoService::SOURCE)->count());
        $this->assertEquals(1, $service->getItems(XCityVideoService::SOURCE, TemporaryUrl::STATE_COMPLETED)->count());
    }

    public function test_idol_command()
    {
        $this->mocker->method('get')->willReturn($this->getSuccessfulMockedResponse('video.html'));
        app()->instance(XCrawlerClient::class, $this->mocker);
        $this->crawler = app(XCityIdolCrawler::class);

        $temporary = TemporaryUrl::factory()->create([
            'url' => $this->faker->url,
            'source' => XCityVideoService::SOURCE_VIDEO,
            'state_code' => TemporaryUrl::STATE_INIT,
            'data' => [
                'payload' => [

                ]
            ]
        ]);

        $this->artisan('jav:xcity-video');
        $sampleItem = json_decode($this->getFixture('video.json'), true);

        unset($sampleItem['url']);
        unset($sampleItem['sales_date']);
        unset($sampleItem['release_date']);
        unset($sampleItem['tags']);
        unset($sampleItem['actresses']);

        $this->assertDatabaseHas('x_city_videos', $sampleItem);
        $temporary->refresh();
        $this->assertEquals(TemporaryUrl::STATE_COMPLETED, $temporary->state_code);
    }
}