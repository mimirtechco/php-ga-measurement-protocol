<?php

namespace TheIconic\Tracking\GoogleAnalytics\Network;


use Http\Discovery\MessageFactoryDiscovery;
use TheIconic\Tracking\GoogleAnalytics\AnalyticsResponse;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Message\RequestFactory;
use Http\Promise\Promise;

use React\Http\Browser;
use React\EventLoop\Factory;

/**
 * Class HttpClient
 *
 * @package TheIconic\Tracking\GoogleAnalytics
 */
class HttpClient
{
    /**
     * User agent for the client.
     */
    const PHP_GA_MEASUREMENT_PROTOCOL_USER_AGENT =
        'THE ICONIC GA Measurement Protocol PHP Client (https://github.com/theiconic/php-ga-measurement-protocol)';

    /**
     * Timeout in seconds for the request connection and actual request execution.
     * Using the same value you can find in Google's PHP Client.
     */
    const REQUEST_TIMEOUT_SECONDS = 100;

    /**
     * HTTP client.
     *
     * @var Browser
     */
    private $client;

    /**
     * @var Factory
     */
    private $requestFactory = null;

    /**
     * Holds the promises (async responses).
     *
     * @var Promise[]
     */
    private static $promises = [];

    /**
     * Sets HTTP client.
     *
     * @internal
     * @param Browser $client
     */
    public function setClient(Browser $client)
    {
        $this->client = $client;
    }

    /**
     * Gets HTTP client for internal class use.
     *
     * @return Browser
     *
     * @throws \Http\Discovery\Exception\NotFoundException
     */
    private function getClient()
    {
        if ($this->client === null) {
            // @codeCoverageIgnoreStart
            //$loop = \React\EventLoop\Factory::create();
            $this->setClient(new Browser($this->getRequestFactory()));
        }
        // @codeCoverageIgnoreEnd

        return $this->client;
    }

    /**
     * Sets HTTP request factory.
     *
     * @param $requestFactory
     *
     * @internal
     */
    public function setRequestFactory($requestFactory)
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return Factory|null
     *
     * @throws \Http\Discovery\Exception\NotFoundException
     */
    private function getRequestFactory()
    {
        if (null === $this->requestFactory) {
            $this->setRequestFactory(Factory::create());
        }

        return $this->requestFactory;
    }

    /**
     * Sends request to Google Analytics.
     *
     * @internal
     * @param string $url
     * @param array $options
     * @return AnalyticsResponse
     *
     * @throws \Exception If processing the request is impossible (eg. bad configuration).
     * @throws \Http\Discovery\Exception\NotFoundException
     */
    public function post($url, array $options = [])
    {

        return $this->sendRequest($url, $options);
    }

    /**
     * Sends batch request to Google Analytics.
     *
     * @internal
     * @param string $url
     * @param array $batchUrls
     * @param array $options
     * @return AnalyticsResponse
     */
    public function batch($url, array $batchUrls, array $options = [])
    {
        $body = implode(PHP_EOL, $batchUrls);

        $request = $this->getRequestFactory()->createReques(
            'POST',
            $url,
            ['User-Agent' => self::PHP_GA_MEASUREMENT_PROTOCOL_USER_AGENT],
            $body
        );

        return $this->sendRequest($request, $options);
    }

    private function sendRequest($request, array $options = [])
    {
        $opts = $this->parseOptions($options);
        $response = $this->getClient()->get( $request )->then(
          function ($request, ResponseInterface $response) {
            return $this->getAnalyticsResponse($request, $response);
          });

          $this->getRequestFactory()->run();

    }

    /**
     * Parse the given options and fill missing fields with default values.
     *
     * @param array $options
     * @return array
     */
    private function parseOptions(array $options)
    {
        $defaultOptions = [
            'timeout' => static::REQUEST_TIMEOUT_SECONDS,
            'async' => false,
        ];

        $opts = [];
        foreach ($defaultOptions as $option => $value) {
            $opts[$option] = isset($options[$option]) ? $options[$option] : $defaultOptions[$option];
        }

        if (!is_int($opts['timeout']) || $opts['timeout'] <= 0) {
            throw new \UnexpectedValueException('The timeout must be an integer with a value greater than 0');
        }

        if (!is_bool($opts['async'])) {
            throw new \UnexpectedValueException('The async option must be boolean');
        }

        return $opts;
    }

    /**
     * Creates an analytics response object.
     *
     * @param RequestInterface $request
     * @param ResponseInterface|PromiseInterface $response
     * @return AnalyticsResponse
     */
    protected function getAnalyticsResponse(RequestInterface $request, $response)
    {
        return new AnalyticsResponse($request, $response);
    }
}
