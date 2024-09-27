<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Clients\Geoapify\Classes;

class ForwardBatch 
{
    protected $addresses;

    /**
     * [Description for __construct]
     *
     * @param string[]|null $addresses
     * 
     */
    public function __construct(?array $addresses = null)
    {
        $this->addresses = $addresses ?? [];    
    }

    /**
     * [Description for getAddresses]
     *
     * @return string[]
     * 
     */
    public function getAddresses() : array
    {
        return $this->addresses;
    }

    /**
     * [Description for addAddress]
     *
     * @param string $address
     * 
     * @return self
     * 
     */
    public function addAddress(string $address) : self
    {
        $this->addresses[] = $address;
        return $this;
    }

    /**
     * [Description for addAddresses]
     *
     * @param string[] $addresses
     * 
     * @return self
     * 
     */
    public function addAddresses(array $addresses) : self
    {
        $this->addresses = array_merge($this->addresses, $addresses);
        return $this;
    }

    /**
     * [Description for toArray]
     * 
     * https://apidocs.geoapify.com/docs/geocoding/batch/#api
     *
     * @return array
     * 
     */
    public function toArray() : array
    {
        return $this->addresses;
    }
}