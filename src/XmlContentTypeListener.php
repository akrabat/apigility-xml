<?php
namespace Akrabat\ApigilityXml;

use Exception;
use Zend\Json\Json;
use Zend\Mvc\MvcEvent;
use Zend\Xml2Json\Xml2Json;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZF\ContentNegotiation\ParameterDataContainer;

class XmlContentTypeListener
{
    /**
     * Perform content negotiation for XML content types
     *
     * For HTTP methods expecting body content, attempts to match the incoming
     * content-type against the list of allowed content types, and then performs
     * appropriate content deserialization.
     *
     * If an error occurs during deserialization, an ApiProblemResponse is
     * returned, indicating an issue with the submission.
     *
     * @param  MvcEvent $e
     * @return null|ApiProblemResponse
     */
    public function __invoke(MvcEvent $e)
    {
        $request = $e->getRequest();
        if (! method_exists($request, 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return;
        }

        $contentType = $request->getHeader('Content-Type');
        if (!$contentType || !$contentType->match('application/xml')) {
            // Not XML; nothing to do
            return;
        }

        $routeMatch    = $e->getRouteMatch();
        $parameterData = new ParameterDataContainer();

        // route parameters:
        $routeParams = $routeMatch->getParams();
        $parameterData->setRouteParams($routeParams);

        // query parameters:
        $parameterData->setQueryParams($request->getQuery()->toArray());

        // body parameters:
        $bodyParams  = [];
        switch ($request->getMethod()) {
            case $request::METHOD_POST:
            case $request::METHOD_PATCH:
            case $request::METHOD_PUT:
            case $request::METHOD_DELETE:
                $bodyParams = $this->decodeXml($request->getContent());
                break;
            default:
                break;
        }

        if ($bodyParams instanceof ApiProblemResponse) {
            return $bodyParams;
        }

        $parameterData->setBodyParams($bodyParams);
        $e->setParam('ZFContentNegotiationParameterData', $parameterData);
    }


    /**
     * Attempt to decode an XML string
     *
     * Decodes an XML string and returns it; if invalid, returns
     * an ApiProblemResponse.
     *
     * @param  string $xml
     * @return mixed|ApiProblemResponse
     */
    public function decodeXml($xml)
    {
        $xml = trim($xml);

        // If the data is empty, return an empty array
        if (empty($xml)) {
            return [];
        }

        try {
            $json = Xml2Json::fromXml($xml);
            $data = Json::decode($json, Json::TYPE_ARRAY);
            if (!is_array($data)) {
                return new ApiProblemResponse(
                    new ApiProblem(400, 'Unknown XML decoding error')
                );
            }

            // Remove the top level tag from the data array
            $data = array_shift($data);

            // Decode 'application/hal+xml' to 'application/xml' by merging _embedded into the array
            if (isset($data['_embedded'])) {
                foreach ($data['_embedded'] as $key => $value) {
                    $data[$key] = $value;
                }
                unset($data['_embedded']);
            }
        } catch (Exception $e) {
            return new ApiProblemResponse(
                new ApiProblem(400, sprintf('XML decoding error: %s', $e->getMessage()))
            );
        }

        return $data;
    }
}
