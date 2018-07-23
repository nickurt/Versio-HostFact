<?php

namespace nickurt\VersioHostFact\Tests;

class VersionTest extends BaseTest
{
    /** @test */
    public function it_can_get_module_version_information()
    {
        $this->assertSame(array(
            'name' => 'Versio',
            'api_version' => '1.0 REST API',
            'date' => '2018-07-05',
            'wefact_version' => '5.0.0',
            'autorenew' => true,
            'handle_support' => true,
            'cancel_direct' => true,
            'cancel_expire' => true,
            'dev_logo' => 'https://www.versio.nl/assets/images/logos/versio-logo.png',
            'dev_author' => 'Versio B.V.',
            'dev_website' => 'https://www.versio.nl/',
            'dev_email' => 'support@versio.nl',
            'domain_support' => true,
            'ssl_support' => true,
            'dns_management_support' => true,
            'dns_templates_support' => true,
            'dns_records_support' => true,
        ), \Versio::getVersionInformation());
    }
}