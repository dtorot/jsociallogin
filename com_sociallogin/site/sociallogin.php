<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

// Get the task from the URL
$task = Factory::getApplication()->input->get('task', 'default');

// Create the controller instance
$controller = BaseController::getInstance('Sociallogin');
$controller->execute($task);
$controller->redirect();
