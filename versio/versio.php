<?php

require_once("3rdparty/domain/IRegistrar.php");
require_once("3rdparty/domain/standardfunctions.php");

class Versio implements IRegistrar
{
    public $class = 'versio';
    public $User;
    public $Password;

    public $Error;
    public $Warning;
    public $Success;

    public $Period = 1;
    public $registrarHandles = array();

    public $values = "";

    private $ClassName;

    function __construct()
    {
        $this->ClassName = __CLASS__;

        $this->Error = array();
        $this->Warning = array();
        $this->Success = array();
    }

    /**
     * @param $domain
     * @return bool
     */
    function checkDomain($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain . '/availability');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        }

        if ($response['available'] == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $whois
     * @param $type
     * @return bool|mixed
     */
    function createContact($whois, $type = HANDLE_OWNER)
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
        $contactDetails = array();
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

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return $response['contact_id'];
        }
    }

    /**
     * @param $handle
     * @return bool
     */
    function deleteContact($handle)
    {
        $response = $this->request('DELETE', '/contacts/' . $handle);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $domain
     * @param string $delType
     * @return bool
     */
    function deleteDomain($domain, $delType = 'end')
    {
        return $this->setDomainAutoRenew($domain, false);
    }

    /**
     * @param $domain
     * @param $pendingInfo
     * @return bool|string
     */
    function doPending($domain, $pendingInfo)
    {
        $response = $this->request('GET', '/domains/' . $domain);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return 'pending';
        } else {
            switch ($response['domainInfo']['status']) {
                case 'OK':
                    $this->Success[] = "Domeinnaam '" . $domain . "' is succesvol aangevraagd.";
                    return true;
                    break;
                case 'PENDING':
                    return 'pending';
                    break;
                case 'INACTIVE':
                    $this->Error[] = "Domeinnaam '" . $domain . "' aangevraag is mislukt.";
                    return false;
                    break;
                case 'PENDING_TRANSFER':
                    return 'pending';
                    break;
                default:
                    return 'pending';
            }
        }
    }

    /**
     * @param $domain
     * @param $nyears
     * @return bool
     */
    function extendDomain($domain, $nyears)
    {
        $data = array('years' => $nyears);

        $response = $this->request('POST', '/domains/' . $domain, $data);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $handle
     * @return bool|whois
     */
    function getContact($handle)
    {
        $response = $this->request('GET', '/contacts/' . $handle);

        if ($response['error']) {
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
     * @param array $whois
     * @param $type
     * @return bool
     */
    function getContactHandle($whois = array(), $type = HANDLE_OWNER)
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

        unset($handle);
        unset($reponse);

        $response = $this->request('GET', '/contacts' . $whois->ownerRegistrarHandles['versio']);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param string $surname
     * @return array|bool
     */
    function getContactList($surname = "")
    {
        $response = $this->request('GET', '/contacts');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $contactsList = array();

            foreach ($response['ContactList'] as $contactlist) {
                $contact = array();
                $contact['Handle'] = $contactlist['contact_id'];
                $contact['Sex'] = ''; // Not provided in Versio API
                $contact['Initials'] = $contactlist['firstname'];
                $contact['SurName'] = $contactlist['surname'];
                $contact['CompanyName'] = $contactlist['company'];

                $contact['Address'] = $contactlist['street'] . ' ' . $contactlist['number'];
                $contact['ZipCode'] = $contactlist['zipcode'];
                $contact['City'] = $contactlist['city'];
                $contact['Country'] = $contactlist['country'];

                $contact['PhoneNumber'] = $contactlist['phone'];
                $contact['EmailAddress'] = $contactlist['email'];

                $contactsList[] = $contact;
            }

            return $contactsList;
        }
    }

    /**
     * @return array|bool
     */
    function getDNSTemplates()
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
     * @param $domain
     * @return array|bool
     */
    function getDNSZone($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain . '?show_dns_records=true');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $record_type = 'records';
            $i = 0;
            $dns_zone = array();

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
     * @param $domain
     * @return array|bool|mixed
     */
    function getDomainInformation($domain)
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

            $response = array(
                'Domain' => $domain,
                'Information' => array(
                    'nameservers' => $response['domainInfo']['ns'],
                    'whois' => $whois,
                    'expiration_date' => $response['domainInfo']['expire-date'],
                    'registration_date' => date("Y-m-d", $lastyear),
                    'authkey' => $this->getToken($domain)
                )
            );

            return $response;
        }
    }

    /**
     * @param string $contactHandle
     * @return array|bool
     */
    function getDomainList($contactHandle = "")
    {
        if ($contactHandle != "") {
            $this->Error[] = 'Het filteren op een contact is momenteel niet mogelijk';
        }

        $response = $this->request('GET', '/domains?status=OK');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        }

        $domainsList = array();

        foreach ($response['DomainsList'] as $domainlist) {
            $nameservers = array();

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

            $domain = array();
            $domain['Domain'] = $domainlist['domain'];
            $domain['Information'] = array(
                'nameservers' => $nameservers,
                'whois' => $whois,
                'expiration_date' => $domainlist['expire-date'],
                'registration_date' => date("Y-m-d", $lastyear)
            );

            array_push($domainsList, $domain);
        }

        return $domainsList;
    }

    /**
     * @param $domain
     * @return array|bool
     */
    function getDomainWhois($domain)
    {
        $response = $this->request('GET', '/domains/' . $domain);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $contacts = array();

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
     * @param $list_domains
     * @return mixed
     */
    function getSyncData($list_domains)
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
            $nameservers = array();

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
     * @param $domain
     * @return bool|string
     */
    function getToken($domain)
    {
        $aDomain = explode('.', $domain);

        $response = $this->request('GET', '/domains/' . $domain . '?show_epp_code=true');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            if ($aDomain[1] == 'be') {
                return 'EPP code will be sent by email';
            } else {
                return $response['domainInfo']['epp_code'];
            }
        }
    }

    /**
     * @return mixed
     */
    static function getVersionInformation()
    {
        require_once("3rdparty/domain/versio/version.php");
        return $version;
    }

    /**
     * @param $domain
     * @param bool $lock
     * @return bool
     */
    function lockDomain($domain, $lock = true)
    {
        $settings = array();
        $settings['lock'] = $lock;

        $response = $this->request('POST', '/domains/' . $domain . '/update', $settings);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
        } else {
            return true;
        }
    }

    /**
     * @param $domain
     * @param array $nameservers
     * @param null $whois
     * @return bool
     */
    function registerDomain($domain, $nameservers = array(), $whois = null)
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

        $nameservers = array();
        if (isset($bNs1))
            $nameservers[] = $nameservers['ns1'];
        if (isset($bNs2))
            $nameservers[] = $nameservers['ns2'];
        if (isset($bNs3))
            $nameservers[] = $nameservers['ns3'];

        $data = array(
            'years' => 1,
            'contact_id' => $ownerHandle,
            'ns' => $nameservers
        );

        $response = $this->request('POST', '/domains/' . $domain, $data);

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
    function request($requesttype, $request, $data = array())
    {
        require("version.php");

        if ($this->Testmode == '1') {
            $this->endpoint = 'https://www.versio' . $version['site_version'] . '/testapi/v1';
        } else {
            $this->endpoint = 'https://www.versio' . $version['site_version'] . '/api/v1';
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

        //$this->setApi_debug(); //debug disabled

        if ($this->debug) {
            $debugdata = array('requesttype' => $requesttype, 'url' => $url, 'postdata' => $data, 'result' => $result, 'httpcode' => $httpcode);
            var_dump($debugdata);
        }

        $codes = array('200', '201', '202', '400', '401', '404');

        $result = json_decode($result, 1);
        $result['httpcode'] = $httpcode;

        if (in_array($httpcode, $codes)) {
            return $result;
        } else {
            $error = array();
            $error['error']['message'] = 'Request failed';
            return $error;
        }
    }

    /**
     * @param $domain
     * @param $dns_zone
     * @return bool
     */
    function saveDNSZone($domain, $dns_zone)
    {
        $dns = array();

        foreach ($dns_zone['records'] as $records) {
            if ($records['name'] == null) {
                $name = $domain . '.';
            } else {
                $name = $records['name'] . '.' . $domain . '.';
            }

            $dns[] = array('type' => $records['type'], 'name' => $name, 'value' => $records['value'], 'prio' => $records['priority'], 'ttl' => $records['ttl']);
        }

        $data = array();
        $data['dns_records'] = $dns;

        $response = $this->request('POST', '/domains/' . $domain . '/update', $data);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    function setApi_debug()
    {
        $this->debug = true;
    }

    function setApi_output($outputresult)
    {
        $this->output = $outputresult;
    }

    /**
     * @param $domain
     * @param bool $autorenew
     * @return bool
     */
    function setDomainAutoRenew($domain, $autorenew = true)
    {
        $settings = array();
        $settings['auto_renew'] = $autorenew;

        $response = $this->request('POST', '/domains/' . $domain . '/update', $settings);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $ssl_order_id
     * @return bool
     */
    function ssl_download_ssl_certificate($ssl_order_id)
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
     * @param $domain
     * @param $templatename
     * @return array|bool
     */
    function ssl_get_approver_list($domain, $templatename)
    {
        $response = $this->request('GET', '/sslapprovers/' . $domain);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $approverEmails = array();

            foreach ($response['approverList'] as $approver) {
                $approverEmails[] = $approver;
            }

            return $approverEmails;
        }
    }

    /**
     * @param $templatename
     * @return array
     */
    function ssl_get_product($templatename)
    {
        foreach ($this->ssl_list_products() as $sslProductList) {
            if ($sslProductList['templatename'] == $templatename) {
                $sslProduct = $sslProductList;
            }
        }

        $product_info = array();

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
        $product_info['periods'] = array();

        if (isset($sslProduct['pricing'])) {
            foreach ($sslProduct['pricing']['period'] as $index => $period_fee) {
                // please note: periods should be in years, not months
                $product_info['periods'][] = array('periods' => $index, 'price' => $period_fee);
            }
        }

        return $product_info;
    }

    /**
     * @param $ssl_order_id
     * @return array|bool
     */
    function ssl_get_request_status($ssl_order_id)
    {
        $response = $this->request('GET', '/sslcertificates/' . $ssl_order_id);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            $order_info = array();

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
     * @param string $ssl_type
     * @return array|bool
     */
    function ssl_list_products($ssl_type = '')
    {
        $products_array = array();

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
                $product['pricing']['period'] = array(
                    1 => $sslproducts['prices']['1_year'],
                    2 => $sslproducts['prices']['2_year'],
                    3 => $sslproducts['prices']['3_year']
                );

                array_push($products_array, $product);
            }

            return $products_array;
        }
    }

    /**
     * @param $ssl_order_id
     * @param $ssl_info
     * @param $whois
     * @return bool
     */
    function ssl_reissue_certificate($ssl_order_id, $ssl_info, $whois)
    {
        $sslDetails = array(
            "csr" => $ssl_info['csr'],
            "approver_email" => $ssl_info['approver_email'],
            "address" => $whois->ownerAddress,
            "postalcode" => $whois->ownerZipCode
        );

        $response = $this->request('POST', '/sslcertificates/' . $ssl_order_id . '/reissue', $sslDetails);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $ssl_info
     * @param $whois
     * @return bool|mixed
     */
    function ssl_renew_certificate($ssl_info, $whois)
    {
        return $this->ssl_request_certificate($ssl_info, $whois);
    }

    /**
     * @param $ssl_info
     * @param $whois
     * @return bool|mixed
     */
    function ssl_request_certificate($ssl_info, $whois)
    {
        $sslDetails = array(
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
        );

        $response = $this->request('POST', '/sslcertificates', $sslDetails);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return $response['id'];
        }
    }

    /**
     * @param $ssl_order_id
     * @param $approver_emailaddress
     * @return bool
     */
    function ssl_resend_approver_email($ssl_order_id, $approver_emailaddress)
    {
        $sslDetails = array("approver_email" => $approver_emailaddress);

        $response = $this->request('POST', '/sslcertificates/' . $ssl_order_id . '/changeapprover', $sslDetails);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $ssl_order_id
     * @return bool
     */
    function ssl_revoke_ssl_certificate($ssl_order_id)
    {
        $response = $this->request('POST', '/sslcertificates/' . $ssl_order_id . '/cancel');

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            if ($response['status'] == 'CANCELLED') {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param $domain
     * @param array $nameservers
     * @param null $whois
     * @param string $authcode
     * @return bool
     */
    function transferDomain($domain, $nameservers = array(), $whois = null, $authcode = "")
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

        $nameservers = array();
        if (isset($bNs1))
            $nameservers[] = $nameservers['ns1'];
        if (isset($bNs2))
            $nameservers[] = $nameservers['ns2'];
        if (isset($bNs3))
            $nameservers[] = $nameservers['ns3'];

        $data = array(
            'years' => 1,
            'contact_id' => $ownerHandle,
            'auth_code' => $authcode,
            'ns' => $nameservers
        );

        $response = $this->request('POST', '/domains/' . $domain . '/transfer', $data);

        if ($response['error']) {
            $this->Error[] = $response['error']['message'];
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $handle
     * @param $whois
     * @param $type
     * @return bool
     */
    function updateContact($handle, $whois, $type = HANDLE_OWNER)
    {
        $this->Error[] = sprintf("Versio: Het bewerken van een contact is niet mogelijk.");
        return false;
    }

    /**
     * @param $domain
     * @param $whois
     * @return bool
     */
    function updateDomainWhois($domain, $whois)
    {
        $aDomain = explode('.', $domain);

        if ($aDomain[1] == 'be') {
            $this->Error[] = 'Voor een .be domeinnaam moet de EPP code eerst ingevuld zijn. Deze functie wordt momenteel nog niet ondersteund.';
            return false;
        } else {
            $contactId = $this->createContact($whois);

            $settings = array();
            $settings['registrant_id'] = $contactId;

            $response = $this->request('POST', '/domains/' . $domain . '/update', $settings);

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
     * @param $domain
     * @param array $nameservers
     * @return bool
     */
    function updateNameServers($domain, $nameservers = array())
    {
        $ns = array();
        if (!$nameservers['ns1'] == null) {
            $ns[] = array('ns' => $nameservers['ns1'], 'nsip' => '');
        }

        if (!$nameservers['ns2'] == null) {
            $ns[] = array('ns' => $nameservers['ns2'], 'nsip' => '');
        }

        if (!$nameservers['ns3'] == null) {
            $ns[] = array('ns' => $nameservers['ns3'], 'nsip' => '');
        }

        if ($nameservers['ns1'] == 'nszero1.axc.nl' || $nameservers['ns2'] == 'nszero2.axc.nl') {
            $data = array(
                'dns_management' => true,
                'ns' => $ns
            );
        } else {
            $data = array(
                'dns_management' => false,
                'ns' => $ns
            );
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