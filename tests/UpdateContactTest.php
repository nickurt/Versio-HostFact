<?php

namespace nickurt\VersioHostFact\Tests;

class UpdateContactTest extends BaseTest
{
    /** @test */
    public function it_will_throw_error_because_update_contact_is_not_allowed()
    {
        $this->assertFalse($this->versio->updateContact('13377331', $this->getDummyWhoisData()));

        $this->assertEquals([
            'Versio: Het bewerken van een contact is niet mogelijk.',
        ], $this->versio->Error);
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