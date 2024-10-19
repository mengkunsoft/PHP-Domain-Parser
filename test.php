<?php

// 模块测试

include_once('DomainParser.class.php');
include_once('IDN.class.php');

$parser = new DomainParser();
// $parser->update();

// 测试链接 -> 正确的域名
$tests = [
    ['http://test.abc.tool.mkblog.cn/pathto/1', 'mkblog.cn'],
    ['1.doc.baidu.net.cn', 'baidu.net.cn'],
    ['大声说.我爱你.中国', '我爱你.中国'],
    ['https://xn--4qsveq93n.xn--6qq986b3xl.xn--fiqs8s', '我爱你.中国'],
    ['http://sub.example.co.uk/path/to/resource', 'example.co.uk'],
    ['test.www.abc.ck', 'www.abc.ck'], // *.ck
    ['test.www.www.ck', 'www.ck'], // !www.ck
    ['test.www.abc.er', 'www.abc.er'], // *.er
    ['ftp://test.www.abc.kawasaki.jp', 'www.abc.kawasaki.jp'], // *.kawasaki.jp
    ['www.city.kawasaki.jp', 'city.kawasaki.jp'], // !city.kawasaki.jp
    ['www.PreF.OkiNawA.jP', 'pref.okinawa.jp'], // okinawa.jp
    ['.net.cn', ''],    // 非完整域名
    ['net.cn', ''],    // 非完整域名
    ['www.test.null', ''],    // 非域名
];

?>
<!DOCTYPE html>
<html>
<head>
<title>PHP 域名解析</title>
<style>
html {
    font-size: 14px;
}
table {
    border-collapse: collapse;
    border-spacing: 0;
}
table th, table td {
    border: .1em solid #f2f2f2;
    padding: .6em .8em;
}
table th {
    color: #909399;
    background-color: #f9f9f9;
    font-weight: normal;
}
table tr:hover {
    background-color: #fcfcfc;
}
table tr.nok {
    background-color: #fde2e2!important;
}

pre {
    font-family: microsoft yahei;
    background: #f2f4f5;
    padding: 12px;
    font-size: 14px;
    line-height: 1.6em;
    color: #000;
    white-space: pre-wrap;
    border-radius: 4px;
}
</style>
</head>
<body>

<h2>测试用例</h2>

<table>
    <tr><th>Original</th><th>Nominal</th><th>Actual</th><th>Check</th></tr>
    <?php
    foreach ($tests as $item) {
        $url = $item[0];
        $nominal = $item[1];
        // 转码经过 punycode 编码的域名
        // $utf8Url = idn_to_utf8($url);
        $utf8Url = IDN::decodeIDN($url);
        $result = $parser->parse($utf8Url);
        $actual = $result['icann'];
        $ok = $nominal == $actual;
        echo '<tr class="' . ($ok? 'ok': 'nok') . '">
            <td>' . $url . '</td>
            <td>' . $nominal . '</td>
            <td>' . $actual . '</td>
            <td>' . ($ok? '√': '×') . '</td>
        </tr>';
    }
    ?>
<table>

<h2>完整返回示例</h2>

<pre>
<?php 
$url = $tests[0][0];
$result = $parser->parse($url);
echo htmlspecialchars(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
?>
</pre>

</body>
</html>