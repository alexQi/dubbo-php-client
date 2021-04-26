<?php

namespace DubboPhp\Client\Protocols;

use Exception;
use DubboPhp\Client\Invoker;
use DubboPhp\Client\Utility;
use DubboPhp\Client\Type;
use DubboPhp\Client\Adapter;
use Icecave\Chrono\DateTime;
use Icecave\Collections\Vector;
use Icecave\Flax\Serialization\Encoder;
use Icecave\Flax\Serialization\Decoder;
use stdClass;

/**
 * Class Dubbo
 *
 * @package DubboPhp\Client\Protocols
 */
class Dubbo extends Invoker
{

    const DEFAULT_LANGUAGE = 'Java';

    /**
     * Dubbo constructor.
     *
     * @param null $url
     * @param bool $debug
     */
    public function __construct($url = null, $debug = false)
    {
        parent::__construct($url, $debug);
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return array|int|stdClass
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        $schemeInfo = parse_url($this->url);

        return $this->connect(
            $schemeInfo['host'],
            $schemeInfo['port'],
            substr($schemeInfo['path'], 1),
            $name,
            $arguments,
            null,
            null,
            null
        );
    }

    /**
     * @param        $host
     * @param        $port
     * @param        $path
     * @param        $method
     * @param        $args
     * @param        $group
     * @param        $version
     * @param string $dubboVersion
     *
     * @return array|int|stdClass
     * @throws Exception
     */
    private function connect($host, $port, $path, $method, $args, $group, $version, $dubboVersion = "2.6.0")
    {
        try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($socket, $host, $port);

            $buffer = $this->buffer($path, $method, $args, $group, $version, $dubboVersion);
            socket_write($socket, $buffer, strlen($buffer));

            $data = '';
            $bl   = 16;

            do {
                $chunk = @socket_read($socket, 1024);

                if (empty($data)) {
                    $arr = Utility::sliceToArray($chunk, 0, 16);
                    $i   = 0;

                    while ($i < 3) {
                        $bl += array_pop($arr) * (256 ** $i++);
                    }
                }

                $data .= $chunk;

                if (empty($chunk) || strlen($data) >= $bl) {
                    break;
                }
            } while (true);
            socket_close($socket);
            return $this->parser($data);
        } catch (\Exception $e) {
            $message = $data ? $this->rinser($data) : $e->getMessage();
            throw new \Exception($message);
        }
    }

    /**
     * @param $data
     *
     * @return array|int|stdClass
     * @throws \Icecave\Flax\Exception\DecodeException
     */
    private function parser($data)
    {
        $decoder = new Decoder;
        $decoder->feed($this->rinser($data));
        $obj = $decoder->finalize();
        return $this->recursive($obj);
    }

    /**
     * @param $data
     *
     * @return false|string
     */
    private function rinser($data)
    {
        return substr($data, 17);
    }

    /**
     * @param $data
     *
     * @return array|int|stdClass
     */
    private function recursive($data)
    {
        try {
            if ($data instanceof Vector) {
                return $this->recursive($data->elements());
            }
            if ($data instanceof DateTime) {
                return $data->unixTime();
            }
            if ($data instanceof stdClass) {
                foreach ($data as $key => $value) {
                    if (empty($value->cause)) {
                        $data->$key = $this->recursive($value);
                    }
                }
            }
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = $this->recursive($value);
                }
            }
            return $data;
        } catch
        (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $path
     * @param $method
     * @param $args
     * @param $group
     * @param $version
     * @param $dubboVersion
     *
     * @return string
     * @throws \Icecave\Flax\Exception\EncodeException
     */
    private function buffer($path, $method, $args, $group, $version, $dubboVersion)
    {
        $typeRefs = $this->typeRefs($args);

        $attachment = Type::object(
            'java.util.HashMap',
            [
                'interface' => $path,
                'version'   => $version,
                'group'     => $group,
                'path'      => $path,
                'timeout'   => '60000'
            ]
        );

        $bufferBody = $this->bufferBody($path, $method, $typeRefs, $args, $attachment, $version, $dubboVersion);
        $bufferHead = $this->bufferHead(strlen($bufferBody));
        return $bufferHead . $bufferBody;
    }

    /**
     * @param $length
     *
     * @return string
     */
    private function bufferHead($length)
    {
        $head = [0xda, 0xbb, 0xc2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $i    = 15;

        if ($length - 256 < 0) {
            $head[$i] = $length - 256;
        } else {
            while ($length - 256 > 0) {
                $head[$i--] = $length % 256;
                $length     = $length >> 8;
            }

            $head[$i] = $length;
        }

        return Utility::asciiArrayToString($head);
    }

    /**
     * @param $path
     * @param $method
     * @param $typeRefs
     * @param $args
     * @param $attachment
     * @param $version
     * @param $dubboVersion
     *
     * @return string
     * @throws \Icecave\Flax\Exception\EncodeException
     */
    private function bufferBody($path, $method, $typeRefs, $args, $attachment, $version, $dubboVersion)
    {
        $body    = '';
        $encoder = new Encoder;
        $body    .= $encoder->encode($dubboVersion);
        $body    .= $encoder->encode($path);
        $body    .= $encoder->encode($version);
        $body    .= $encoder->encode($method);
        $body    .= $encoder->encode($typeRefs);

        foreach ($args as $arg) {
            $body .= $encoder->encode($arg);
        }

        $body .= $encoder->encode($attachment);
        return $body;
    }

    /**
     * @param $args
     *
     * @return string
     * @throws Exception
     */
    private function typeRefs(&$args)
    {
        $typeRefs = '';

        if (count($args)) {
            $lang = Adapter::language(self::DEFAULT_LANGUAGE);

            foreach ($args as &$arg) {
                if ($arg instanceof Type) {
                    $type = $arg->type;
                    $arg  = $arg->value;
                } else {
                    $type = $this->argToType($arg);
                }

                $typeRefs .= $lang->typeRef($type);
            }
        }

        return $typeRefs;
    }

    /**
     * @param $arg
     *
     * @return int
     * @throws Exception
     */
    private function argToType($arg)
    {
        switch (gettype($arg)) {
            case 'integer':
                return $this->numToType($arg);
            case 'boolean':
                return Type::BOOLEAN;
            case 'double':
                return Type::DOUBLE;
            case 'string':
                return Type::STRING;
            case 'object':
                return $arg->className();
            default:
                throw new \Exception("Handler for type {$arg} not implemented");
        }
    }

    /**
     * @param $value
     *
     * @return int
     */
    private function numToType($value)
    {
        if (Utility::isBetween($value, -32768, 32767)) {
            return Type::SHORT;
        }

        if (Utility::isBetween($value, -2147483648, 2147483647)) {
            return Type::INT;
        }

        return Type::LONG;
    }
}