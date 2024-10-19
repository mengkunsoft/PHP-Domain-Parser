<?php

/**
 * 域名解析
 * 从链接中解析出正确的域名
 * 
 * @version 1.0 @ 2024-10-19
 * @copyright (c) mengkun
 * @see https://mkblog.cn
 */

class DomainParser
{
    private $url = 'https://publicsuffix.org/list/public_suffix_list.dat';
    private $dbFile = __DIR__ . '/DomainParser.db';
    private $dbJsonFile = __DIR__ . '/DomainParser.json';
    private $dbDatFile = __DIR__ . '/public_suffix_list.dat';

    // 解析域名
    public function parse($url)
    {
        $result = [
            'code' => 200,
            'msg' => '解析成功',
            'host' => '',
            'icann' => '',
            'private' => ''
        ];
        $host = $this->getHost($url);
        if (!$host) {
            $result['code'] = 501;
            $result['msg'] = '无法解析域名 host';
            return $result;
        }
        $result['host'] = $host;

        // 准备数据库
        if (!file_exists($this->dbFile)) {
            $result['code'] = 502;
            $result['msg'] = '数据库文件不存在';
            return $result;
        }
        $contents = file_get_contents($this->dbFile);
        $datas = unserialize($contents);

        $icann = isset($datas['icann']) ? $datas['icann'] : [];
        $private = isset($datas['private']) ? $datas['private'] : [];

        $result['icann'] = $this->getBaseDomain($host, $icann);
        if (!$result['icann']) {
            $result['code'] = 503;
            $result['msg'] = '域名不正确或不完整';
            return $result;
        }
        
        $result['private'] = $this->getBaseDomain($host, $private);
        // 没匹配上私有的规则，纠错
        if (strlen($result['private']) < strlen($result['icann'])) {
            $result['private'] = $result['icann'];
        }
        
        return $result;
    }

    // 获取域名主机部分
    public function getHost($url)
    {
        $url = strtolower($url);    // 统一最小化
        $url = str_replace('。', '.', $url); // 中文的点转换
        $url = str_replace(' ', '', $url); // 去除空格
        $url = preg_replace('/^[\w:]*\/\//i', '', $url); // 去除乱七八糟的协议头

        if (!$url) {
            return '';
        }

        $url = 'https://' . $url; // 加上协议头，再解析域名
        $parseUrl = parse_url($url);

        // 无法解析的域名
        if (!isset($parseUrl['host']) || !$parseUrl['host']) {
            return '';
        }

        $host = $parseUrl['host'];
        return $host;
    }

    // 从 host 中提取出域名
    private function getBaseDomain($host, $datas)
    {
        $pureDomain = [];
        $levelItems = $datas;
        $full = false; // 是否得到了完成的域名
        $splits = explode('.', $host);
        $splits = array_reverse($splits);   // 倒序遍历查找
        foreach ($splits as $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }

            // 还有子层级
            if (isset($levelItems[$item])) {
                $pureDomain[] = $item;
                $levelItems = $levelItems[$item];
            } else {
                // 含 *，需要往下补充一层
                if (isset($levelItems['*'])) {
                    $pureDomain[] = $item;
                    $levelItems = [];
                    continue;
                }

                // 补全域名
                if (isset($levelItems['!'])) {
                    // 本身已经是顶级域名
                } else {
                    $pureDomain[] = $item;
                }
                $full = true;
                break;
            }
        }

        if (!$full || (count($pureDomain) < 2)) {
            return '';  // 没有得到完整的域名
        }

        $pureDomain = array_reverse($pureDomain);
        $domain = implode('.', $pureDomain);

        return $domain;
    }

    // 更新数据库
    public function update()
    {
        // 下载数据库
        $contents = file_get_contents($this->url);
        if (empty($contents)) {
            return false;
        }

        // 逐行解析文件
        $flag = '';
        $datas = array();
        $lines = explode("\n", $contents);
        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('#^$#', $line)) {
                continue;
            }

            if (preg_match('#BEGIN ICANN DOMAINS#i', $line)) {
                $flag = 'icann';
            }

            if (preg_match('#BEGIN PRIVATE DOMAINS#i', $line)) {
                $flag = 'private';
            }

            if (preg_match('#^//#', $line)) {
                continue;
            }

            $parts = explode('.', $line);
            $this->praseData($datas[$flag], $parts);
        }

        // 保存解析后的数据文件
        file_put_contents($this->dbFile, serialize($datas));

        // 可选：保存解析后的数据文件到 JSON
        if ($this->dbJsonFile) {
            $json = json_encode($datas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            file_put_contents($this->dbJsonFile, $json);
        }

        // 可选：保存原始数据文件
        if ($this->dbDatFile) {
            file_put_contents($this->dbDatFile, $contents);
        }
    }

    // 解析数据
    private function praseData(&$node, $tldParts)
    {
        $dom = trim(array_pop($tldParts));

        $isNotDomain = false;
        if ($dom[0] == '!') {
            $dom = substr($dom, 1);
            $isNotDomain = true;
        }

        if (!is_array($node) || !array_key_exists($dom, $node)) {
            if ($isNotDomain) {
                $node[$dom] = array("!" => array());
            } else {
                $node[$dom] = array();
            }
        }

        if (!$isNotDomain && count($tldParts) > 0) {
            $this->praseData($node[$dom], $tldParts);
        }
    }
}