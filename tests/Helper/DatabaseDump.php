<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Tests\Helper;

use Exception;

/**
 * Class DatabaseDump
 *
 * @package PrestaShop\PrestaShop\Tests\Helper
 */
class DatabaseDump
{
    private $host;
    private $port;
    private $user;
    private $password;
    private $databaseName;
    private $dumpFile;

    /**
     * Constructor extracts database connection info from PrestaShop's confifugation,
     * but we use mysqldump and mysql for dump / restore.
     */
    private function __construct()
    {
        $hostAndMaybePort = explode(':', _DB_SERVER_);

        if (count($hostAndMaybePort) === 1) {
            $this->host = $hostAndMaybePort[0];
            $this->port = 3306;
        } elseif (count($hostAndMaybePort) === 2) {
            $this->host = $hostAndMaybePort[0];
            $this->port = $hostAndMaybePort[1];
        }

        $this->databaseName = _DB_NAME_;
        $this->user = _DB_USER_;
        $this->password = _DB_PASSWD_;
    }

    /**
     * Make a database dump and return an object on which you can call `restore` to restore the dump.
     */
    public static function create()
    {
        $dump = new DatabaseDump();

        $dump->dump();

        return $dump;
    }

    /**
     * The actual dump function.
     */
    private function dump()
    {
        $dumpCommand = $this->buildMySQLCommand('mysqldump', [$this->databaseName]);
        $this->dumpFile = tempnam(sys_get_temp_dir(), 'ps_dump');
        $dumpCommand .= ' > '.escapeshellarg($this->dumpFile);
        $this->exec($dumpCommand);
    }

    /**
     * Clean the temporary file.
     */
    public function __destruct()
    {
        if ($this->dumpFile && file_exists($this->dumpFile)) {
            unlink($this->dumpFile);
            $this->dumpFile = null;
        }
    }

    /**
     * Restore the dump to the actual database.
     */
    public function restore()
    {
        $restoreCommand = $this->buildMySQLCommand('mysql', [$this->databaseName]);
        $restoreCommand .= ' < '.escapeshellarg($this->dumpFile);
        $this->exec($restoreCommand);
    }

    /**
     * Wrapper to easily build mysql commands: sets password, port, user
     */
    private function buildMySQLCommand($executable, array $arguments = [])
    {
        $parts = [
            escapeshellarg($executable),
            '-u', escapeshellarg($this->user),
            '-P', escapeshellarg($this->port),
            '-h', escapeshellarg($this->host),
        ];

        if ($this->password) {
            $parts[] = '-p'.escapeshellarg($this->password);
        }

        $parts = array_merge($parts, array_map('escapeshellarg', $arguments));

        return implode(' ', $parts);
    }

    /**
     * Like exec, but will raise an exception if the command failed.
     */
    private function exec($command)
    {
        $output = [];
        $ret = 1;
        exec($command, $output, $ret);

        if ($ret !== 0) {
            throw new Exception(sprintf('Unable to exec command: `%s`, missing a binary?', $command));
        }

        return $output;
    }
}
