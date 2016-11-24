<?php

namespace Acilia\Bundle\MailerBundle\Library\Members;

use Acilia\Bundle\MailerBundle\Library\Exceptions\Members\SmartFocusResponseException;
use Acilia\Bundle\MailerBundle\Library\Members\Interfaces\DefaultMemberInterface;
use Exception;

class SmartFocusMember implements DefaultMemberInterface
{
    private $server = null;
    private $api;

    private $_login;
    private $_pwd;
    private $_key;

    private $_token;
    private $_signedIn = false;

    public function __construct($server)
    {
        $this->server = $server;
        $this->api = 'apimember';
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

    public function setApi($api)
    {
        if ($api == 'batch') {
            $this->api = 'apibatchmember';
        } else {
            $this->api = 'apimember';
        }
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
            ['{server}', '{api}', '{login}', '{password}', '{key}'],
            [$this->server, $this->api, $this->_login, $this->_pwd, $this->_key],
            'https://{server}/{api}/services/rest/connect/open/{login}/{password}/{key}');

        $response = $this->_request($url);
        if ($response) {
            $response = simplexml_load_string($response);
            if ($response and $response['responseStatus'] == 'success') {
                $this->_token = (string)$response->result;
                $this->_signedIn = true;

            } else {
                throw new SmartFocusResponseException(sprintf('[SF-API] Cannot open API connection: %s', (string)$response->description));
            }

        } else {
            throw new SmartFocusResponseException('[SF-API] Cannot open API connection: Malformed XML response received.');
        }
    }

    public function closeConnection()
    {
        if ($this->_signedIn) {

            $url = str_replace(
                ['{server}', '{api}', '{token}'],
                [$this->server, $this->api, $this->_token],
                'https://{server}/{api}/services/rest/connect/close/{token}');

            $response = $this->_request($url);
            if ($response) {
                $response = simplexml_load_string($response);
                if ($response and $response['responseStatus'] == 'success') {
                    $this->_token = null;
                    $this->_signedIn = false;

                } else {
                    throw new SmartFocusResponseException(sprintf('[SF-API] Cannot close API connection: %s', (string)$response->description));
                }

            } else {
                throw new SmartFocusResponseException('[SF-API] Cannot close API connection: Malformed XML response received.');
            }
        }
    }

    public function insertMembers($header, $data)
    {
        $url = str_replace(
            ['{server}', '{api}', '{token}'],
            [$this->server, $this->api, $this->_token],
            'https://{server}/{api}/services/rest/batchmemberservice/{token}/batchmember/insertUpload');

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
                throw new SmartFocusResponseException(sprintf('[SF-API] Cannot mass ingest members: %s', (string)$response->description));
            }

        } else {
            throw new SmartFocusResponseException('[SF-API] Cannot mass ingest members: Malformed XML response received.');
        }
    }

    public function insertSingleMember($email, $data)
    {
        $url = str_replace(
            ['{server}', '{api}', '{token}'],
            [$this->server, $this->api, $this->_token],
            'https://{server}/{api}/services/rest/member/insertMember/{token}');

        // single member payload
        $body = $this->_getSingleData($email, $data);

        // single member post options
        $options = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => ['Content-Type: application/xml;', 'Accept: application/xml']
        ];

        $response = $this->_request($url, 'POST', $body, $options);
        if ($response) {
            $response = simplexml_load_string($response);
            if ($response and $response['responseStatus'] == 'success') {
                return (string)$response->result;

            } else {
                throw new SmartFocusResponseException(sprintf('[SF-API] Cannot ingest single member: %s', (string)$response->description));
            }

        } else {
            throw new SmartFocusResponseException('[SF-API] Cannot ingest single member: Malformed XML response received.');
        }
    }

    public function removeSingleMember($email)
    {
        $url = str_replace(
            ['{server}', '{api}', '{token}', '{email}'],
            [$this->server, $this->api, $this->_token, $email],
            'https://{server}/{api}/services/rest/member/unjoinByEmail/{token}/{email}');

        $response = $this->_request($url);
        if ($response) {
            $response = simplexml_load_string($response);
            if ($response and $response['responseStatus'] == 'success') {
                return (string)$response->result;

            } else {
                throw new SmartFocusResponseException(sprintf('[SF-API] Cannot remove single member: %s', (string)$response->description));
            }

        } else {
            throw new SmartFocusResponseException('[SF-API] Cannot remove single member: Malformed XML response received.');
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

    private function _getSingleData($email, $data)
    {
        $configData = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<synchroMember>
    <dynContent>
        %entries%
    </dynContent>
    <email>%email%</email>
</synchroMember>
EOD;

        $entries = [];
        foreach ($data as $key => $value) {
            $entries[] = sprintf('<entry><key>%s</key><value>%s</value></entry>', $key, $value);
        }

        $configData = str_replace('%entries%', implode(PHP_EOL, $entries), $configData);
        $configData = str_replace('%email%', $email, $configData);

        return $configData;
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
