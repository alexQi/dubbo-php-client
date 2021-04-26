<?php
/**
 * Description
 *
 * @project     mulsite
 * @package     mulsite
 * @author      nickfan <nickfan81@gmail.com>
 * @link        http://www.axiong.me
 * @version     $Id$
 * @lastmodified: 2015-09-21 09:12
 *
 */

namespace DubboPhp\Client\Facades;

use DubboPhp\Client\Client;
use Illuminate\Support\Facades\Facade;

/**
 * Class DubboPhpClientFactory
 * @method static Client getService($serviceName, $forceVgp = false, $group = null, $protocol = Client::PROTOCOL_JSONRPC, $version = Client::VERSION_DEFAULT)
 *
 * @package DubboPhp\Client\Facades
 */
class DubboPhpClientFactory extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dubbo_cli.factory';
    }

}
