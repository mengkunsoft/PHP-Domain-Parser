# PHP 域名解析

使用 PHP 从链接中解析出公共域名后缀（Public Suffix） / 顶级域名（Registrable Domain），自动纠错，超强匹配！基于 Public Suffix List

| 示例输入                                        | 解析结果            |
| ----------------------------------------------- | ------------------- |
| http://test.abc.tool.mkblog.cn/pathto/1         | mkblog.cn           |
| 1.doc.baidu.net.cn                              | baidu.net.cn        |
| 大声说.我爱你.中国                              | 我爱你.中国         |
| https://xn--4qsveq93n.xn--6qq986b3xl.xn--fiqs8s | 我爱你.中国         |
| http://sub.example.co.uk/path/to/resource       | example.co.uk       |
| test.www.abc.ck                                 | www.abc.ck          |
| test.www.www.ck                                 | www.ck              |
| test.www.abc.er                                 | www.abc.er          |
| ftp://test.www.abc.kawasaki.jp                  | www.abc.kawasaki.jp |
| www.city.kawasaki.jp                            | city.kawasaki.jp    |
| www.PreF.OkiNawA.jP                             | pref.okinawa.jp     |

更多测试用例见 [test.php](test.php)



## 使用方法

```php
// 引入模块
include_once('DomainParser.class.php');

// 初始化
$parser = new DomainParser();

// 更新公共域名后缀数据库（可选，一般每周更新一次就够了，无需每次更新）
$parser->update();

// 解析链接
$url = 'http://test.abc.tool.mkblog.cn/pathto/1';
$result = $parser->parse($url);

// 获取结果
if ($result['code'] == 200) {
    echo $result['icann'];
}
```

#### 解析结果

| 字段    | 类型   | 说明                                                        |
| ------- | ------ | ----------------------------------------------------------- |
| code    | int    | 200 - 解析成功 / 其他数值 - 解析失败                        |
| msg     | string | 如果解析失败，返回错误消息                                  |
| host    | string | 返回解析出的 host 部分                                      |
| icann   | string | 返回基于 Public Suffix List 中 ICANN 规则解析出的顶级域名   |
| private | string | 返回基于 Public Suffix List 中 PRIVATE 规则解析出的顶级域名 |



## 参考资料

- Public Suffix List https://publicsuffix.org/
- PHP Domain Parser https://github.com/jeremykendall/php-domain-parser
- 域名小知识：Public Suffix List https://imququ.com/post/domain-public-suffix-list.html