<?php
/**
 * @version    CVS: 0.0.1
 * @package    Com_Jsociallogin
 * @author     David Toro Triana <dtorot@opensai.org>
 * @copyright  2024 David Toro Triana
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Jsl\Component\Jsociallogin\Administrator\Extension\JsocialloginComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;


/**
 * The Jsociallogin service provider.
 *
 * @since  0.0.1
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   0.0.1
	 */
	public function register(Container $container)
	{

		$container->registerServiceProvider(new CategoryFactory('\\Jsl\\Component\\Jsociallogin'));
		$container->registerServiceProvider(new MVCFactory('\\Jsl\\Component\\Jsociallogin'));
		$container->registerServiceProvider(new ComponentDispatcherFactory('\\Jsl\\Component\\Jsociallogin'));
		$container->registerServiceProvider(new RouterFactory('\\Jsl\\Component\\Jsociallogin'));

		$container->set(
			ComponentInterface::class,
			function (Container $container)
			{
				$component = new JsocialloginComponent($container->get(ComponentDispatcherFactoryInterface::class));

				$component->setRegistry($container->get(Registry::class));
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setCategoryFactory($container->get(CategoryFactoryInterface::class));
				$component->setRouterFactory($container->get(RouterFactoryInterface::class));

				return $component;
			}
		);
	}
};
