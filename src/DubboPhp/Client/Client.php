<?php
/**
 * Created by IntelliJ IDEA.
 * User: user
 * Date: 2017/3/8
 * Time: 17:06
 */

namespace DubboPhp\Client;

class Client
{
    const VERSION_DEFAULT  = '0.0.0';
    const PROTOCOL_JSONRPC = 'jsonrpc';
    const PROTOCOL_HESSIAN = 'hessian';
    const PROTOCOL_DUBBO   = 'dubbo';

    /**
     * @var Register
     */
    protected        $register;
    protected static $protocolSupports = [
        self::PROTOCOL_JSONRPC => true,
        self::PROTOCOL_HESSIAN => false,
        self::PROTOCOL_DUBBO   => true,
    ];
    protected static $protocols        = [
    ];

    /**
     * Client constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->register = new Register($options);
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function factory($options = [])
    {
        return new static($options);
    }

    /**
     * @param        $serviceName
     * @param string $protocol
     * @param null   $group
     * @param string $version
     * @param bool   $forceVgp
     *
     * @return Invoker|mixed
     * @throws DubboPhpException
     */
    public function getService(
        $serviceName,
        $forceVgp = false,
        $group = null,
        $protocol = self::PROTOCOL_JSONRPC,
        $version = self::VERSION_DEFAULT
    ) {
        $serviceVersion  = !$forceVgp ? $this->register->getServiceVersion() : $version;
        $serviceGroup    = !$forceVgp ? $this->register->getServiceGroup() : $group;
        $serviceProtocol = !$forceVgp ? $this->register->getServiceProtocol() : $protocol;
        $invokerDesc     = new InvokerDesc($serviceName, $serviceVersion, $serviceGroup, $protocol);
        $invoker         = $this->register->getInvoker($invokerDesc);
        if (!$invoker) {
            $invoker = $this->makeInvokerByProtocol($serviceProtocol);
            $this->register->register($invokerDesc, $invoker);
        }
        return $invoker;
    }

    /**
     * @param $protocol
     *
     * @return Invoker instance of specific protocol
     * @throws \DubboPhp\Client\DubboPhpException
     */
    private function makeInvokerByProtocol($protocol = self::PROTOCOL_JSONRPC, $url = null, $debug = false)
    {
        if (!isset(self::$protocolSupports[$protocol]) || self::$protocolSupports[$protocol] != true) {
            throw new DubboPhpException('Protocol Not Supported yet.');
        }
        $providerName = 'DubboPhp\\Client\\Protocols\\' . ucfirst($protocol);
        return new $providerName($url, $debug);
    }

}