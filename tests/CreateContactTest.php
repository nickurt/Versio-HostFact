<?php

namespace nickurt\VersioHostFact\Tests;

class CreateContactTest extends BaseTest
{
    /** @test */
    public function it_can_create_new_contact_and_return_handle_id()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'contact_id' => '13377331'
        ]);

        $this->assertSame('13377331', $this->versio->createContact(
            $this->getDummyWhoisData()
        ));
    }

    protected function getDummyWhoisData()
    {
        $whois = new \Whois();
        $whois->ownerCompanyName = 'CompanyName';
        $whois->ownerInitials = 'Initials';
        $whois->ownerSurName = 'SurName';
        $whois->ownerEmailAddress = 'owner@emailaddress.tld';
        $whois->ownerPhoneNumber = '1234-567890';
        $whois->ownerAddress = 'Address 123';
        $whois->ownerStreetNumberAddon = 'AB';
        $whois->ownerZipCode = '1234AB';
        $whois->ownerCity = 'City';
        $whois->ownerCountry = 'Country';

        return $whois;
    }
}