# Distributed Tracing using Zipkin in Lumen framework

[![Build Status](https://travis-ci.org/dipenduroy/lumenzipkin.svg?branch=master)](https://travis-ci.org/jcchavezs/zipkin-instrumentation-guzzle)
[![Latest Stable Version](https://poser.pugx.org/dipenduroy/lumenzipkin/v/stable)](https://packagist.org/packages/dipenduroy/lumenzipkin)
[![Total Downloads](https://poser.pugx.org/dipenduroy/lumenzipkin/downloads)](https://packagist.org/packages/dipenduroy/lumenzipkin)
[![License](https://poser.pugx.org/dipenduroy/lumenzipkin/license)](https://packagist.org/packages/dipenduroy/lumenzipkin)

Distributed Tracing of Guzzle HTTP Client and Micro Services in Lumen

## Install

```bash
composer require dipenduroy/lumenzipkin
```

### Register LumenZipkinServiceProvider

Add below in bootstrap/app.php

```
$app->register(DipenduRoy\LumenZipkin\LumenZipkinServiceProvider::class);
```
