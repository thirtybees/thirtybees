<?php

namespace Thirtybees\Core\Mail;

interface MailTemplate
{
    /**
     * @return string
     */
    public function getContentType():string;

    /**
     * @return string
     */
    public function getTemplate():string;

    /**
     * Renders mail content from parameters
     *
     * @param array $parameters template paramters
     *
     * @return string
     */
    public function renderTemplate(array $parameters):string;
}