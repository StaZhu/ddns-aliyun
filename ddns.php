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
    public $line = "default";       //解析线路(isp)：【默认：default | 境外：oversea】

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
    
    public function setDomainNameLine($line) {
        $this->line = $line;
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
            'Line' => $this->line,
            'Value' => $this->ip, 
            'Version' => '2015-01-09'
        ];

        return $this->doRequest($queries);
    }

    public function doRequest($queries) {
        ksort($queries);
        $canonicalQueryString = '';
        $i                    = 0;

        foreach ($queries as $param => $query) {
            $canonicalQueryString .= $i === 0 ? null : '&';
            $canonicalQueryString .= $this->percentEncode($param) . "=" . $this->percentEncode($query);
            $i++;
        }

        $signature  = $this->getSignature($canonicalQueryString);
        $requestUrl = "http://alidns.aliyuncs.com/?{$canonicalQueryString}&Signature=" . urlencode($signature);			//如果服务器在非大陆地区不推荐用dns.aliyuncs.com，因为延时比较高，有的时候导致设置不成功，经过与阿里云沟通，他们提供了新地址。
        $response   = file_get_contents($requestUrl, false, stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]));

        return json_decode($response, true);
    }

    public function GetRecordValue() {
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
            if ($record['Type'] === $this->type && $record['Line'] === $this->line && $record['RR'] === $this->prefix) {
                $prefix = $record;
            }
        }

        if ($prefix === null) {
            die('prefix ' . $this->prefix . ' not found.');
        }
		
        //print_r($prefix);

        return $prefix['Value'];
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
            if ($record['Type'] === $this->type && $record['Line'] === $this->line && $record['RR'] === $this->prefix) {
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
        return date('Y-m-d\TH:i:s\Z');
    }
    
    public function percentEncode($str) {
        //使用urlencode编码后，将"+","*","%7E"做替换即满足ECS API规定的编码规范
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }

    public function getSignature($CanonicalQueryString) {
        $HTTPMethod                  = 'GET';
        $slash                       = urlencode('/');
        $EncodedCanonicalQueryString = urlencode($CanonicalQueryString);
        $StringToSign                = "{$HTTPMethod}&{$slash}&{$EncodedCanonicalQueryString}";
        //$StringToSign                = str_replace('%40', '%2540', $StringToSign);
        //$StringToSign                = str_replace('%3A', '%253A', $StringToSign);
        $HMAC                        = hash_hmac('sha1', $StringToSign, "{$this->accessKeySecret}&", true);

        return base64_encode($HMAC);
    }
}
?>
