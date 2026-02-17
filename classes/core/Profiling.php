<?php

namespace Thirtybees\Core;

class Profiling
{
    const COOKIE_NAME = 'thirtybees_profiling';
    const EXPIRATION = 3600;

    /**
     * @var bool
     */
    private bool $enabled;
    /**
     * @var bool
     */
    private bool $enabledGlobally;

    /**
     * Profiling constructor
     */
    public function __construct()
    {
        if (defined('_PS_DEBUG_PROFILING_') && _PS_DEBUG_PROFILING_) {
            $this->enabled = true;
            $this->enabledGlobally = true;
        } else {
            $this->enabled = $this->checkCookie();
            $this->enabledGlobally = false;
        }
    }

    /**
     * @return bool
     */
    protected function checkCookie(): bool
    {
        if (isset($_COOKIE[static::COOKIE_NAME])) {
            $pair = explode(':', (string)$_COOKIE[static::COOKIE_NAME]);
            if (count($pair) === 2) {
                $ts = (int)$pair[0];
                $signature = (string)$pair[1];
                if ($ts > 0 && $signature) {
                    $expected = $this->calculateSignature($ts);
                    if ($signature === $expected) {
                        if (time() < $ts) {
                            return true;
                        }
                    }
                }
            }
            $this->disableForSession();
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    public function isEnabledGlobally(): bool
    {
        return $this->enabledGlobally;
    }

    /**
     * @return void
     */
    public function enableForSession()
    {
        $until = time() + static::EXPIRATION;
        $signature = $this->calculateSignature($until);
        setcookie(static::COOKIE_NAME, $until . ':' . $signature, $until, '/', "", true);
        $this->enabled = true;
    }

    /**
     * @return void
     */
    public function disableForSession()
    {
        setcookie(static::COOKIE_NAME, '', -1, '/', "", true);
        $this->enabled = false;
    }

    /**
     * @param int $ts
     * @return string
     */
    private function calculateSignature(int $ts): string
    {
        return sha1('profiling' . _COOKIE_KEY_ . $ts);
    }
}