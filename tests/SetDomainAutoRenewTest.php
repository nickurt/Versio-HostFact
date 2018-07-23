<?php

namespace nickurt\VersioHostFact\Tests;

class SetDomainAutoRenewTest extends BaseTest
{
    /** @test */
    public function it_can_set_domain_autorenew_to_false()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domainInfo' => [
                'domain' => 'domain-with-autorenew-to-false.com'
            ]
        ]);

        $this->assertTrue($this->versio->setDomainAutoRenew('domain-with-autorenew-to-false.com', false));
    }

    /** @test */
    public function it_can_set_domain_autorenew_to_true()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domainInfo' => [
                'domain' => 'domain-with-autorenew-to-true.com'
            ]
        ]);

        $this->assertTrue($this->versio->setDomainAutoRenew('domain-with-autorenew-to-true.com', true));
    }
}