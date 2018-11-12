<?php

class MyDDNS
{
    public $accessKeyId;
    public $accessKeySecret;
    public $domainName;
    public $ip;
    public $ttl;
    public $prefix;
    public $type;

    function __construct($accessKeyId, $accessKeySecret) {
        $this->accessKeyId     = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
    }

    public function setDomainName($domainName) {
        $this->domainName = $domainName;
    }

    public function setIP($ip) {
        $this->ip = $ip;
    }

    public function setTTL($ttl) {
        $this->ttl = $ttl;
    }

    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }
    
    public function setDomainNameType($type) {
        $this->type = $type;
    }

    public function sendRequest() {
        $queries = [
            'AccessKeyId' => $this->accessKeyId,
            'Action' => 'UpdateDomainRecord',
            'Format' => 'JSON',
            'RR' => $this->prefix,
            'RecordId' => $this->getRecordId(),
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => rand(1000000000, 9999999999),
            'SignatureVersion' => '1.0',
            'TTL' => $this->ttl,
            'Timestamp' => $this->getDate(),
            'Type' => $this->type,
            'Value' => $this->ip, 
            'Version' => '2015-01-09'
        ];

        return $this->doRequest($queries);
    }

    public function doRequest($queries) {
        $canonicalQueryString = '';
        $i                    = 0;

        foreach ($queries as $param => $query) {
            $canonicalQueryString .= $i === 0 ? null : '&';
            $canonicalQueryString .= "$param=$query";
            $i++;
        }

        $signature  = $this->getSignature($canonicalQueryString);
        $requestUrl = "http://dns.aliyuncs.com/?{$canonicalQueryString}&Signature=" . urlencode($signature);
        $response   = file_get_contents($requestUrl, false, stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]));

        return json_decode($response, true);
    }

    public function getRecordId() {
        $queries = [
            'AccessKeyId' => $this->accessKeyId,
            'Action' => 'DescribeDomainRecords',
            'DomainName' => $this->domainName,
            'Format' => 'JSON',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => rand(1000000000, 9999999999),
            'SignatureVersion' => '1.0',
            'Timestamp' => $this->getDate(),
            'Version' => '2015-01-09'
        ];

        $response = $this->doRequest($queries);
        $recordList = $response['DomainRecords']['Record'];
        $prefix = null;

        foreach ($recordList as $key => $record) {
            if ($record['Type'] === $this->type && $this->prefix === $record['RR']) {
                $prefix = $record;
            }
        }

        if ($prefix === null) {
            die('prefix ' . $this->prefix . ' not found.');
        }

        return $prefix['RecordId'];
    }

    public function getDate() {
        date_default_timezone_set('UTC');

        $date      = date('Y-m-d');
        $H         = date('H');
        $i         = date('i');
        $s         = date('s');

        return "{$date}T{$H}%3A{$i}%3A{$s}";
    }

    public function getSignature($CanonicalQueryString) {
        $HTTPMethod                  = 'GET';
        $slash                       = urlencode('/');
        $EncodedCanonicalQueryString = urlencode($CanonicalQueryString);
        $StringToSign                = "{$HTTPMethod}&{$slash}&{$EncodedCanonicalQueryString}";
        $StringToSign                = str_replace('%40', '%2540', $StringToSign);
        $StringToSign                = str_replace('%3A', '%253A', $StringToSign);
        $HMAC                        = hash_hmac('sha1', $StringToSign, "{$this->accessKeySecret}&", true);

        return base64_encode($HMAC);
    }
}
?>
