<?php

namespace Akrabat\ApigilityXml\Serializer\Adapter;

use DOMDocument;
use SimpleXMLElement;
use Zend\Json\Json;
use Zend\Serializer\Exception;
use Zend\Serializer\Adapter\AbstractAdapter;

/**
 * Class Xml
 */
class Xml extends AbstractAdapter
{
    const DEFAULT_XML_NODE = 'item';

    /**
     * @var XmlOptions
     */
    protected $options = null;

    /**
     * Set options.
     *
     * @param array|\Traversable|XmlOptions $options
     *
     * @return Xml
     */
    public function setOptions($options)
    {
        if (!$options instanceof XmlOptions) {
            $options = new XmlOptions($options);
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Get options.
     *
     * @return XmlOptions
     */
    public function getOptions()
    {
        if ($this->options === null) {
            $this->options = new XmlOptions();
        }

        return $this->options;
    }

    /**
     * Serialize PHP value to XML.
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function serialize($value)
    {
        $options = $this->getOptions();

        try {
            $dom = new DOMDocument($options->getXmlVersion(), $options->getEncoding());
            $dom->xmlStandalone = $options->isStandalone();
            $root = $dom->appendChild($dom->createElement($options->getRootNode()));
            $this->createNodes($dom, $value, $root);

            return $dom->saveXml();
        } catch (\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException('Serialization failed: '.$e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new Exception\RuntimeException('Serialization failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Deserialize XML to PHP value.
     *
     * @param string $xml
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function unserialize($xml)
    {
        try {
            $json = Json::fromXml($xml);
            $unserialized = Json::decode($json, Json::TYPE_OBJECT);
        } catch (\InvalidArgumentException $e) {
            throw new Exception\InvalidArgumentException('Unserialization failed: '.$e->getMessage(), 0, $e);
        } catch (\Exception $e) {
            throw new Exception\RuntimeException('Unserialization failed: '.$e->getMessage(), 0, $e);
        }

        return $unserialized;
    }

    /**
     * @param DomDocument $dom
     * @param $data
     * @param DomDocument $parent
     */
    private function createNodes($dom, $data, &$parent)
    {
        switch (gettype($data)) {
            case 'string':
            case 'integer':
            case 'double':
                $parent->appendChild($dom->createTextNode($data));
                break;

            case 'boolean':
                switch ($data) {
                    case true:
                        $value = 'true';
                        break;

                    case false:
                        $value = 'false';
                        break;
                }

                $parent->appendChild($dom->createTextNode($value));
                break;

            case 'object':
            case 'array':
                foreach ($data as $key => $value) {
                    if (is_object($value) and $value instanceof DOMDocument and !empty($value->firstChild)) {
                        $node = $dom->importNode($value->firstChild, true);
                        $parent->appendChild($node);
                    } else {
                        $attributes = null;

                        // SimpleXMLElements can contain key with @attribute as the key name
                        // which indicates an associated array that should be applied to the xml element

                        if (is_object($value) and $value instanceof SimpleXMLElement) {
                            $attributes = $value->attributes();
                            $value = (array) $value;
                        }

                        // don't emit @attribute as an element of it's own
                        if ($key[0] !== '@') {
                            if (gettype($value) == 'array' and !is_numeric($key)) {
                                $child = $parent->appendChild($dom->createElement($key));

                                if ($attributes) {
                                    foreach ($attributes as $attrKey => $attrValue) {
                                        $child->setAttribute($attrKey, $attrValue);
                                    }
                                }

                                $this->createNodes($dom, $value, $child);
                            } else {
                                if (is_numeric($key)) {
                                    $key = self::DEFAULT_XML_NODE;
                                }

                                $child = $parent->appendChild($dom->createElement($key));

                                if ($attributes) {
                                    foreach ($attributes as $attrKey => $attrValue) {
                                        $child->setAttribute($attrKey, $attrValue);
                                    }
                                }

                                $this->createNodes($dom, $value, $child);
                            }
                        } else {
                            $parent->setAttribute(substr($key, 1), $value);
                        }
                    }
                }

                break;
        }
    }
}
