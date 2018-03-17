###简介

ddns-aliyun 是一个基于php，阿里云api的动态域名服务程序，只适用于macOS下，PPPoE拨号获取到的局域网ip映射

### 如何使用

环境要求：ddns-aliyun 需要php环境,可使用mac 默认自带php7.1.3,修改配置文件后方使用，也可使用homebrew 安装最新版php

### **下载**

```git clone ```

### **修改参数**

请自行设置AccessKeyID，AccessKeySecret，域名，前缀，ttl 五个参数的值

### **测试**

```
cd ddns-aliyun
php loader.php
```

### **设置计划任务**

```
crontab -e
```

请按照  `*/10 * * * * /usr/local/bin/php /path/to/your/loader.php &> /dev/null` 格式，设定

开头部分  `*/10`代表每十分钟执行一次脚本，如果你的ttl设置为600，请保持当前参数，如果ttl设置为120，则可改为 `*/2` ，以此类推

## 许可证

 GPL General Public License 3.0 see <http://www.gnu.org/licenses/gpl-3.0.html>