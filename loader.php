<?php
require_once dirname(__FILE__).'/ddns.php';
use DDNS\MyDDNS;

$accessKeyId     = '你的Access Key ID';         //设置你的阿里云accessKeyId,用于调用api
$accessKeySecret = '你的Access Key Secret';     //设置你的阿里云accessKeySecret,用于调用api
$updater         = new MyDDNS($accessKeyId, $accessKeySecret);

$ip              = shell_exec('/sbin/ifconfig |grep -A 3 ppp|grep inet.*netmask.*|sed \'s|^.*inet \(.*\) -->.*$|\1|g\'|tr -d "\n" ');  //获取pppoe拨号后的内网ip地址，并映射到域名，如需映射其他ip，请自行修改指令
$updater->setIP($ip);                          

$updater->setDomainName('你的域名');            //设置二级域名，例：ddns.stazhu.com 中的 stazhu.com
$updater->setPrefix('你的前缀');                //设置二级域名的前缀，例：ddns.stazhu.com 中的 ddns
$updater->setTTL('600');                       //设置TTL值，取决于你购买的云解析服务，TTL越低，DNS缓存更新越快，越佳，默认请设置为600，即10分钟。

print_r($updater->sendRequest());
echo "\n";
print_r("$updater->prefix.$updater->domainName 已被定向到 $ip");
echo "\n";

?>
