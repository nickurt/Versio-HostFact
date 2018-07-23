<?php

namespace nickurt\VersioHostFact\Tests;

class DeleteDomainTest extends BaseTest
{
    /** @test */
    public function it_can_delete_a_domain()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domainInfo' => [
                'domain' => 'domain-with-autorenew-to-false.com'
            ]
        ]);

        $this->assertTrue($this->versio->deleteDomain('domain-with-autorenew-to-false.com'));
    }
}