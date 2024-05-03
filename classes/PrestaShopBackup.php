<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
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
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class PrestaShopBackupCore
 */
class PrestaShopBackupCore
{
    /** @var string default backup directory. */
    public static $backupDir = '/backups/';
    /** @var int Object id */
    public $id;
    /** @var string Last error messages */
    public $error;
    /** @var string custom backup directory. */
    public $customBackupDir = null;
    /** @var bool|string $psBackupAll */
    public $psBackupAll = true;
    /** @var bool|string $psBackupDropTable */
    public $psBackupDropTable = true;

    /**
     * Creates a new backup object
     *
     * @param string|null $filename Filename of the backup file
     *
     * @throws PrestaShopException
     */
    public function __construct($filename = null)
    {
        if ($filename) {
            $this->id = $this->getRealBackupPath($filename);
        }

        $psBackupAll = Configuration::get('PS_BACKUP_ALL');
        $psBackupDropTable = Configuration::get('PS_BACKUP_DROP_TABLE');
        $this->psBackupAll = $psBackupAll !== false ? $psBackupAll : true;
        $this->psBackupDropTable = $psBackupDropTable !== false ? $psBackupDropTable : true;
    }

    /**
     * get the path to use for backup (customBackupDir if specified, or default)
     *
     * @param string|null $filename filename to use
     *
     * @return string full path
     *
     * @throws PrestaShopException
     */
    public function getRealBackupPath($filename = null)
    {
        $backupDir = static::getBackupPath($filename);
        if (!empty($this->customBackupDir)) {
            $backupDir = str_replace(
                _PS_ADMIN_DIR_.static::$backupDir,
                _PS_ADMIN_DIR_.$this->customBackupDir,
                $backupDir
            );

            if (strrpos($backupDir, DIRECTORY_SEPARATOR)) {
                $backupDir .= DIRECTORY_SEPARATOR;
            }
        }

        return $backupDir;
    }

    /**
     * Get the full path of the backup file
     *
     * @param string $filename prefix of the backup file (datetime will be the second part)
     *
     * @return string The full path of the backup file, or false if the backup file does not exists
     *
     * @throws PrestaShopException
     */
    public static function getBackupPath($filename = '')
    {
        $backupdir = realpath(_PS_ADMIN_DIR_.static::$backupDir);

        if ($backupdir === false) {
            throw new PrestaShopException(Tools::displayError('"Backup" directory does not exist.'));
        }

        // Check the realpath so we can validate the backup file is under the backup directory
        if (!empty($filename)) {
            $backupfile = realpath($backupdir.DIRECTORY_SEPARATOR.$filename);
        } else {
            $backupfile = $backupdir.DIRECTORY_SEPARATOR;
        }

        if ($backupfile === false || strncmp($backupdir, $backupfile, strlen($backupdir)) != 0) {
            throw new PrestaShopException(Tools::displayError('Failed to resolve backup file path.'));
        }

        return $backupfile;
    }

    /**
     * Check if a backup file exist
     *
     * @param string $filename prefix of the backup file (datetime will be the second part)
     *
     * @return bool true if backup file exist
     *
     * @throws PrestaShopException
     */
    public static function backupExist($filename)
    {
        $backupdir = realpath(_PS_ADMIN_DIR_.static::$backupDir);

        if ($backupdir === false) {
            throw new PrestaShopException(Tools::displayError('"Backup" directory does not exist.'));
        }

        return @filemtime($backupdir.DIRECTORY_SEPARATOR.$filename);
    }

    /**
     * you can set a different path with that function
     *
     * @TODO    include the prefix name
     *
     * @param string $dir
     *
     * @return bool bo
     */
    public function setCustomBackupPath($dir)
    {
        $customDir = DIRECTORY_SEPARATOR.trim($dir, '/').DIRECTORY_SEPARATOR;
        if (is_dir(_PS_ADMIN_DIR_.$customDir)) {
            $this->customBackupDir = $customDir;
        } else {
            return false;
        }

        return true;
    }

    /**
     * Get the URL used to retrieve this backup file
     *
     * @return string The url used to request the backup file
     */
    public function getBackupURL()
    {
        return __PS_BASE_URI__.basename(_PS_ADMIN_DIR_).'/backup.php?filename='.basename($this->id);
    }

    /**
     * Deletes a range of backup files
     *
     * @param string[] $list
     * @return bool True on success
     *
     * @throws PrestaShopException
     */
    public function deleteSelection($list)
    {
        foreach ($list as $file) {
            $backup = new self($file);
            if (!$backup->delete()) {
                $this->error = $backup->error;

                return false;
            }
        }

        return true;
    }

