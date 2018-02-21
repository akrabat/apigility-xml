<?php

namespace Akrabat\ApigilityXml\View;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\View\ViewEvent;

/**
 * Class XmlStrategy
 */
class XmlStrategy extends AbstractListenerAggregate
{
    /**
     * Character set for associated content-type.
     *
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * @var XmlRenderer
     */
    protected $renderer;

    /**
     * Constructor.
     *
     * @param XmlRenderer $renderer
     */
    public function __construct(XmlRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, array($this, 'selectRenderer'), $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, array($this, 'injectResponse'), $priority);
    }

    /**
     * Set the content-type character set.
     *
     * @param string $charset
     *
     * @return XmlStrategy
     */
    public function setCharset($charset)
    {
        $this->charset = (string) $charset;

        return $this;
    }

    /**
     * Retrieve the current character set.
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Detect if we should use the XmlRenderer based on model type.
     *
     * @param ViewEvent $e
     *
     * @return null|XmlRenderer
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if (!$model instanceof XmlModel) {
            // no XmlModel; do nothing
            return null;
        }

        // XmlModel found
        return $this->renderer;
    }

    /**
     * Inject the response with the XML payload and appropriate Content-Type header.
     *
     * @param ViewEvent $e
     */
    public function injectResponse(ViewEvent $e)
    {
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        $result = $e->getResult();
        if (!is_string($result)) {
            // We don't have a string, and thus, no XML
            return;
        }

        // Populate response
        /** @var \Zend\Http\Response $response */
        $response = $e->getResponse();
        $response->setContent($result);
        $headers = $response->getHeaders();

        $contentType = 'application/xml';

        $contentType .= '; charset='.$this->charset;
        $headers->addHeaderLine('content-type', $contentType);
    }
}
