<?php
namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

class SocketAddressesHelper
{
    public function validateSocketAddresses(array $socketAddresses, &$invalidSocketAddress = null)
    {
        foreach ($socketAddresses as $socketAddress) {
            if (!$this->isSocketAddressValid($socketAddress)) {
                $invalidSocketAddress = $socketAddress;
                throw new HelperException('Invalid socket address found: "' . $socketAddress . '"');
            }
        }
    }

    public function areSocketAddressesValid(array $socketAddresses, &$invalidSocketAddress = null)
    {
        try {
            $this->validateSocketAddresses($socketAddresses, $invalidSocketAddress);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateSocketAddress($socketAddress)
    {
        $this->validateSocketAddresses(array($socketAddress));
    }

    public function isSocketAddressValid($socketAddress)
    {
        $parts = explode(':', $socketAddress);
        if (count($parts) !== 2) {
            return false;
        }

        if (!filter_var($parts[0], FILTER_VALIDATE_IP)) {
            return false;
        }

        if (!preg_match('/^\d+$/', $parts[1])) {
            return false;
        }

        if ((int)$parts[1] < 1 || (int)$parts[1] > 65535) {
            return false;
        }

        return true;
    }

    public function filterSocketAddresses(array $socketAddresses)
    {
        return array_slice(array_filter($socketAddresses, array(__CLASS__, 'isSocketAddressValid')), 0);
    }
}
