<?php

namespace App\Flickr\Tests\Feature\Command;

use App\Flickr\Tests\AbstractFlickrTest;
use App\Jobs\Flickr\ContactsJob;
use Illuminate\Support\Facades\Queue;

class ContactsTest extends AbstractFlickrTest
{
    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_get_contacts()
    {
        $this->artisan('flickr:contacts');
        Queue::assertPushed(ContactsJob::class);
    }
}
