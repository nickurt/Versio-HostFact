<?php

namespace nickurt\VersioHostFact\Tests;

class GetTokenTest extends BaseTest
{
    /** @test */
    public function it_can_get_token_for_com_tld_domain()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domainInfo' => [
                'domain' => 'it-can-get-token-for-com-tld-domain.com',
                'epp_code' => 'ePpCoDe180723'
            ]
        ]);

        $this->assertSame('ePpCoDe180723', $this->versio->getToken('it-can-get-token-for-com-tld-domain.com'));
    }

    /** @test */
    public function it_will_throw_message_for_be_tld_domain()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'domainInfo' => [
                'domain' => 'it-will-throw-message-for-be-tld-domain.be',
                'epp_code' => 'ePpCoDe180723'
            ]
        ]);

        $this->assertSame('EPP code will be sent by email', $this->versio->getToken('it-will-throw-message-for-be-tld-domain.be'));
    }
}