# 友盟接口调用工具

#### 一键登录  <p><a href="https://developer.umeng.com/docs/143070/detail/144783">友盟官网文档</a></p>
```php
use ZacharyUtils\Umeng\Client as UMengClient;

$uMengAppKey = '******';
$client = new UMengClient('阿里appkey', '阿里appsecret', 'https://verify5.market.alicloudapi.com');

$result = $client->getMobile($uMengAppKey, 'App端传入token');

var_dump($result);

```