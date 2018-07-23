<?php

namespace nickurt\VersioHostFact\Tests;

class CheckDomainTest extends BaseTest
{
    /** @test */
    public function it_can_check_if_domain_is_available()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domain' => 'this-domain-is-available.com',
            'available' => true,
            'push_required' => true,
        ]);

        $this->assertTrue($this->versio->checkDomain('this-domain-is-available.com'));
    }

    /** @test */
    public function it_can_check_if_domain_is_not_available()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domain' => 'this-domain-is-not-available.com',
            'available' => false,
            'push_required' => true,
        ]);

        $this->assertFalse($this->versio->checkDomain('this-domain-is-not-available.com'));
    }
}