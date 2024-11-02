<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

$app = Factory::getApplication();
$controller = BaseController::getInstance('SocialLogin');
$controller->execute($app->input->getCmd('task'));
$controller->redirect();
