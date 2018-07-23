<?php

namespace nickurt\VersioHostFact\Tests;

class GetContactTest extends BaseTest
{
    /** @test */
    public function it_can_get_contact_information()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'contactInfo' => [
                'company' => 'CompanyName',
                'firstname' => 'Initials',
                'surname' => 'SurName',
                'street' => 'Street',
                'number' => '123',
                'zipcode' => '1234AB',
                'city' => 'City',
                'country' => 'Country',
                'phone' => '1234-567890',
                'email' => 'owner@emailaddress.tld',
            ]
        ]);

        $response = $this->versio->getContact('13377331');

        $this->assertEquals($this->getDummyWhoisData(), $response);
    }

    protected function getDummyWhoisData()
    {
        $whois = new \Whois();
        $whois->ownerCompanyName = 'CompanyName';
        $whois->ownerInitials = 'Initials';
        $whois->ownerSurName = 'SurName';
        $whois->ownerAddress = 'Street 123';
        $whois->ownerZipCode = '1234AB';
        $whois->ownerCity = 'City';
        $whois->ownerCountry = 'Country';
        $whois->ownerPhoneNumber = '1234-567890';
        $whois->ownerEmailAddress = 'owner@emailaddress.tld';

        return $whois;
    }
}