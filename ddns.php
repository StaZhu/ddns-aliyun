<?php
namespace DDNS;

class MyDDNS
{
    public $accessKeyId;
    public $accessKeySecret;
    public $domainName;
    public $ip;
    public $ttl;
    public $prefix;

    function __construct(string $accessKeyId,string $accessKeySecret) {
        $this->accessKeyId     = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
    }

    public function setDomainName(string $domainName) {
        $this->domainName = $domainName;
    }

    public function setIP(string $ip) {
        $this->ip = $ip;
    }

    public function setTTL(string $ttl) {
        $this->ttl = $ttl;
    }

    public function setPrefix(string $prefix) {
        $this->prefix = $prefix;
    }

   

    public function sendRequest(): array {
        $queries = [
            'AccessKeyId' => $this->accessKeyId,
            'Action' => 'UpdateDomainRecord',
            'Format' => 'json',
            'RR' => $this->prefix,
            'RecordId' => $this->getRecordId(),
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => random_int(1000000000, 9999999999),
            'SignatureVersion' => '1.0',
            'TTL' => $this->ttl,
            'Timestamp' => $this->getDate(),
            'Type' => 'A',
            'Value' => $this->ip, 
            'Version' => '2015-01-09'
        ];

        return $this->doRequest($queries);
    }

    public function doRequest(Array $queries): array {
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

    public function getRecordId(): string {
        $queries = [
            'AccessKeyId' => $this->accessKeyId,
            'Action' => 'DescribeDomainRecords',
            'DomainName' => $this->domainName,
            'Format' => 'json',
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => random_int(1000000000, 9999999999),
            'SignatureVersion' => '1.0',
            'Timestamp' => $this->getDate(),
            'Version' => '2015-01-09'
        ];

        $response = $this->doRequest($queries);
        $recordList = $response['DomainRecords']['Record'];
        $prefix = null;

        foreach ($recordList as $key => $record) {
            if ($this->prefix === $record['RR']) {
                $prefix = $record;
            }
        }

        if ($prefix === null) {
            die('prefix ' . $this->prefix . ' not found.');
        }

        return $prefix['RecordId'];
    }

    public function getDate(): string {
        date_default_timezone_set('UTC');

        $timestamp = date('U');
        $date      = date('Y-m-d', $timestamp);
        $H         = date('H', $timestamp);
        $i         = date('i', $timestamp);
        $s         = date('s', $timestamp);

        return "{$date}T{$H}%3A{$i}%3A{$s}";
    }

    public function getSignature(string $CanonicalQueryString): string {
        $HTTPMethod                  = 'GET';
        $slash                       = urlencode('/');
        $EncodedCanonicalQueryString = urlencode($CanonicalQueryString);
        $StringToSign                = "{$HTTPMethod}&{$slash}&{$EncodedCanonicalQueryString}";
        $StringToSign                = str_replace('%40', '%2540', $StringToSign);
        $HMAC                        = hash_hmac('sha1', $StringToSign, "{$this->accessKeySecret}&", true);

        return base64_encode($HMAC);
    }

    

    
}
?>
