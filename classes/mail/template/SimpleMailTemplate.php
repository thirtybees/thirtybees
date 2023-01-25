<?php

namespace Thirtybees\Core\Mail\Template;

use Context;
use PrestaShopException;
use Thirtybees\Core\Mail\MailTemplate;
use Tools;

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
     * @param string $contentType
     * @param string $template
     */
    public function __construct(string $contentType, string $template)
    {
        $this->contentType = $contentType;
        $this->template = $template;
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
        // convert iamgeFile parameters to url. This is used, for example, by {shop_logo} parameter
        foreach ($parameters as &$parameter) {
            if (is_array($parameter) && isset($parameter['type']) && $parameter['type'] === 'imageFile') {
                $filepath = $parameter['filepath'] ?? '';
                $filepath = str_replace(_PS_ROOT_DIR_, '', $filepath);
                $parameter = Context::getContext()->link->getMediaLink($filepath);
            }
        }

        $template = $this->getTemplate();
        $search = array_keys($parameters);
        $replace = array_values($parameters);
        return str_replace($search, $replace, $template);
    }

}