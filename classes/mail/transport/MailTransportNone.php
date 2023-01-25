<?php
/**
 * Copyright (C) 2021-2021 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2021-2021 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Mail\Transport;


use Thirtybees\Core\Mail\MailAddress;
use Thirtybees\Core\Mail\MailAttachement;
use Thirtybees\Core\Mail\MailTemplate;
use Thirtybees\Core\Mail\MailTransport;
use Translate;

/**
 * Class EMailTransportNoneCore
 */
class MailTransportNoneCore implements MailTransport
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return Translate::getAdminTranslation('None', 'Mail');
    }

    /**
     * @return null
     */
    public function getConfigUrl()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Translate::getAdminTranslation('Never send emails (may be useful for testing purposes)', 'Mail');
    }

    /**
     * @param int $idShop
     * @param int $idLang
     * @param MailAddress $fromAddress
     * @param MailAddress[] $toAddresses
     * @param MailAddress[] $bccAddresses
     * @param MailAddress $replyTo
     * @param string $subject
     * @param MailTemplate[] $templates
     * @param array $templateVars
     * @param MailAttachement[] $attachements
     *
     * @return bool
     */
    public function sendMail(
        int         $idShop,
        int         $idLang,
        MailAddress $fromAddress,
        array       $toAddresses,
        array       $bccAddresses,
        MailAddress $replyTo,
        string      $subject,
        array       $templates,
        array       $templateVars,
        array       $attachements
    ): bool
    {
        return true;
    }
}