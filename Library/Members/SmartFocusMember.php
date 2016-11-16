<?php

namespace Acilia\Bundle\MailerBundle\Library\Members;

use Acilia\Bundle\MailerBundle\Library\Exceptions\Members\SmartFocusResponseException;
use Acilia\Bundle\MailerBundle\Library\Members\Interfaces\DefaultMemberInterface;
use Exception;

class SmartFocusMember implements DefaultMemberInterface
{
    private $server = null;

    private $_login;
    private $_pwd;
    private $_key;

    private $_token;
    private $_signedIn = false;

    public function __construct($server)
    {
        $this->server = $server;
    }

    /**
     * Destruct member (logout if needed)
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * Set name for compiler pass
     * @return string
     */
    public function getName()
    {
        return 'smart_focus_member';
    }

    public function openConnection($login, $pwd, $key)
    {
        if (empty($login) || empty($pwd) || empty($key)) {
            throw new Exception('Bad credentials');
        }

        $this->_login = $login;
        $this->_pwd = $pwd;
        $this->_key = $key;

        $url = str_replace(
            ['{server}', '{login}', '{password}', '{key}'],
            [$this->server, $this->_login, $this->_pwd, $this->_key],
            'https://{server}/apibatchmember/services/rest/connect/open/{login}/{password}/{key}');

        $response = $this->_request($url);
        if ($response) {
            $response = simplexml_load_string($response);
            if ($response and $response['responseStatus'] == 'success') {
                $this->_token = (string)$response->result;
                $this->_signedIn = true;

            } else {
                throw new SmartFocusResponseException(sprintf('Cannot open API connection: %s', (string)$response->description));
            }

        } else {
            throw new SmartFocusResponseException('Cannot open API connection: Malformed XML response received.');
        }
    }

    public function closeConnection()
    {
        if ($this->_signedIn) {

            $url = str_replace(
                ['{server}', '{token}'],
                [$this->server, $this->_token],
                'https://{server}/apibatchmember/services/rest/connect/close/{token}');

            $response = $this->_request($url);
            if ($response) {

                $response = simplexml_load_string($response);
                if ($response and $response['responseStatus'] == 'success') {
                    $this->_token = null;
                    $this->_signedIn = false;

                } else {
                    throw new SmartFocusResponseException(sprintf('Cannot close API connection: %s', (string)$response->description));
                }

            } else {
                throw new SmartFocusResponseException('Cannot close API connection: Malformed XML response received.');
            }
        }
    }

    public function insertMembers($header, $data)
    {
        $url = str_replace(
            ['{server}', '{token}'],
            [$this->server, $this->_token],
            'https://{server}/apibatchmember/services/rest/batchmemberservice/{token}/batchmember/insertUpload');

        $boundary = md5(time());

        // members payload
        $body = '--%boundary%' . PHP_EOL
            . $this->_getConfigData('member.csv', $header) . PHP_EOL
            . '--%boundary%--' . PHP_EOL
            . '--%boundary%' . PHP_EOL
            . $this->_getStreamData('member.csv', base64_encode($data)) . PHP_EOL
            . '--%boundary%--';
        $payload = str_replace('%boundary%', $boundary, $body);

        // member put options
        $options = [
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_HTTPHEADER => ['Content-Type: multipart/form-data;', sprintf('boundary=%s', $boundary)]
        ];

        $response = $this->_request($url, 'PUT', $payload, $options);
        if ($response) {
            $response = simplexml_load_string($response);
            if ($response and $response['responseStatus'] == 'success') {
                return;
            } else {
                throw new SmartFocusResponseException(sprintf('Cannot ingest members into API connection: %s', (string)$response->description));
            }

        } else {
            throw new SmartFocusResponseException('Cannot ingest members into API connection: Malformed XML response received.');
        }
    }

    private function _request($url, $method='GET', $payload = false, $options = array())
    {
        // Init Curl
        $curl = curl_init();

        // Set Options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Is Post
        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }

        // Set Extra Options
        foreach ($options as $optionName => $optionValue) {
            curl_setopt($curl, $optionName, $optionValue);
        }

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    private function _getConfigData($member, $header)
    {
        $configData = <<<EOD
Content-Disposition: form-data; name="insertUpload"
Content-Type: text/xml

<?xml version="1.0" encoding="UTF-8"?>
<insertUpload>
    <fileName>%member_file%</fileName>
    <fileEncoding>UTF-8</fileEncoding>
    <separator>,</separator>
    <dateFormat>dd/mm/yyyy HH24:mi</dateFormat>
    <skipFirstLine>true</skipFirstLine>
    <mapping>
        %mapping%
    </mapping>
</insertUpload>
EOD;

        $mapping = [];
        foreach ($header as $idx => $field) {
            $mapping[] = sprintf('<column><colNum>%s</colNum><fieldName>%s</fieldName></column>', ($idx + 1), $field);
        }

        $configData = str_replace('%member_file%', $member, $configData);
        $configData = str_replace('%mapping%', implode(PHP_EOL, $mapping), $configData);

        return $configData;
    }

    private function _getStreamData($member, $data)
    {
        $streamData = <<<EOD
Content-Disposition: form-data; name="inputStream"
Content-Type: application/octet-stream
Content-Transfer-Encoding: base64

%members%
EOD;
        $streamData = str_replace('%member_file%', $member, $streamData);
        $streamData = str_replace('%members%', $data, $streamData);

        return $streamData;
    }

}
