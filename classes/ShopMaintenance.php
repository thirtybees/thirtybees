<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class ShopMaintenance
 *
 * This class implements tasks for maintaining hte shop installation, to be
 * run on a regular schedule. It gets called by an asynchronous Ajax request
 * in DashboardController.
 *
 * @since 1.0.8
 */
class ShopMaintenanceCore
{
    /**
     * Run tasks as needed. Should take care of running tasks not more often
     * than needed and that one run takes not longer than a few seconds.
     *
     * This method gets triggered by the 'getNotifications' Ajax request, so
     * every two minutes while somebody has back office open.
     *
     * @since 1.0.8
     */
    public static function run()
    {
        $now = time();
        $lastRun = Configuration::get('SHOP_MAINTENANCE_LAST_RUN');
        if ($now - $lastRun > 86400) {
            // Run daily tasks.
            static::adjustThemeHeaders();
            static::optinShop();
            static::cleanAdminControllerMessages();

            Configuration::updateGlobalValue('SHOP_MAINTENANCE_LAST_RUN', $now);
       		Configuration::updateGlobalValue('SHOP_MAINTENANCE_IMAGES_RUN', '0|0');
        }
        
        $imagesRun = Configuration::get('SHOP_MAINTENANCE_IMAGES_RUN');
        $imgRun = explode("|", $imagesRun);
		$imagesMax = intval($imgRun[1]);
		$imagesNow = intval($imgRun[0]);
        if ($imagesNow < $imagesMax || $imagesMax == 0) static::CheckAndFixImages($imagesNow,$imagesMax); 
    }

    /**
     * Correct the "generator" meta tag in templates. Technology detection
     * sites like builtwith.com don't recognize thirty bees technology if the
     * theme template inserts a meta tag "generator" for PrestaShop.
     *
     * @since 1.0.8
     */
    public static function adjustThemeHeaders()
    {
        foreach (scandir(_PS_ALL_THEMES_DIR_) as $themeDir) {
            if ( ! is_dir(_PS_ALL_THEMES_DIR_.$themeDir)
                || in_array($themeDir, ['.', '..'])) {
                continue;
            }

            $headerPath = _PS_ALL_THEMES_DIR_.$themeDir.'/header.tpl';
            if (is_writable($headerPath)) {
                $header = file_get_contents($headerPath);
                $newHeader = preg_replace('/<\s*meta\s*name\s*=\s*["\']generator["\']\s*content\s*=\s*["\'].*["\']\s*>/i',
                    '<meta name="generator" content="thirty bees">', $header);
                if ($newHeader !== $header) {
                    file_put_contents($headerPath, $newHeader);
                    Tools::clearSmartyCache();
                }
            }
        }
    }

