<?php
/**
 * Copyright (C) 2023-2023 thirty bees
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
 * @copyright 2023-2023 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

namespace Thirtybees\Core\Mail;

/**
 * Interface MailTransport
 */
interface MailTransport
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return string|null
     */
    public function getConfigUrl();

    /**
     * @param int $idShop
     * @param int $idLang
     * @param MailAddress $fromAddress
     * @param MailAddress[] $toAddresses
     * @param MailAddress[] $bccAddresses
     * @param MailAddress $replyTo
     * @param string $subject
     * @param MailTemplate[] $templates ,
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
    ): bool;
}
