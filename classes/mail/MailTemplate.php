<?php

namespace Thirtybees\Core\Mail;

interface MailTemplate
{
    /**
     * Returns name of template
     *
     * @return string
     */
    public function getTemplateName():string;

    /**
     * Returns template content type
     *
     * @return string
     */
    public function getContentType():string;

    /**
     * Returns template content
     *
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