<?php

namespace Akrabat\ApigilityXml\View;

use Akrabat\ApigilityXml\Serializer\Adapter\Xml as XmlSerializer;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\ViewModel as BaseViewModel;
use ZF\Hal\Collection;
use ZF\Hal\Entity;

/**
 * Class XmlModel
 */
class XmlModel extends BaseViewModel
{
    /**
     * XML probably won't need to be captured into
     * a parent container by default.
     *
     * @var string
     */
    protected $captureTo = 'content';

    /**
     * XML is usually terminal.
     *
     * @var bool
     */
    protected $terminate = true;

    /**
     * Does the payload represent a HAL collection?
     *
     * @return bool
     */
    public function isCollection()
    {
        $payload = $this->getPayload();

        return ($payload instanceof Collection);
    }

    /**
     * Does the payload represent a HAL item?
     *
     * Deprecated; please use isEntity().
     *
     * @deprecated
     *
     * @return bool
     */
    public function isResource()
    {
        trigger_error(sprintf('%s is deprecated; please use %s::isEntity', __METHOD__, __CLASS__), E_USER_DEPRECATED);

        return self::isEntity();
    }

    /**
     * Does the payload represent a HAL entity?
     *
     * @return bool
     */
    public function isEntity()
    {
        $payload = $this->getPayload();

        return $payload instanceof Entity;
    }

    /**
     * Set the payload for the response.
     *
     * This is the value to represent in the response.
     *
     * @param mixed $payload
     *
     * @return self
     */
    public function setPayload($payload)
    {
        $this->setVariable('payload', $payload);

        return $this;
    }

    /**
     * Retrieve the payload for the response.
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->getVariable('payload');
    }

    /**
     * Override setTerminal().
     *
     * Does nothing; does not allow re-setting "terminate" flag.
     *
     * @param bool $flag
     *
     * @return self
     */
    public function setTerminal($flag = true)
    {
        return $this;
    }

    /**
     * Serialize to XML.
     *
     * @return string
     */
    public function serialize()
    {
        $variables = $this->getVariables();
        $output = '';

        if ($variables instanceof Traversable) {
            $variables = ArrayUtils::iteratorToArray($variables);
        }

        $payload = $variables['payload'];

        if ($payload instanceof Collection) {
            $object = $payload->getCollection();
            $data = is_array($object) ? $object : $object->getCurrentItems();
            $pages = is_array($object) ? array() : $object->getPages();
            $output = array('type' => 'collection', 'data' => $data, 'pages' => $pages);
        } elseif ($payload instanceof Entity) {
            $object = $payload->entity;
            $output = array('type' => 'entity', 'data' => $object);
        } elseif (is_array($payload)) {
            $output = ArrayUtils::iteratorToArray($payload);
        }

        $serializer = new XmlSerializer();
        $xml = $serializer->serialize($output);

        return $xml;
    }
}
