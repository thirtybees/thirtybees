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
 * Class AttachmentCore
 */
class AttachmentCore extends ObjectModel
{
    /** @var string $file */
    public $file;
    /** @var string $file_name */
    public $file_name;
    /** @var int $file_size */
    public $file_size;
    /** @var string|string[] $name */
    public $name;
    /** @var string $mime */
    public $mime;
    /** @var string|string[] $description */
    public $description;
    /** @var int position */
    public $position;

    /**
     * @var array Object model definition
     */
    public static $definition = [
        'table'     => 'attachment',
        'primary'   => 'id_attachment',
        'multilang' => true,
        'fields'    => [
            'file'        => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 40],
            'file_name'   => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 128, 'dbNullable' => false],
            'file_size'   => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'bigint(11) unsigned', 'dbDefault' => '0'],
            'mime'        => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 128],

            /* Lang fields */
            'name'        => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
            'description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => ObjectModel::SIZE_TEXT],
        ],
    ];

    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($autoDate = true, $nullValues = false)
    {
        $this->file_size = $this->getFileSize();

        return parent::add($autoDate, $nullValues);
    }

    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($nullValues = false)
    {
        $this->file_size = $this->getFileSize();

        return parent::update($nullValues);
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->fileExists()) {
            unlink($this->getFilePath());
        }

        $products = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('`id_product`')
                ->from('product_attachment')
                ->where('`id_attachment` = '.(int) $this->id)
        );

        Db::getInstance()->delete('product_attachment', '`id_attachment` = '.(int) $this->id);

        foreach ($products as $product) {
            Product::updateCacheAttachment((int) $product['id_product']);
        }

        return parent::delete();
    }

    /**
     * @param array $attachments
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function deleteSelection($attachments)
    {
        if (empty($attachments)) {
            return true;
        }

        $return = true;

        $attachmentsData = Db::readOnly()->getArray(
            (new DbQuery())
                ->select('*')
                ->from(bqSQL(Attachment::$definition['table']))
                ->where('`id_attachment` IN ('.implode(',', $attachments).')')
        );

        if (empty($attachmentsData)) {
            return true;
        }

        foreach ($attachmentsData as $attachmentData) {
            $attachment = new Attachment();
            $attachment->hydrate($attachmentData);
            $return = $attachment->delete() && $return;
        }

        return $return;
    }

    /**
     * @param int $idLang
     * @param int $idProduct
     * @param bool $include
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getAttachments($idLang, $idProduct, $include = true)
    {
        return Db::readOnly()->getArray('
            SELECT *
            FROM '._DB_PREFIX_.'attachment a
            LEFT JOIN '._DB_PREFIX_.'attachment_lang al
                ON (a.id_attachment = al.id_attachment AND al.id_lang = '.(int) $idLang.')
            WHERE a.id_attachment '.($include ? 'IN' : 'NOT IN').' (
                SELECT pa.id_attachment
                FROM '._DB_PREFIX_.'product_attachment pa
                WHERE id_product = '.(int) $idProduct.'
            )'
        );
    }

    /**
     * Unassociate $id_product from the current object
     *
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function deleteProductAttachments($idProduct)
    {
        $res = Db::getInstance()->delete(
            'product_attachment',
            '`id_product` = '.(int) $idProduct
        );

        Product::updateCacheAttachment((int) $idProduct);

        return $res;
    }

    /**
     * associate $id_product to the current object.
     *
     * @param int $idProduct id of the product to associate
     *
     * @return bool true if succed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function attachProduct($idProduct)
    {
        $res = Db::getInstance()->insert(
            'product_attachment',
            [
                'id_attachment' => (int) $this->id,
                'id_product'    => (int) $idProduct,
            ]
        );

        Product::updateCacheAttachment((int) $idProduct);

        return $res;
    }

    /**
     * Associate an array of id_attachment $array to the product $id_product
     * and remove eventual previous association
     *
     * @param int $idProduct
     * @param array $array
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function attachToProduct($idProduct, $array)
    {
        $result1 = Attachment::deleteProductAttachments($idProduct);

        if (is_array($array)) {
            $ids = [];
            foreach ($array as $idAttachment) {
                if ((int) $idAttachment > 0) {
                    $ids[] = ['id_product' => (int) $idProduct, 'id_attachment' => (int) $idAttachment];
                }
            }

            if (!empty($ids)) {
                $result2 = Db::getInstance()->insert('product_attachment', $ids);
            }
        }

        Product::updateCacheAttachment((int) $idProduct);
        if (is_array($array)) {
            return ($result1 && (!isset($result2) || $result2));
        }

        return $result1;
    }

    /**
     * @param int $idLang
     * @param array $list
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function getProductAttached($idLang, $list)
    {
        $idAttachments = [];
        if (is_array($list)) {
            foreach ($list as $attachment) {
                $idAttachments[] = $attachment['id_attachment'];
            }

            $tmp = Db::readOnly()->getArray(
                (new DbQuery())
                    ->select('*')
                    ->from('product_attachment', 'pa')
                    ->leftJoin('product_lang', 'pl', 'pa.`id_product` = pl.`id_product`')
                    ->where('pa.`id_attachment` IN ('.implode(',', array_map('intval', $idAttachments)).')')
                    ->where('pl.`id_shop` = '.(int) Context::getContext()->shop->id)
                    ->where('pl.`id_lang` = '.(int) $idLang)
            );
            $productAttachments = [];
            foreach ($tmp as $t) {
                $productAttachments[$t['id_attachment']][] = $t['name'];
            }

            return $productAttachments;
        } else {
            return false;
        }
    }

    /**
     * Return a sha1 filename
     *
     * @return string Sha1 unique filename
     */
    public static function getNewFilename()
    {
        do {
            $filename = sha1(microtime());
        } while (file_exists(_PS_DOWNLOAD_DIR_ . $filename));

        return $filename;
    }


    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return _PS_DOWNLOAD_DIR_ . basename($this->file);
    }

    /**
     * @return bool
     */
    public function fileExists(): bool
    {
        return (
            file_exists($this->getFilePath()) &&
            is_file($this->getFilePath())
        );
    }

    /**
     * @return int
     */
    protected function getFileSize(): int
    {
        if ($this->fileExists()) {
            return (int)filesize($this->getFilePath());
        }
        return 0;
    }
}