    /**
     * Handle shop optin.
     *
     * @since 1.0.8
     */
    public static function optinShop()
    {
        $name = Configuration::STORE_REGISTERED;
        if ( ! Configuration::get($name)) {
            $employees = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_);
            // Usually there's only one employee when we run this code.
            foreach ($employees as $employee) {
                $employee = new Employee($employee);
                $employee->optin = true;
                if ($employee->update()) {
                    Configuration::updateValue($name, 1);
                }
            }
        }
    }

    /**
     * Delete lost AdminController messages.
     *
     * @since 1.0.8
     */
    public static function cleanAdminControllerMessages()
    {
        $name = AdminController::MESSAGE_CACHE_PATH;
        $nameLength = strlen($name);
        foreach (scandir(_PS_CACHE_DIR_) as $candidate) {
            if (substr($candidate, 0, $nameLength) === $name) {
                $path = _PS_CACHE_DIR_.'/'.$candidate;
                if (time() - filemtime($path) > 3600) {
                    unlink($path);
                }
            }
        }
    }
    
     /**
     * Clean image products directories 'img/p' and delete database infos without file linked on server.
     *
     * @since 1.0.9
     */
    public static function CheckAndFixImages($imagesNow=0,$imagesMax=0)
    {
		$starttime = time(); 
		$path = _PS_ROOT_DIR_.'/img/p/';
		$directory = new \RecursiveDirectoryIterator($path);
		$iterator = new \RecursiveIteratorIterator($directory);
		$count_ko = 0 ;	
		$count_noexist = 0;
					
		if ($imgtypes = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT name FROM '._DB_PREFIX_.'image_type WHERE products= 1')) {
			foreach ($imgtypes as $type)
			$Image_types_product[]=$type['name'];
		}

		if ($images_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT id_image FROM '._DB_PREFIX_.'image_shop WHERE 1')) {
			foreach ($images_list as $img)
			$Image_listing[]=$img['id_image'];
		}

		foreach ($iterator as $info) {
			$filename = $info->getFilename();
			$pathname = $info->getPathname();
			   
			if ($info->isDir() || $filename=='index.php'){ 
				 continue;
			}
			$img_nbr = explode("-", $filename, 2);
			$img_nbr[0] = str_replace(".jpg", "", $img_nbr[0]);

			if (isset($img_nbr[1])) {
				$img_type_name = str_replace(".jpg", "", $img_nbr[1]);
				$images[] = array('id' => $img_nbr[0], 'path' => $pathname, 'type' => $img_type_name);
			}
			else {
				$images[] = array('id' => $img_nbr[0], 'path' => $pathname, 'type' => 'default');
			}
		}
			
		$imagesMax = count($images)-1;
					
		foreach($images as $key => $value) {
			if ($key < $imagesNow) continue;
			$image_id = intval($value['id']);
			if (!in_array($image_id, $Image_listing, true)) {
				$count_ko++;
				unlink($value['path']);
			}
			elseif (!in_array($value['type'], $Image_types_product, true) && $value['type']!='default') {
				$count_ko++;
				unlink($value['path']);
			}
			
			$imagesRun = $key.'|'.$imagesMax;	
			$imagesHere = $key;	
			$imdigits = strlen($image_id); 

			switch ($imdigits) {
				case 1:
					$directory=$image_id;
					break;
				case 2:
					$str = $image_id;
					$chars = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
					$directory = $chars[0]."/".$chars[1];
					break;
				case 3:
					$str = $image_id;
					$chars = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
					$directory = $chars[0]."/".$chars[1]."/".$chars[2];
					break;
				case 4:
					$str = $image_id;
					$chars = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
					$directory = $chars[0]."/".$chars[1]."/".$chars[2]."/".$chars[3];
					break;
				case 5:
					$str = $image_id;
					$chars = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
					$directory = $chars[0]."/".$chars[1]."/".$chars[2]."/".$chars[3]."/".$chars[4];
					break;
				case 6:
					$str = $image_id;
					$chars = preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
					$directory = $chars[0]."/".$chars[1]."/".$chars[2]."/".$chars[3]."/".$chars[4]."/".$chars[5];
				break;
			}					
			
			$file_pointer = _PS_ROOT_DIR_."/img/p/".$directory."/".$image_id.".jpg";
				
			if (!file_exists($file_pointer)) {
				$sql = "DELETE FROM "._DB_PREFIX_."image WHERE id_image=".$image_id;
				Db::getInstance()->execute($sql);
				$sql = "DELETE FROM "._DB_PREFIX_."image_lang WHERE id_image=".$image_id;
				Db::getInstance()->execute($sql);
				$sql = "DELETE FROM "._DB_PREFIX_."image_shop WHERE id_image=".$image_id;
				Db::getInstance()->execute($sql);
				$count_noexist++;
			}
			
			$now = time()-$starttime;
			if ($now > 2.99) {  
				break;
			}	
		}
	    
		if ($imagesHere==$imagesMax) {
			PrestaShopLoggerCore::addLog($imagesHere.' images processed, '.$count_ko.' file(s) deleted - This task is finished for today', 1, null, 'ImageClean', '199');
			if ($count_noexist>0) PrestaShopLoggerCore::addLog( 'Found: '$count_noexist.' database image product to delete', 1, null,'ImageClean', '199');
		}
		elseif ($imagesHere>0) {
			PrestaShopLoggerCore::addLog($imagesHere.' images processed ('.$imagesMax.' total), '.$count_ko.' file(s) deleted', 1, null,'ImageClean', '199');
			if ($count_noexist>0) PrestaShopLoggerCore::addLog( 'Found: '$count_noexist.' database image product to delete', 1, null,'ImageClean', '199');
		}
		Configuration::updateGlobalValue('SHOP_MAINTENANCE_IMAGES_RUN', $imagesRun);
    }   
}
