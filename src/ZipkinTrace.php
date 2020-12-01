<?php
namespace DipenduRoy\LumenZipkin;

use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\Map;
use Zipkin\Timestamp;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

/**
 *
 * @author dipenduroy
 *        
 */
class ZipkinTrace
{

    private $tracing, $defaultSamplingFlags, $tracer, $createRootSpan;

    private $root_span, $current_span, $extractedContext;

    private $createdRootSpan = false;

    private $span_array = [];

    /**
     *
     * @param boolean $createRootSpan
     */
    public function __construct($createRootSpan = false)
    {
        $localServiceIPv4 = null;
        if (env('LUMEN_ZIPKIN_ENABLE_SERVER_ADDRESS')) {
            $localServiceIPv4 = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : null;
        }
        $this->tracing = self::create_tracing(env('APP_NAME'), $localServiceIPv4);
        $this->tracer = $this->tracing->getTracer();
        $this->createRootSpan = $createRootSpan;
        if ($this->createRootSpan && ! $this->createdRootSpan) {
            /* Always sample traces */
            $this->defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();
            $this->root_span = $this->current_span = $this->tracer->newTrace($this->defaultSamplingFlags);
            /* Creates the main span */
            $this->current_span->start(Timestamp\now());
        } else {
            $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
            $carrier = array_map(function ($header) {
                return $header[0];
            }, $request->headers->all());
            $extractor = $this->tracing->getPropagation()->getExtractor(new Map());
            $this->extractedContext = $extractor($carrier);
            /* Creates the extracted main span */
            $this->current_span = $this->tracer->nextSpan($this->extractedContext);
            $this->current_span->start();
        }
        $this->current_span->setKind(\Zipkin\Kind\SERVER);
        $this->current_span->setName('parse_request');
        $this->span_array[] = $this->current_span;
    }

    /**
     * create_tracing function is a handy function that allows you to create a tracing
     * component by just passing the local service information.
     * If you need to pass a
     * custom zipkin server URL use the HTTP_REPORTER_URL env var.
     */
    public static function create_tracing($localServiceName, $localServiceIPv4, $localServicePort = null)
    {
        $httpReporterURL = getenv('HTTP_REPORTER_URL');
        if ($httpReporterURL === false) {
            $httpReporterURL = 'http://localhost:9411/api/v2/spans';
        }

        $endpoint = Endpoint::create($localServiceName, $localServiceIPv4, null, $localServicePort);

        $reporter = new \Zipkin\Reporters\Http(\Zipkin\Reporters\Http\CurlFactory::create(), [
            'endpoint_url' => $httpReporterURL
        ]);
        $sampler = BinarySampler::createAsAlwaysSample();
        return TracingBuilder::create()->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
    }

    public function createChildClientSpan($name = 'guzzle_client_call')
    {
        if (count($this->span_array) > 1) {
            $childSpan = array_pop($this->span_array);
            $childSpan->finish();
        }
        $this->current_span = $this->tracer->newChild($this->current_span->getContext());
        $this->current_span->start();
        $this->current_span->setKind(\Zipkin\Kind\CLIENT);
        $this->current_span->setName($name);
        $this->span_array[] = $this->current_span;
        return $this->current_span;
    }

    public function getHeaders()
    {
        $headers = [];
        $injector = $this->tracing->getPropagation()->getInjector(new Map());
        $injector($this->current_span->getContext(), $headers);
        return $headers;
    }

    public function flushTracer($request, $response)
    {
        $IlluminateResponse = 'Illuminate\Http\Response';
        $SymfonyReponse = 'Symfony\Component\HttpFoundation\Response';

        while (! empty($this->span_array)) {
            $span = array_pop($this->span_array);
            if (count($this->span_array) == 0) {
                $span->tag(\Zipkin\Tags\HTTP_HOST, $request->getSchemeAndHttpHost());
                $span->tag(\Zipkin\Tags\HTTP_URL, $request->fullUrl());
                $span->tag(\Zipkin\Tags\HTTP_PATH, $request->path());
                $span->tag(\Zipkin\Tags\HTTP_METHOD, strtoupper($request->method()));
                if ($response instanceof $IlluminateResponse) {
                    $span->tag(\Zipkin\Tags\HTTP_STATUS_CODE, $response->status());
                } else if ($response instanceof $SymfonyReponse) {
                    $span->tag(\Zipkin\Tags\HTTP_STATUS_CODE, $response->getStatusCode());
                }
            }
            $span->finish();
        }
        $this->tracer->flush();
    }
}

