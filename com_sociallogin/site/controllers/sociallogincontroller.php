<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class SocialloginController extends BaseController
{
    // Task 1: Display a custom message
    public function displayMessage()
    {
        $app = Factory::getApplication();
        $params = $app->getParams('com_sociallogin');
        $customString = $params->get('custom_string', 'Default message');

        echo "Custom String: " . htmlspecialchars($customString);
    }

    // Task 2: Reverse a string passed via URL parameter
    public function reverseString()
    {
        $input = Factory::getApplication()->input;
        $string = $input->getString('str', '');

        if (empty($string)) {
            echo "No string provided.";
            return;
        }

        echo "Reversed String: " . strrev($string);
    }
}
