<?php

namespace Akrabat\ApigilityXml;

use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\View\ApiProblemRenderer;
use Akrabat\ApigilityXml\View\XmlRenderer;
use Akrabat\ApigilityXml\View\XmlStrategy;

/**
 * Module.
 *
 * @license MIT
 *
 * @link    https://github.com/diegograssato/apigility-xm-lnegotiation
 * @since   1.0
 *
 * @author  Diego Pereira Grassato <diego.grassaot@gmail.com>
 */
class Module
{
    /**
     * Retrieve module configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__.'/../config/module.config.php';
    }

    /**
     * Retrieve Service Manager configuration.
     *
     * Defines Xml  Negotiation\XmlStrategy service factory.
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array('factories' => array(
            XmlRenderer::class => function ($services) {
                $helpers = $services->get('ViewHelperManager');
                $apiProblemRenderer = $services->get(ApiProblemRenderer::class);
                $config = $services->get('Config');

                $renderer = new View\XmlRenderer($apiProblemRenderer, $config);
                $renderer->setHelperPluginManager($helpers);

                return $renderer;
            },
            XmlStrategy::class => function ($services) {
                $renderer = $services->get(XmlRenderer::class);

                return new View\XmlStrategy($renderer);
            },
        ));
    }

    /**
     * Listener for bootstrap event.
     *
     * Attaches a render event.
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onBootstrap(MvcEvent $event)
    {
        $app = $event->getTarget();
        $services = $app->getServiceManager();
        $events = $app->getEventManager();

        $events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'), 100);
        $events->attach(MvcEvent::EVENT_ROUTE, $services->get(XmlContentTypeListener::class), -626);
    }

    /**
     * Listener for the render event.
     *
     * Attaches a rendering/response strategy to the View.
     *
     * @param \Zend\Mvc\MvcEvent $event
     */
    public function onRender(MvcEvent $event)
    {
        $result = $event->getResult();

        if (!$result instanceof View\XmlModel) {
            return;
        }

        $app = $event->getTarget();
        $services = $app->getServiceManager();
        if ($services->has('View')) {
            $view = $services->get('View');
            $events = $view->getEventManager();

            // register at high priority, to "beat" normal json strategy registered
            // via view manager, as well as HAL strategy.
            $services->get(XmlStrategy::class)->attach($events, 100);
        }
    }
}
