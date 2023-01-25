<?php

namespace Thirtybees\Core\Mail;


class MailAttachementCore
{
    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $mime;

    /**
     * @param string $content
     * @param string $name
     * @param string $mime
     */
    public function __construct(string $content, string $name, string $mime)
    {
        $this->content = $content;
        $this->name = $name;
        $this->mime = $mime;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }
}