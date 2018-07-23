<?php

namespace nickurt\VersioHostFact\Tests;

class GetContactListTest extends BaseTest
{
    /** @test */
    public function it_can_get_contact_list_with_empty_contacts()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'ContactList' => [
                //
            ]
        ]);

        $this->assertSame(array(), $this->versio->getContactList());
    }

    /** @test */
    public function it_can_get_contact_list_with_multiple_contacts()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'ContactList' => [
                [
                    'contact_id' => '13377331',
                    'firstname' => 'FirstName',
                    'surname' => 'SurName',
                    'company' => 'Company',
                    'street' => 'Street',
                    'number' => '123',
                    'zipcode' => '1234AB',
                    'city' => 'City',
                    'country' => 'Country',
                    'phone' => '1234-567890',
                    'email' => 'email@contactlist.tld'
                ],
                [
                    'contact_id' => '73311337',
                    'firstname' => 'NameFirst',
                    'surname' => 'NameSur',
                    'company' => 'anyComp',
                    'street' => 'eetStr',
                    'number' => '321',
                    'zipcode' => '4321ZX',
                    'city' => 'ityC',
                    'country' => 'ntryCou',
                    'phone' => '0987-654321',
                    'email' => 'contactlist@email.tld'
                ],
            ]
        ]);

        $this->assertSame(array(array(
            'Handle' => '13377331',
            'Sex' => '',
            'Initials' => 'FirstName',
            'SurName' => 'SurName',
            'CompanyName' => 'Company',
            'Address' => 'Street 123',
            'ZipCode' => '1234AB',
            'City' => 'City',
            'Country' => 'Country',
            'PhoneNumber' => '1234-567890',
            'EmailAddress' => 'email@contactlist.tld',
        ), array(
            'Handle' => '73311337',
            'Sex' => '',
            'Initials' => 'NameFirst',
            'SurName' => 'NameSur',
            'CompanyName' => 'anyComp',
            'Address' => 'eetStr 321',
            'ZipCode' => '4321ZX',
            'City' => 'ityC',
            'Country' => 'ntryCou',
            'PhoneNumber' => '0987-654321',
            'EmailAddress' => 'contactlist@email.tld',
        )), $this->versio->getContactList());
    }

    /** @test */
    public function it_can_get_contact_list_with_one_contact()
    {
        $this->versio->shouldReceive('request')->andReturn([
            'ContactList' => [
                [
                    'contact_id' => '13377331',
                    'firstname' => 'FirstName',
                    'surname' => 'SurName',
                    'company' => 'Company',
                    'street' => 'Street',
                    'number' => '123',
                    'zipcode' => '1234AB',
                    'city' => 'City',
                    'country' => 'Country',
                    'phone' => '1234-567890',
                    'email' => 'email@contactlist.tld'
                ]
            ]
        ]);

        $this->assertSame(array(array(
            'Handle' => '13377331',
            'Sex' => '',
            'Initials' => 'FirstName',
            'SurName' => 'SurName',
            'CompanyName' => 'Company',
            'Address' => 'Street 123',
            'ZipCode' => '1234AB',
            'City' => 'City',
            'Country' => 'Country',
            'PhoneNumber' => '1234-567890',
            'EmailAddress' => 'email@contactlist.tld',
        )), $this->versio->getContactList());
    }
}