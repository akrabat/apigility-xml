# ApigilityXml

This is an Apigility module to provide XML content-negotiation features. It allows for the rendering of payloads in XML and HAL+XML formats. It also supports ingesting HTTP bodies that are in XML format if the content-type is `application/xml`

The response type is based on *Accept* header:

* `application/xml` (or `application/*+xml`) renders content in XML
* `application/hal+json` (or `application/*+json`) renders content in HalJson. 


### Installation

1. Install the module using composer:

        $ composer require akrabat/apigility-xml

2. Add `Akrabat\ApigilityXml` to `modules.config.php`:

    	return [
        	...,
            'Akrabat\ApigilityXml',
            ....
    	]

3. In the Apigility admin, select your API and change *Content Negotiation Selector* to **HalJsonXML**
4. Add `application/xml` to *Accept whitelist* and *Content-Type whitelist*. Add other headers if needed.
5. Save configuration



### Credits

This module is a fork of https://github.com/diegograssato/apigility-xml-negotiation
which appears to be a fork of https://github.com/zpetr/apigility-xmlnegotiation which
was inspired by the https://github.com/markushausammann's ApigilityXml.

Thanks to everyone who came before me!
