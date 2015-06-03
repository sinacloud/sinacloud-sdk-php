<?php

namespace sinacloud\sae;

if (!interface_exists("SessionHandlerInterface")) {

    /**
     * Fake SessionHandlerInterface in PHP 5.3
     */
    interface SessionHandlerInterface {}
} else {
    interface SessionHandlerInterface extends \SessionHandlerInterface {}
}

class MemcacheSessionHandler implements SessionHandlerInterface
{
    private $mc;

    function open($savePath, $sessionName)
    {
        $this->mc = memcache_connect();
        return true;
    }

    function close()
    {
        return true;
    }

    function read($id)
    {
        return (string)memcache_get($this->mc, $id);
    }

    function write($id, $data)
    {
        return memcache_set($this->mc, $id, $data, 0, ini_get('session.gc_maxlifetime'));
    }

    function destroy($id)
    {
        return memcache_delete($this->mc, $id);
    }

    function gc($maxlifetime)
    {
        return true;
    }
}
