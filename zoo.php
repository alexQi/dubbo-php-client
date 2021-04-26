<?php
/**
 * Created by PhpStorm.
 * User: kali
 * Date: 2019/4/19
 * Time: 下午3:40
 */

class ZookeeperDemo
{
    private $zookeeper;
    function __construct($address)
    {
        $this->zookeeper = new Zookeeper($address);
    }
    /*
    * get
    */
    public function get($path)
    {
        if (!$this->zookeeper->exists($path)) {
            return null;
        }
        return $this->zookeeper->get($path);
    }

    public function getChildren($path) {
        if (strlen($path) > 1 && preg_match('@/$@', $path)) {
            // remove trailing /
            $path = substr($path, 0, -1);
        }
        return $this->zookeeper->getChildren($path);
    }
    /*
    * set 值
    *
    *
    */
    public function set($path, $value)
    {
        if (!$this->zookeeper->exists($path)) {
            //创建节点
            $this->makePath($path);
        } else {
            $this->zookeeper->set($path,$value);
        }

    }
    /*
    * 创建路径
    */
    private function makePath($path, $value='')
    {
        $parts = explode('/', $path);
        $parts = array_filter($parts);//过滤空值
        $subPath = '';
        while (count($parts) > 1) {
            $subPath .= '/' . array_shift($parts);//数组第一个元素弹出数组
            if (!$this->zookeeper->exists($subpath)) {
                $this->makeNode($subPath, $value);
            }
        }
    }
    /*
    * 创建节点
    *
    */
    private function makeNode($path, $value, array $params = array())
    {
        if (empty($params)) {
            $params = [
                [
                    'perms' => Zookeeper::PERM_ALL,
                    'scheme' => 'world',
                    'id' => 'anyone'
                ]
            ];
        }
        return $this->zookeeper->create($path, $value, $params);
    }
    /*
    * 删除
    **/
    public function deleteNode($path)
    {
        if (!$this->zookeeper->exists($path)) {
            return null;
        } else {
            return $this->zookeeper->delete($path);
        }
    }

}


$zk = new ZookeeperDemo('172.31.214.198:2181');
//$zk = new ZookeeperDemo('192.168.62.31:2181');

//var_dump($zk->get('/'));
//var_dump($zk->getChildren('/dubbo'));
//var_dump($zk->getChildren('/dubbo/com.tuner.basecenter.service.newservice.CenterUserService/providers'));
$p = $zk->getChildren('/dubbo/com.tuner.jdmob.service.JdDataPackageService/providers');
print_r($p);
//echo urldecode($p[0]);
//$p = $zk->getChildren('/dubbo/com.tuner.basecenter.service.newservice.CenterUserService/consumers');
//echo urldecode($p[0]);
//$zc = new Zookeeper();
//$zc->connect('localhost:2181');
//var_dump($zc->get('/zookeeper'));