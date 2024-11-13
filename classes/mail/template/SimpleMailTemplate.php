<?php

namespace Thirtybees\Core\Mail\Template;

use Mail;
use PrestaShopException;
use Thirtybees\Core\Mail\MailTemplate;

class SimpleMailTemplateCore implements MailTemplate
{

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @param string $templateName
     * @param string $contentType
     * @param string $template
     */
    public function __construct(string $templateName, string $contentType, string $template)
    {
        $this->templateName = $templateName;
        $this->contentType = $contentType;
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param array $parameters
     *
     * @return string
     * @throws PrestaShopException
     */
    public function renderTemplate(array $parameters): string
    {
        return Mail::substituteTemplateVars($this->getTemplate(), $parameters);
    }

}