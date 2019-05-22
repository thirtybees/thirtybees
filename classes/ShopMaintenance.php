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
            static::cleanImagesDir('new');

            Configuration::updateGlobalValue('SHOP_MAINTENANCE_LAST_RUN', $now);
        }
        else static::cleanImagesDir();
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
     * Clean image products directories 'img/p'.
     *
     * @since 1.0.9
     */
    public static function cleanImagesDir($new='') 
	{				
		if (isset($new) && $new=='new') {
			$images_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT id_image FROM '._DB_PREFIX_.'image ORDER BY id_image DESC');	
			$imagesMax = $images_list[0]['id_image'];
			Configuration::updateGlobalValue('SHOP_MAINTENANCE_IMAGES_RUN', $imagesMax.'|'.$imagesMax); 
		}
		else {
			$imagesRun = Configuration::get('SHOP_MAINTENANCE_IMAGES_RUN');
			$imgRun = explode("|", $imagesRun);
			if (isset($imgRun[1])) $imagesMax = intval($imgRun[1]);
				else $imagesMax = 0;	
			if (isset($imgRun[0])) $imagesNow = intval($imgRun[0]);
				else $imagesNow = 0;
			if ($imagesMax > 0 && $imagesNow > 0) static::CheckAndFixImages($imagesNow, $imagesMax); 
		}
	}

 	public static function CheckAndFixImages($imagesNow, $imagesMax)
    {
		$starttime = time(); 
	
		if ($imgtypes = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT name FROM '._DB_PREFIX_.'image_type WHERE products=1')) {
			foreach ($imgtypes as $type)
			$Image_types_product[]=$type['name'];
		}
		
		if ($images_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT id_image FROM '._DB_PREFIX_.'image_shop ORDER BY id_image DESC')) {
			foreach ($images_list as $img)
			$Image_listing[]=$img['id_image'];
		}

		for ($i = $imagesNow; $i >= 0; $i--) {		
			$directory = _PS_PROD_IMG_DIR_.implode('/', str_split($i));
			if (file_exists($directory)) {
				if (!in_array($i, $Image_listing)) Image::deleteAllImages($directory);
				else foreach (glob($directory . '/*' ) as $filename) {
					$file_parts = pathinfo($filename);
					if (is_file($filename) && $file_parts['extension'] == "jpg") {	
						$img_number = explode("-", $file_parts['filename'], 2);			
						if (!in_array($img_number[0], $Image_listing)) unlink($filename);				
						elseif (!empty($img_number[1]) && !in_array($img_number[1], $Image_types_product)) unlink($filename);																
					}
				} 
			}
			else {
				$sql = "DELETE FROM "._DB_PREFIX_."image WHERE id_image=".$i;
				Db::getInstance()->execute($sql);
				$sql = "DELETE FROM "._DB_PREFIX_."image_lang WHERE id_image=".$i;
				Db::getInstance()->execute($sql);
				$sql = "DELETE FROM "._DB_PREFIX_."image_shop WHERE id_image=".$i;
				Db::getInstance()->execute($sql);
				$sql = "DELETE FROM "._DB_PREFIX_."product_attribute_image WHERE id_image=".$i;
				Db::getInstance()->execute($sql);
			}			
			$now = time()-$starttime;
			if ($now > 2.99) {  
				break;
			}
		}
		Configuration::updateGlobalValue('SHOP_MAINTENANCE_IMAGES_RUN', ($i+1).'|'.$imagesMax); 					
	}
}
