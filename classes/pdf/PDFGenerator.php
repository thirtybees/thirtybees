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

/**
 * Class PDFGeneratorCore
 *
 * @since 1.0.0
 */
class PDFGeneratorCore extends TCPDF
{
    const DEFAULT_FONT = 'helvetica';

    // @codingStandardsIgnoreStart
    public $header;
    public $footer;
    public $pagination;
    public $content;
    public $font;

    public $font_by_lang = [
        'ja' => 'cid0jp',
        'bg' => 'freeserif',
        'ru' => 'freeserif',
        'uk' => 'freeserif',
        'mk' => 'freeserif',
        'el' => 'freeserif',
        'en' => 'dejavusans',
        'vn' => 'dejavusans',
        'pl' => 'dejavusans',
        'ar' => 'dejavusans',
        'fa' => 'dejavusans',
        'ur' => 'dejavusans',
        'az' => 'dejavusans',
        'ca' => 'dejavusans',
        'gl' => 'dejavusans',
        'hr' => 'dejavusans',
        'sr' => 'dejavusans',
        'si' => 'dejavusans',
        'cs' => 'dejavusans',
        'sk' => 'dejavusans',
        'ka' => 'dejavusans',
        'he' => 'dejavusans',
        'lo' => 'dejavusans',
        'lt' => 'dejavusans',
        'lv' => 'dejavusans',
        'tr' => 'dejavusans',
        'ko' => 'cid0kr',
        'zh' => 'cid0cs',
        'tw' => 'cid0cs',
        'th' => 'freeserif',
    ];
    // @codingStandardsIgnoreEnd

    /**
     * @param bool   $useCache
     * @param string $orientation
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($useCache = false, $orientation = 'P')
    {
        parent::__construct($orientation, 'mm', 'A4', true, 'UTF-8', $useCache, false);
        $this->setRTL(Context::getContext()->language->is_rtl);
    }

    /**
     * set the PDF encoding
     *
     * @param string $encoding
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    /**
     *
     * set the PDF header
     *
     * @param string $header HTML
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createHeader($header)
    {
        $this->header = $header;
    }

    /**
     *
     * set the PDF footer
     *
     * @param string $footer HTML
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createFooter($footer)
    {
        $this->footer = $footer;
    }

    /**
     *
     * create the PDF content
     *
     * @param string $content HTML
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createContent($content)
    {
        $this->content = $content;
    }

    /**
     *
     * create the PDF pagination
     *
     * @param string $pagination HTML
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function createPagination($pagination)
    {
        $this->pagination = $pagination;
    }

    /**
     * Change the font
     *
     * @param string $isoLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function setFontForLang($isoLang)
    {
        if (array_key_exists($isoLang, $this->font_by_lang)) {
            $this->font = $this->font_by_lang[$isoLang];
        } else {
            $this->font = self::DEFAULT_FONT;
        }

        $this->setHeaderFont([$this->font, '', PDF_FONT_SIZE_MAIN, '', false]);
        $this->setFooterFont([$this->font, '', PDF_FONT_SIZE_MAIN, '', false]);

        $this->setFont($this->font, '', PDF_FONT_SIZE_MAIN, '', false);
    }

    /**
     * @see TCPDF::Header()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function Header()
    {
        $this->writeHTML($this->header);
    }

    /**
     * @see TCPDF::Footer()
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function Footer()
    {
        $this->writeHTML($this->footer);
        $this->FontFamily = self::DEFAULT_FONT;
        $this->writeHTML($this->pagination);
    }

    /**
     * Render HTML template
     *
     * @param string $filename
     * @param bool   $display true:display to user, false:save, 'I','D','S' as fpdf display
     *
     * @throws PrestaShopException
     *
     * @return string HTML rendered
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function render($filename, $display = true)
    {
        if (empty($filename)) {
            throw new PrestaShopException('Missing filename.');
        }

        $this->lastPage();

        if ($display === true) {
            $output = 'D';
        } elseif ($display === false) {
            $output = 'S';
        } elseif ($display == 'D') {
            $output = 'D';
        } elseif ($display == 'S') {
            $output = 'S';
        } elseif ($display == 'F') {
            $output = 'F';
        } else {
            $output = 'I';
        }

        return $this->output($filename, $output);
    }

    /**
     * Write a PDF page
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function writePage()
    {
        $this->SetHeaderMargin(5);
        $this->SetFooterMargin(21);
        $this->setMargins(10, 40, 10);
        $this->AddPage();
        $this->writeHTML($this->content, true, false, true, false, '');
    }

    /**
     * Override of TCPDF::getRandomSeed() - getmypid() is blocked on several hosting
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected function getRandomSeed($seed = '')
    {
        $seed .= microtime();

        if (function_exists('openssl_random_pseudo_bytes') && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) {
            // this is not used on windows systems because it is very slow for a know bug
            $seed .= openssl_random_pseudo_bytes(512);
        } else {
            for ($i = 0; $i < 23; ++$i) {
                $seed .= uniqid('', true);
            }
        }

        $seed .= uniqid('', true);
        $seed .= rand();
        $seed .= __FILE__;
        $seed .= $this->bufferlen;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $seed .= $_SERVER['REMOTE_ADDR'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $seed .= $_SERVER['HTTP_USER_AGENT'];
        }
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $seed .= $_SERVER['HTTP_ACCEPT'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $seed .= $_SERVER['HTTP_ACCEPT_ENCODING'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $seed .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $seed .= $_SERVER['HTTP_ACCEPT_CHARSET'];
        }

        $seed .= rand();
        $seed .= uniqid('', true);
        $seed .= microtime();

        return $seed;
    }
}
