<?php

require_once '3rdparty/domain/IRegistrar.php';
require_once '3rdparty/domain/standardfunctions.php';

class Versio implements IRegistrar
{
    /**
     * Contains the API key
     *
     * @var string
     */
    public $User;

    /**
     * Contains the API secret
     *
     * @var string
     */
    public $Password;

    /**
     * Array with error messages
     *
     * @var array
     */
    public $Error = [];

    /**
     * Array with warning messages
     *
     * @var array
     */
    public $Warning = [];

    /**
     * Array with warning messages
     *
     * @var array
     */
    public $Success = [];

    /**
     * Defines the default registration period in years
     *
     * @var int
     */
    public $Period = 1;

    /**
     * Array with register handles that are stored by HostFact
     *
     * @var array
     */
    public $registrarHandles = [];

    /**
     * Contains the class name
     *
     * @var string
     */
    private $ClassName = __CLASS__;

    /**
     * Check whether a domain is already registered or not.
     *
     * @param string $domain The name of the domain that needs to be checked.
     * @return bool True if free, False if not free, False and $this->Error[] in case of error.
     */
    public function checkDomain($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain . '/availability');

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        }

        return (bool) $response['available'];
    }

    /**
     * Create a new whois contact
     *
     * @param array $whois The whois information for the new contact.
     * @param mixed $type The contact type. This is only used to access the right data in the $whois object.
     * @return bool|mixed Handle when the new contact was created successfully; False otherwise
     */
    public function createContact($whois, $type = HANDLE_OWNER)
    {
        // Determine which contact type should be found
        switch ($type) {
            case HANDLE_OWNER:
                $prefix = "owner";
                break;
            case HANDLE_ADMIN:
                $prefix = "admin";
                break;
            case HANDLE_TECH:
                $prefix = "tech";
                break;
            default:
                $prefix = "";
                break;
        }

        $whois->getParam($prefix, 'Address');

        $countryCode = $whois->{$prefix . 'Country'};

        if (strlen($countryCode) > 2) {
            $countryCode = str_replace('EU-', '', $countryCode);
        }

        if (strlen($countryCode) != 2) {
            $countryCode = 'NL';
        }

        $sStreet = $whois->{$prefix . 'Address'};
        $iNumber = filter_var($sStreet, FILTER_SANITIZE_NUMBER_INT);
        $sStreet = str_replace($iNumber, '', $sStreet);

        // registrant information
        $contactDetails = [];
        $contactDetails['company'] = $whois->{$prefix . 'CompanyName'};
        $contactDetails['firstname'] = $whois->{$prefix . 'Initials'};
        $contactDetails['surname'] = $whois->{$prefix . 'SurName'};
        $contactDetails['email'] = $whois->{$prefix . 'EmailAddress'};
        $contactDetails['phone'] = $whois->{$prefix . 'PhoneNumber'};
        $contactDetails['street'] = $sStreet;
        $contactDetails['number'] = $iNumber;
        $contactDetails['number_addition'] = $whois->{$prefix . 'StreetNumberAddon'};
        $contactDetails['zipcode'] = $whois->{$prefix . 'ZipCode'};
        $contactDetails['city'] = $whois->{$prefix . 'City'};
        $contactDetails['country'] = $countryCode;

        $response = $this->request('POST', '/contacts', $contactDetails);

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return $response['contact_id'];
        }
    }

    /**
     * Delete a domain
     *
     * @param string $domain The name of the domain that you want to delete.
     * @param string $delType end|now
     * @return bool True if the domain was successfully removed; False otherwise;
     */
    public function deleteDomain($domain, $delType = 'end')
    {
        return $this->setDomainAutoRenew($domain, false);
    }

    /**
     * Get information available of the requested contact.
     *
     * @param string $handle The handle of the contact to request.
     * @return bool|array Information available about the requested contact.
     */
    public function getContact($handle)
    {
        $response = $this->request('GET', '/contacts/' . $handle);

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $whois = new whois();

            // The contact is found
            $whois->ownerCompanyName = $response['contactInfo']['company'];
            $whois->ownerInitials = $response['contactInfo']['firstname'];
            $whois->ownerSurName = $response['contactInfo']['surname'];
            $whois->ownerAddress = $response['contactInfo']['street'] . ' ' . $response['contactInfo']['number'];
            $whois->ownerZipCode = $response['contactInfo']['zipcode'];
            $whois->ownerCity = $response['contactInfo']['city'];
            $whois->ownerCountry = $response['contactInfo']['country'];
            $whois->ownerPhoneNumber = $response['contactInfo']['phone'];
            $whois->ownerEmailAddress = $response['contactInfo']['email'];

            return $whois;
        }
    }

    /**
     * Get the handle of a contact.
     *
     * @param array $whois The whois information of contact
     * @param string $type The type of person. This is used to access the right fields in the whois object.
     * @return bool
     */
    public function getContactHandle($whois = [], $type = HANDLE_OWNER)
    {
        // Determine which contact type should be found
        switch ($type) {
            case HANDLE_OWNER:
                $prefix = "owner";
                break;
            case HANDLE_ADMIN:
                $prefix = "admin";
                break;
            case HANDLE_TECH:
                $prefix = "tech";
                break;
            default:
                $prefix = "";
                break;
        }

        $response = $this->request('GET', '/contacts' . $whois->{$prefix . 'Handle'});

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return $response['contactInfo']['contact_id'];
        }
    }

    /**
     * Get a list of contact handles available
     *
     * @param string $surname Surname to limit the number of records in the list.
     * @return array|bool List of all contact matching the $surname search criteria.
     */
    public function getContactList($surname = "")
    {
        $response = $this->request('GET', '/contacts');

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $contactList = [];

            foreach ($response['ContactList'] as $contact) {
                $contactList[] = [
                    'Handle' => $contact['contact_id'],
                    'Sex' => '',
                    'Initials' => $contact['firstname'],
                    'SurName' => $contact['surname'],
                    'CompanyName' => $contact['company'],
                    'Address' => $contact['street'] . ' ' . $contact['number'],
                    'ZipCode' => $contact['zipcode'],
                    'City' => $contact['city'],
                    'Country' => $contact['country'],
                    'PhoneNumber' => $contact['phone'],
                    'EmailAddress' => $contact['email'],
                ];
            }

            return $contactList;
        }
    }

    /**
     * Retrieve all DNS templates from registrar
     *
     * @return array|bool List of all DNS templates
     */
    public function getDNSTemplates()
    {
        $response = $this->request('GET', '/dnstemplates');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $dns_templates = [];
            $teller = 1;

            foreach ($response['dnstemplatesList'] as $template) {
                $dns_templates['templates'][$teller]['id'] = $template['id'];
                $dns_templates['templates'][$teller]['name'] = $template['name'];;
                $teller++;
            }

            return $dns_templates;
        }
    }

    /**
     * Get the DNS zone of a domain
     *
     * @param string $domain The domain
     * @return array|bool Array with DNS zone info and DNS records
     */
    public function getDNSZone($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain . '?show_dns_records=true');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $record_type = 'records';
            $i = 0;
            $dns_zone = [];

            foreach ($response['domainInfo']['dns_records'] as $records) {
                $dns_zone[$record_type][$i]['name'] = $records['name'];
                $dns_zone[$record_type][$i]['type'] = $records['type'];
                $dns_zone[$record_type][$i]['value'] = $records['value'];
                $dns_zone[$record_type][$i]['priority'] = $records['prio'];
                $dns_zone[$record_type][$i]['ttl'] = $records['ttl'];
                $i++;
            }

            return $dns_zone;
        }
    }

    /**
     * Get all available information of the given domain
     *
     * @param string $domain The domain for which the information is requested.
     * @return array|bool|mixed The array containing all information about the given domain
     */
    public function getDomainInformation($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $lastyear = strtotime("-1 year", strtotime($response['domainInfo']['expire-date']));

            $whois = new whois();

            if ($response['domainInfo']['registrant_id'] == null) {
                $whois->ownerHandle = 'ABCD001';
                $whois->adminHandle = 'ABCD001';
                $whois->techHandle = 'ABCD002';
            } else {
                $whois->ownerHandle = $response['domainInfo']['registrant_id'];
                $whois->adminHandle = $response['domainInfo']['registrant_id'];
                $whois->techHandle = $response['domainInfo']['registrant_id'];
            }

            $response = [
                'Domain' => $domain,
                'Information' => [
                    'nameservers' => $response['domainInfo']['ns'],
                    'whois' => $whois,
                    'expiration_date' => $response['domainInfo']['expire-date'],
                    'registration_date' => date("Y-m-d", $lastyear),
                    'authkey' => $this->getToken($domain)
                ]
            ];

            return $response;
        }
    }

    /**
     * Get a list of all the domains
     *
     * @param string $contactHandle The handle of a contact, so the list could be filtered (usefull for updating domain whois data)
     * @return array|bool A list of all domains available in the system.
     */
    public function getDomainList($contactHandle = "")
    {
        if ($contactHandle != "") {
            $this->Error[] = 'Het filteren op een contact is momenteel niet mogelijk';
        }

        $response = $this->request('GET', '/domains?status=OK');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        }

        $domainsList = [];

        foreach ($response['DomainsList'] as $domainlist) {
            $nameservers = [];

            foreach ($domainlist['ns'] as $ns) {
                array_push($nameservers, $ns['ns']);
            }

            $whois = new Whois();

            if ($domainlist['registrant_id'] == null) {
                $whois->ownerHandle = 'ABCD001';
            } else {
                $response = $this->request('GET', '/contacts/' . $domainlist['registrant_id']);

                if ($response['error']) {
                    $whois->ownerHandle = 'ABCD001';
                } else {
                    $whois->ownerHandle = $response['contactInfo']['contact_id'];

                    $whois->ownerSex = 'm';
                    $whois->ownerInitials = $response['contactInfo']['firstname'];
                    $whois->ownerSurName = $response['contactInfo']['surname'];
                    $whois->ownerCompanyName = $response['contactInfo']['company'];

                    $whois->ownerAddress = $response['contactInfo']['street'] . ' ' . $response['contactInfo']['number'];
                    $whois->ownerZipCode = $response['contactInfo']['zipcode'];
                    $whois->ownerCity = $response['contactInfo']['city'];
                    $whois->ownerCountry = $response['contactInfo']['country'];

                    $whois->ownerPhoneNumber = $response['contactInfo']['phone'];
                    $whois->ownerEmailAddress = $response['contactInfo']['email'];
                }
            }

            $whois->adminHandle = 'ABCD001';
            $whois->techHandle = 'ABCD001';

            $lastyear = strtotime("-1 year", strtotime($domainlist['expire-date']));

            $domain = [];
            $domain['Domain'] = $domainlist['domain'];
            $domain['Information'] = [
                'nameservers' => $nameservers,
                'whois' => $whois,
                'expiration_date' => $domainlist['expire-date'],
                'registration_date' => date("Y-m-d", $lastyear)
            ];

            array_push($domainsList, $domain);
        }

        return $domainsList;
    }

    /**
     * Get domain whois handles
     *
     * @param string $domain Domain
     * @return array|bool Handles
     */
    public function getDomainWhois($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $contacts = [];

            if ($response['domainInfo']['registrant_id'] == null) {
                $contacts['ownerHandle'] = 'ABCD001';
                $contacts['adminHandle'] = 'ABCD001';
                $contacts['techHandle'] = 'ABCD001';
            } else {
                $contacts['ownerHandle'] = $response['domainInfo']['registrant_id'];
                $contacts['adminHandle'] = $response['domainInfo']['registrant_id'];
                $contacts['techHandle'] = $response['domainInfo']['registrant_id'];
            }

            return $contacts;
        }
    }

    /**
     * Check domain information for one or more domains
     *
     * @param array $list_domains Array with list of domains. Key is domain, value must be filled.
     * @return array listDomains
     */
    public function getSyncData($list_domains)
    {
        $max_domains_to_check = 10;

        $checked_domains = 0;

        foreach ($list_domains as $domain_name => $value) {
            $response = $this->request('GET', '/domains/' . $domain_name);

            if ($response['error']) {
                $list_domains[$domain_name]['Status'] = 'error';
                $list_domains[$domain_name]['Error_msg'] = 'Domain not found';
                continue;
            }

            // Add data
            $ns = $response['domainInfo']['ns'];
            $nameservers = [];

            foreach ($ns as $nameserver) {
                array_push($nameservers, $nameserver['ns']);
            }

            // extend the list_domains array with data from the registrar
            $list_domains[$domain_name]['Information']['nameservers'] = $nameservers;
            $list_domains[$domain_name]['Information']['expiration_date'] = $response['domainInfo']['expire-date'];
            $list_domains[$domain_name]['Information']['auto_renew'] = $response['domainInfo']['auto_renew'];
            $list_domains[$domain_name]['Information']['lock'] = $response['domainInfo']['lock'];
            $list_domains[$domain_name]['Status'] = 'success';

            // Increment counter
            $checked_domains++;

            // Stop loop after max domains
            if ($checked_domains > $max_domains_to_check) {
                break;
            }
        }

        // Return list  (domains which aren't completed with data, will be synced by a next cronjob)
        return $list_domains;
    }

    /**
     * Get EPP code/token
     *
     * @param string $domain Domain
     * @return bool|string
     */
    public function getToken($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain . '?show_epp_code=true');

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $explodedDomain = explode('.', $domain);

            if ($explodedDomain[1] == 'be') {
                return 'EPP code will be sent by email';
            } else {
                return $response['domainInfo']['epp_code'];
            }
        }
    }

    /**
     * Get class version information
     *
     * @return array
     */
    public static function getVersionInformation()
    {
        return require_once __DIR__ . '/version.php';
    }

    /**
     * Change the lock status of the specified domain.
     *
     * @param string $domain The domain to change the lock state for
     * @param bool $lock The new lock state (True|False)
     * @return bool True is the lock state was changed successfully
     */
    public function lockDomain($domain, $lock = true)
    {
        $response = $this->request('POST', '/domains/' . $domain . '/update', [
            'lock' => $lock
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
        } else {
            return true;
        }
    }

    /**
     * Register a new domain
     *
     * @param string $domain The domainname that needs to be registered.
     * @param array $nameservers The nameservers for the new domain.
     * @param array $whois The customer information for the domain's whois information.
     * @return bool True on success; False otherwise.
     */
    public function registerDomain($domain, $nameservers = [], $whois = null)
    {
        $ownerHandle = "";

        if (isset($whois->ownerRegistrarHandles[$this->ClassName])) {
            $ownerHandle = $whois->ownerRegistrarHandles[$this->ClassName];
        } elseif ($whois->ownerSurName != "") {
            $ownerHandle = $this->getContactHandle($whois, HANDLE_OWNER);

            if ($ownerHandle == "") {
                if (!$ownerHandle = $this->createContact($whois, HANDLE_OWNER)) {
                    return false;
                }
            }

            $this->registrarHandles['owner'] = $ownerHandle;
        } else {
            $this->Error[] = sprintf("Er zijn geen houdergegevens opgegeven voor domeinnaam '%s'.", $domain);
            return false;
        }

        /**
         * COPY HANDLE TO WEFACT
         */
        $this->registrarHandles['owner'] = $ownerHandle;

        $this->Period = 1;

        // check
        if (isset($nameservers['ns1']) && !empty($nameservers['ns1']) && $nameservers['ns1'] !== "") {
            $bNs1 = true;
        }
        if (isset($nameservers['ns2']) && !empty($nameservers['ns2']) && $nameservers['ns2'] !== "") {
            $bNs2 = true;
        }
        if (isset($nameservers['ns3']) && !empty($nameservers['ns3']) && $nameservers['ns3'] !== "") {
            $bNs3 = true;
        }

        $nameservers = [];
        if (isset($bNs1))
            $nameservers[] = $nameservers['ns1'];
        if (isset($bNs2))
            $nameservers[] = $nameservers['ns2'];
        if (isset($bNs3))
            $nameservers[] = $nameservers['ns3'];

        $response = $this->request('POST', '/domains/' . $domain, [
            'years' => 1,
            'contact_id' => $ownerHandle,
            'ns' => $nameservers
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $requesttype
     * @param $request
     * @param array $data
     * @return array|mixed
     */
    public function request($requesttype, $request, $data = [])
    {
        require("version.php");

        if ($this->Testmode == '1') {
            $this->endpoint = 'https://www.versio.nl/testapi/v1';
        } else {
            $this->endpoint = 'https://www.versio.nl/api/v1';
        }

        $url = $this->endpoint . $request;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERPWD, $this->User . ":" . $this->Password);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requesttype);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $codes = ['200', '201', '202', '400', '401', '404'];

        $result = json_decode($result, 1);
        $result['httpcode'] = $httpcode;

        if (in_array($httpcode, $codes)) {
            return $result;
        } else {
            $error = [];
            $error['error']['message'] = 'Request failed';
            return $error;
        }
    }

    /**
     * Update the DNS zone of a domain at the registrar
     *
     * @param string $domain The domain
     * @param array $dns_zone Array with DNS zone info and DNS records
     * @return bool
     */
    public function saveDNSZone($domain, $dns_zone)
    {
        $dns = [];

        foreach ($dns_zone['records'] as $records) {
            if ($records['name'] == null) {
                $name = $domain . '.';
            } else {
                $name = $records['name'] . '.' . $domain . '.';
            }

            $dns[] = ['type' => $records['type'], 'name' => $name, 'value' => $records['value'], 'prio' => $records['priority'], 'ttl' => $records['ttl']];
        }

        $response = $this->request('POST', '/domains/' . $domain . '/update', [
            'dns_records' => $dns
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * Change the autorenew state of the given domain. When autorenew is enabled, the domain will be extended.
     *
     * @param string $domain The domainname to change the autorenew setting for,
     * @param bool $autorenew The new autorenew setting (True = On|False = Off)
     * @return bool True when the setting is succesfully changed; False otherwise
     */
    public function setDomainAutoRenew($domain, $autorenew = true)
    {
        $response = $this->request('POST', '/domains/' . $domain . '/update', [
            'auto_renew' => $autorenew
        ]);

        if (isset($response['error'])) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * Download the CSR of a SSL certificate from the registrar
     *
     * @param string $ssl_order_id Order id from a SSL certificate
     * @return bool
     */
    public function ssl_download_ssl_certificate($ssl_order_id)
    {
        $response = $this->request('GET', '/sslcertificates/' . $ssl_order_id);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return $response['SSLcertificateInfo']['certificate'];
        }
    }

    /**
     * Retrieve the approver emailadresses for a SSL certificate from the registrar
     *
     * @param string $domain Domain name
     * @param string $templatename ID of the product as retrieved by ssl_list_products()
     * @return array|bool
     */
    public function ssl_get_approver_list($domain, $templatename)
    {
        $response = $this->request('GET', '/sslapprovers/' . $domain);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $approverEmails = [];

            foreach ($response['approverList'] as $approver) {
                $approverEmails[] = $approver;
            }

            return $approverEmails;
        }
    }

    /**
     * Get SSL product from registrar
     *
     * @param string $templatename ID of the product as retrieved by ssl_list_products()
     * @return array
     */
    public function ssl_get_product($templatename)
    {
        foreach ($this->ssl_list_products() as $sslProductList) {
            if ($sslProductList['templatename'] == $templatename) {
                $sslProduct = $sslProductList;
            }
        }

        $product_info = [];

        $product_info['name'] = $sslProduct['name'];
        $product_info['brand'] = $sslProduct['brand'];
        $product_info['templatename'] = $sslProduct['templatename'];
        $product_info['type'] = $sslProduct['type']; // domain, extended or organization

        $product_info['wildcard'] = ($sslProduct['wildcard'] != NULL) ? TRUE : FALSE;
        $product_info['multidomain'] = ($sslProduct['multidomain_max'] != NULL) ? TRUE : FALSE;
        // $product_info['multidomain_included']	= ($sslProduct['domains']) ? $sslProduct['domains'] : 0;

        if ($product_info['multidomain_max'])
            $product_info['multidomain_max'] = 99;

        // Pricing-periods
        $product_info['periods'] = [];

        if (isset($sslProduct['pricing'])) {
            foreach ($sslProduct['pricing']['period'] as $index => $period_fee) {
                // please note: periods should be in years, not months
                $product_info['periods'][] = ['periods' => $index, 'price' => $period_fee];
            }
        }

        return $product_info;
    }

    /**
     * Retrieve the status of a SSL certificate from the registrar
     *
     * @param string $ssl_order_id Order id from a SSL certificate
     * @return array|bool
     */
    public function ssl_get_request_status($ssl_order_id)
    {
        $response = $this->request('GET', '/sslcertificates/' . $ssl_order_id);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $order_info = [];

            switch ($response['SSLcertificateInfo']['status']) {
                case 'PENDING_VALIDATION':
                    $order_info['status'] = 'inrequest';
                    break;
                case 'PENDING':
                    $order_info['status'] = 'inrequest';
                    break;
                case 'ISSUED':
                    $order_info['status'] = 'install';
                    break;
            }

            return $order_info;
        }
    }

    /**
     * Retrieve all SSL products from registrar
     *
     * @param string $ssl_type Optional filter for SSL validation type
     * @return array|bool
     */
    public function ssl_list_products($ssl_type = '')
    {
        $products_array = [];

        $response = $this->request('GET', '/sslproducts');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            foreach ($response['sslproductsList'] as $sslproducts) {
                $product['name'] = $sslproducts['supplier'] . ' (' . $sslproducts['type'] . ')';
                $product['brand'] = $sslproducts['supplier'];
                $product['templatename'] = $sslproducts['id'];
                $product['type'] = $sslproducts['type'];
                $product['wildcard'] = ($sslproducts['type'] == 'wildcard') ? 1 : 0;
                $product['multidomain_max'] = $sslproducts['support_san_names'];
                $product['max_years'] = $sslproducts['max_years'];
                $product['pricing']['period'] = [
                    1 => $sslproducts['prices']['1_year'],
                    2 => $sslproducts['prices']['2_year'],
                    3 => $sslproducts['prices']['3_year']
                ];

                array_push($products_array, $product);
            }

            return $products_array;
        }
    }

    /**
     * Reissue a SSL certificate at the registrar
     *
     * @param string $ssl_order_id Order id from a SSL certificate
     * @param array $ssl_info Array with info regarding the SSL certificate
     * @param object $whois Object with WHOIS data
     * @return bool
     */
    public function ssl_reissue_certificate($ssl_order_id, $ssl_info, $whois)
    {
        $response = $this->request('POST', '/sslcertificates/' . $ssl_order_id . '/reissue', [
            "csr" => $ssl_info['csr'],
            "approver_email" => $ssl_info['approver_email'],
            "address" => $whois->ownerAddress,
            "postalcode" => $whois->ownerZipCode
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * Renew the SSL certificate at the registrar
     *
     * @param array $ssl_info Array with info regarding the SSL certificate
     * @param object $whois Object with WHOIS data
     * @return bool|mixed
     */
    public function ssl_renew_certificate($ssl_info, $whois)
    {
        return $this->ssl_request_certificate($ssl_info, $whois);
    }

    /**
     * Request the SSL certificate at the registrar
     *
     * @param array $ssl_info Array with info regarding the SSL certificate
     * @param object $whois Object with WHOIS data
     * @return bool|mixed
     */
    public function ssl_request_certificate($ssl_info, $whois)
    {
        $response = $this->request('POST', '/sslcertificates', [
            "csr" => $ssl_info['csr'],
            "approver_email" => $ssl_info['approver_email'],
            "product_id" => $ssl_info['templatename'],
            "years" => $ssl_info['period'],
            "contactperson" => $whois->ownerInitials . ' ' . $whois->ownerSurName,
            "contactperson_email" => $whois->ownerEmailAddress,
            "contactperson_phone" => $whois->ownerPhoneNumber,
            "address" => $whois->ownerAddress,
            "postalcode" => $whois->ownerZipCode,
            "auto_renew" => true,
            "san_names" => $ssl_info['multidomain_records']
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return $response['id'];
        }
    }

    /**
     * Resend the approver email
     *
     * @param string $ssl_order_id Order id from a SSL certificate
     * @param string $approver_emailaddress Approver emailaddress
     * @return bool
     */
    public function ssl_resend_approver_email($ssl_order_id, $approver_emailaddress)
    {
        $response = $this->request('POST', '/sslcertificates/' . $ssl_order_id . '/changeapprover', [
            "approver_email" => $approver_emailaddress
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * Revoke SSL certificate at the registrar
     *
     * @param string $ssl_order_id Order id from a SSL certificate
     * @return bool
     */
    public function ssl_revoke_ssl_certificate($ssl_order_id)
    {
        $response = $this->request('POST', '/sslcertificates/' . $ssl_order_id . '/cancel');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return (bool) ($response['status'] == 'CANCELLED');
        }
    }

    /**
     * Transfer a domain to the given user.
     *
     * @param string $domain The demainname that needs to be transfered.
     * @param array $nameservers The nameservers for the tranfered domain.
     * @param array $whois The contact information for the new owner, admin, tech and billing contact.
     * @param string $authcode
     * @return bool True on success; False otherwise;
     */
    public function transferDomain($domain, $nameservers = [], $whois = null, $authcode = "")
    {
        $ownerHandle = "";

        if (isset($whois->ownerRegistrarHandles[$this->ClassName])) {
            $ownerHandle = $whois->ownerRegistrarHandles[$this->ClassName];
        } elseif ($whois->ownerSurName != "") {
            $ownerHandle = $this->getContactHandle($whois, HANDLE_OWNER);
            if ($ownerHandle == "") {
                if (!$ownerHandle = $this->createContact($whois, HANDLE_OWNER)) {
                    return false;
                }
            }
            $this->registrarHandles['owner'] = $ownerHandle;
        } else {
            $this->Error[] = sprintf("Er zijn geen houdergegevens opgegeven voor domeinnaam '%s'.", $domain);
            return false;
        }

        /**
         * COPY HANDLE TO WEFACT
         */
        $this->registrarHandles['owner'] = $ownerHandle;

        $this->Period = 1;

        if (isset($nameservers['ns1']) && !empty($nameservers['ns1']) && $nameservers['ns1'] !== "") {
            $bNs1 = true;
        }

        if (isset($nameservers['ns2']) && !empty($nameservers['ns2']) && $nameservers['ns2'] !== "") {
            $bNs2 = true;
        }

        if (isset($nameservers['ns3']) && !empty($nameservers['ns3']) && $nameservers['ns3'] !== "") {
            $bNs3 = true;
        }

        $nameservers = [];
        if (isset($bNs1))
            $nameservers[] = $nameservers['ns1'];
        if (isset($bNs2))
            $nameservers[] = $nameservers['ns2'];
        if (isset($bNs3))
            $nameservers[] = $nameservers['ns3'];

        $response = $this->request('POST', '/domains/' . $domain . '/transfer', [
            'years' => 1,
            'contact_id' => $ownerHandle,
            'auth_code' => $authcode,
            'ns' => $nameservers
        ]);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * Update the whois information for the given contact person.
     *
     * @param string $handle The handle of the contact to be changed.
     * @param array $whois The new whois information for the given contact.
     * @param mixed $type The of contact. This is used to access the right fields in the whois array
     * @return bool
     */
    public function updateContact($handle, $whois, $type = HANDLE_OWNER)
    {
        $this->Error[] = sprintf("Versio: Het bewerken van een contact is niet mogelijk.");
        return false;
    }

    /**
     * Update the domain Whois data, but only if no handles are used by the registrar.
     *
     * @param string $domain
     * @param mixed $whois
     * @return bool True if successfully, false otherwise
     */
    public function updateDomainWhois($domain, $whois)
    {
        $aDomain = explode('.', $domain);

        if ($aDomain[1] == 'be') {
            $this->Error[] = 'Voor een .be domeinnaam moet de EPP code eerst ingevuld zijn. Deze functie wordt momenteel nog niet ondersteund.';
            return false;
        } else {
            $contactId = $this->createContact($whois);

            $response = $this->request('POST', '/domains/' . $domain . '/update', [
                'registrant_id' => $contactId
            ]);

            if ($response['error']) {
                $this->Error[] = $response['error']['message'];
                return false;
            } else {
                $this->registrarHandles['owner'] = $response['registrant_id'];
                return true;
            }
        }
    }

    /**
     * Update the nameservers for the given domain.
     *
     * @param string $domain The domain to be changed.
     * @param array $nameservers The new set of nameservers.
     * @return bool True if the update was succesfull; False otherwise;
     */
    public function updateNameServers($domain, $nameservers = [])
    {
        $ns = [];
        if (!$nameservers['ns1'] == null) {
            $ns[] = ['ns' => $nameservers['ns1'], 'nsip' => ''];
        }

        if (!$nameservers['ns2'] == null) {
            $ns[] = ['ns' => $nameservers['ns2'], 'nsip' => ''];
        }

        if (!$nameservers['ns3'] == null) {
            $ns[] = ['ns' => $nameservers['ns3'], 'nsip' => ''];
        }

        if ($nameservers['ns1'] == 'nszero1.axc.nl' || $nameservers['ns2'] == 'nszero2.axc.nl') {
            $data = [
                'dns_management' => true,
                'ns' => $ns
            ];
        } else {
            $data = [
                'dns_management' => false,
                'ns' => $ns
            ];
        }

        $response = $this->request('POST', '/domains/' . $domain . '/update', $data);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }
}