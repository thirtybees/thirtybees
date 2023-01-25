<?php

namespace Thirtybees\Core\Mail;

use Tools;

class MailAddressCore
{

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @param string $address
     * @param string|null $name
     */
    public function __construct(string $address, ?string $name)
    {
        $this->address = Tools::convertEmailToIdn($address);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

}