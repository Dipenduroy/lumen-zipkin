# Distributed Tracing using Zipkin in Lumen framework

[![Latest Stable Version](https://poser.pugx.org/dipenduroy/lumenzipkin/v/stable)](https://packagist.org/packages/dipenduroy/lumenzipkin)
[![Total Downloads](https://poser.pugx.org/dipenduroy/lumenzipkin/downloads)](https://packagist.org/packages/dipenduroy/lumenzipkin)
[![License](https://poser.pugx.org/dipenduroy/lumenzipkin/license)](https://packagist.org/packages/dipenduroy/lumenzipkin)

Distributed Tracing of Guzzle HTTP Client (Micro Services internal calls) and dynamic profiling in Lumen

## Contents
- [Distributed Tracing using Zipkin in Lumen framework](#Distributed-Tracing-using-Zipkin-in-Lumen-framework)
  * [Prerequisites](#prerequisites)
  * [Install](#install)
  * [Register LumenZipkinServiceProvider](#Register-LumenZipkinServiceProvider)
  * [Register ZipkinTraceMiddleware](#Register-ZipkinTraceMiddleware)
  * [Start Zipkin Server](#Start-Zipkin-Server)
  * [Usage](#Usage)
  * [Configuration](#Configuration)
  * [Running with a custom zipkin location](#Running-with-a-custom-zipkin-location)
  * [References](#references)
  
  
## Prerequisites
- [Composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- [Docker](https://docs.docker.com/engine/installation/) (optional, if you have a zipkin endpoint this is not needed)

## Install

```bash
composer require dipenduroy/lumenzipkin
```

## Register LumenZipkinServiceProvider

To enable Lumen Zipkin library, add below in bootstrap/app.php

```
$app->register(DipenduRoy\LumenZipkin\LumenZipkinServiceProvider::class);
```

## Register ZipkinTraceMiddleware 

To automatically flush all the traces to zipkin server while script ends. This is required to push trace info to zipkin server.

Add **DipenduRoy\LumenZipkin\ZipkinTraceMiddleware::class** in middleware's list of bootstrap/app.php, If no middleware

```
$app->middleware([
    DipenduRoy\LumenZipkin\ZipkinTraceMiddleware::class
]);
```

## Start Zipkin Server

Use Docker to start Zipkin Server

```bash
composer run-zipkin
```

## Usage

Below example traces all guzzle calls between microservices. Similarly refer [zipkin-php](https://github.com/openzipkin/zipkin-php#local-tracing) for more customized tracing

```
	//Set your application variables below
	$baseUri='http://local.app/';
	$method='POST';
	$requestUrl='example/url';
	$queryString='?filter=category';
	$formParams=[];
	
	//Get Zipkin Trace Object
	$ZipkinTrace=app('Trace\ZipkinTrace');
	
	/* Creates the span for getting the guzzle client call */
    $childSpan = $ZipkinTrace->createChildClientSpan($baseUri);
    
    //Tags can be configured as per your requirement for filtering the requests from zipkin dashboard
    $childSpan->tag(\Zipkin\Tags\HTTP_HOST, $baseUri);
    $childSpan->tag(\Zipkin\Tags\HTTP_URL, $baseUri.$requestUrl);
    $childSpan->tag(\Zipkin\Tags\HTTP_PATH, $requestUrl);
    $childSpan->tag(\Zipkin\Tags\HTTP_METHOD, strtoupper($method));
    
	$childSpan->annotate('request_started', \Zipkin\Timestamp\now());
    $client = new \GuzzleHttp\Client([
            'base_uri'  =>  $baseUri,
        ]);
    $headers['accept'] = "application/json";
    $headers=array_merge($headers,$ZipkinTrace->getHeaders());
    $params=[
            'form_params' => $formParams,
            'headers'     => $headers,
            'query' => $queryString,
        ];
    try {
        $response = $client->request($method, $requestUrl, $params);
    } catch (\GuzzleHttp\Exception\TransferException $e) {
        $response = $e->getResponse();
        if ($e->hasResponse()) {
            $responseString = $e->getResponse()->getBody(true);
        } else {
            $childSpan->tag(\Zipkin\Tags\HTTP_STATUS_CODE, 503);
            $childSpan->tag(\Zipkin\Tags\ERROR, 'Guzzle Transfer Exception');
            $childSpan->annotate('request_failed', \Zipkin\Timestamp\now());
        }
    }
    $childSpan->tag(\Zipkin\Tags\HTTP_STATUS_CODE, $response->getStatusCode());
    $childSpan->annotate('request_finished', \Zipkin\Timestamp\now());
```

## Configuration

Set below environment variable as required

**APP_NAME** Environment variable is used for root trace name

**LUMEN_ZIPKIN_ENABLE_SERVER_ADDRESS** Environment variable is used to enable server address if available

## Running with a custom zipkin location

If you need to pass the zipkin endpoint, just pass the reporter
url as `HTTP_REPORTER_URL` env variable. Default zipkin location is - **http://localhost:9411/api/v2/spans**

```bash
HTTP_REPORTER_URL=http://myzipkin:9411/api/v2/span composer run-frontend

```

## References
Thanks to the below contributors

1. This library uses Zipkin PHP Library, [refer for more details](https://github.com/openzipkin/zipkin-php)
2. Know Zipkin [in detail](https://zipkin.io)
3. [Zipkin PHP Example](https://github.com/openzipkin/zipkin-php-example)