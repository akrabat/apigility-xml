<?php

use Akrabat\ApigilityXml\View\XmlModel;
use Akrabat\ApigilityXml\XmlContentTypeListener;
use Zend\ServiceManager\Factory\InvokableFactory;
use ZF\ContentNegotiation\JsonModel;

return [
    'service_manager' => [
        'factories' => [
            XmlContentTypeListener::class => InvokableFactory::class,
        ],
    ],
    'zf-content-negotiation' => [
        'selectors' => [
            'HalJsonXML' => [
                XmlModel::class => [
                    'application/xml',
                    'application/*+xml',
                ],
                JsonModel::class => [
                    'application/json',
                    'application/*+json',
                ],
            ],
        ],
    ],
];
