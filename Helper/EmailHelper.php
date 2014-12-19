<?php
namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

class EmailHelper
{
    public function validateEmails(array $emails, &$invalidEmail = null)
    {
        foreach ($emails as $email) {
            if (!$this->isEmailValid($email)) {
                $invalidEmail = $email;
                throw new HelperException('Invalid email found: "' . $email . '"');
            }
        }
    }

    public function areEmailsValid(array $emails, &$invalidEmail = null)
    {
        try {
            $this->validateEmails($emails, $invalidEmail);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateEmail($email)
    {
        $this->validateEmails(array($email));
    }

    public function isEmailValid($email)
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function filterEmails(array $emails)
    {
        return array_slice(array_filter($emails, array(__CLASS__, 'isEmailValid')), 0);
    }
}