    /**
     * Creates a new backup file
     *
     * @return bool true on successful backup
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add()
    {
        if (!$this->psBackupAll) {
            $ignoreInsertTable = [
                _DB_PREFIX_.'connections',
                _DB_PREFIX_.'connections_page',
                _DB_PREFIX_.'connections_source',
                _DB_PREFIX_.'guest',
                _DB_PREFIX_.'statssearch',
            ];
        } else {
            $ignoreInsertTable = [];
        }

        // Generate some random number, to make it extra hard to guess backup file names
        $rand = dechex(mt_rand(0, min(0xffffffff, mt_getrandmax())));
        $date = time();
        $backupfile = $this->getRealBackupPath().$date.'-'.$rand.'.sql';

        // Figure out what compression is available and open the file
        if (function_exists('bzopen')) {
            $backupfile .= '.bz2';
            $fp = @bzopen($backupfile, 'w');
        } elseif (function_exists('gzopen')) {
            $backupfile .= '.gz';
            $fp = @gzopen($backupfile, 'w');
        } else {
            $fp = @fopen($backupfile, 'w');
        }

        if ($fp === false) {
            echo Tools::displayError('Unable to create backup file').' "'.addslashes($backupfile).'"';

            return false;
        }

        $this->id = realpath($backupfile);

        fwrite($fp, '/* Backup for '.Tools::getHttpHost(false, false).__PS_BASE_URI__."\n *  at ".date($date)."\n */\n");
        fwrite($fp, "\n".'SET NAMES \'utf8\';'."\n\n");

        $conn = Db::getInstance();
        // Find all tables
        $tables = $conn->getArray('SHOW TABLES');
        $found = 0;
        foreach ($tables as $table) {
            $table = current($table);

            // Skip tables which do not start with _DB_PREFIX_
            if (strlen($table) < strlen(_DB_PREFIX_) || strncmp($table, _DB_PREFIX_, strlen(_DB_PREFIX_)) != 0) {
                continue;
            }

            // Export the table schema
            $schema = $conn->getArray('SHOW CREATE TABLE `'.$table.'`');

            if (count($schema) != 1 || !isset($schema[0]['Table']) || !isset($schema[0]['Create Table'])) {
                fclose($fp);
                $this->delete();
                echo Tools::displayError('An error occurred while backing up. Unable to obtain the schema of').' "'.$table;

                return false;
            }

            fwrite($fp, '/* Scheme for table '.$schema[0]['Table']." */\n");

            if ($this->psBackupDropTable) {
                fwrite($fp, 'DROP TABLE IF EXISTS `'.$schema[0]['Table'].'`;'."\n");
            }

            fwrite($fp, $schema[0]['Create Table'].";\n\n");

            if (!in_array($schema[0]['Table'], $ignoreInsertTable)) {
                $data = $conn->query('SELECT * FROM `'.$schema[0]['Table'].'`');
                $sizeof = $conn->NumRows();
                $lines = explode("\n", $schema[0]['Create Table']);

                if ($data && $sizeof > 0) {
                    // Export the table data
                    fwrite($fp, 'INSERT INTO `'.$schema[0]['Table']."` VALUES\n");
                    $i = 1;
                    while ($row = $conn->nextRow($data)) {
                        $s = '(';

                        foreach ($row as $field => $value) {
                            $tmp = "'".pSQL($value, true)."',";
                            if ($tmp != "'',") {
                                $s .= $tmp;
                            } else {
                                foreach ($lines as $line) {
                                    if (strpos($line, '`'.$field.'`') !== false) {
                                        if (preg_match('/(.*NOT NULL.*)/Ui', $line)) {
                                            $s .= "'',";
                                        } else {
                                            $s .= 'NULL,';
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                        $s = rtrim($s, ',');

                        if ($i % 200 == 0 && $i < $sizeof) {
                            $s .= ");\nINSERT INTO `".$schema[0]['Table']."` VALUES\n";
                        } elseif ($i < $sizeof) {
                            $s .= "),\n";
                        } else {
                            $s .= ");\n";
                        }

                        fwrite($fp, $s);
                        ++$i;
                    }
                }
            }
            $found++;
        }

        fclose($fp);
        if ($found == 0) {
            $this->delete();
            echo Tools::displayError('No valid tables were found to backup.');

            return false;
        }

        return true;
    }

    /**
     * Delete the current backup file
     *
     * @return bool Deletion result, true on success
     */
    public function delete()
    {
        if (!$this->id || !unlink($this->id)) {
            $this->error = Tools::displayError('Error deleting').' '.($this->id ? '"'.$this->id.'"' :
                    Tools::displayError('Invalid ID'));

            return false;
        }

        return true;
    }
}
