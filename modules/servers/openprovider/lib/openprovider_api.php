<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'response.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'last_request.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'command_mapping.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'api_configuration.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'params_builder/params_creator_factory.php';

use Openprovider\Api\Rest\Client\Base\Configuration;
use GuzzleHttp6\Client as HttpClient;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class OpenProviderApi
{
    const API_CLIENT_NAME = 'blesta';
    const API_URL = 'https://api.openprovider.eu';
    const API_CTE_URL = 'https://api.cte.openprovider.eu';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var HttpClient
     */
    private $http_client;

    /**
     * @var CommandMapping
     */
    private $command_mapping;

    /**
     * @var ApiConfig
     */
    private $api_config;

    /**
     * @var ParamsCreatorFactory
     */
    private $params_creator_factory;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Response
     */
    private $last_response;

    /**
     * @var LastRequest
     */
    private $last_request;

    public function __construct()
    {
        $this->configuration = new Configuration();
        $this->command_mapping = new CommandMapping();
        $this->api_config = new ApiConfig();
        $this->params_creator_factory = new ParamsCreatorFactory();
        $this->serializer = new Serializer([new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter())]);
        $this->http_client = new HttpClient([
            'headers' => [
                'X-Client' => self::API_CLIENT_NAME
            ]
        ]);
    }

    /**
     * @param string $cmd
     * @param array $args
     *
     * @return Response
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function call(string $cmd, array $args = []): Response
    {
        $response = new Response();

        try {
            $apiClass = $this->command_mapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_CLASS);
            $apiMethod = $this->command_mapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_METHOD);
        } catch (\Exception $e) {
            return $this->failedResponse($response, $e->getMessage(), $e->getCode());
        }

        $service = new $apiClass($this->http_client, $this->configuration);

        $service->getConfig()->setHost($this->api_config->getHost());

        if ($this->api_config->getToken()) {
            $service->getConfig()->setAccessToken($this->api_config->getToken());
        }

        $this->last_request = new LastRequest();
        $this->last_request->setArgs($args);
        $this->last_request->setCommand($cmd);

        try {
            $paramsCreator = $this->params_creator_factory->build($cmd);
            $requestParameters = $paramsCreator->createParameters($args, $service, $apiMethod);
            $reply = $service->$apiMethod(...$requestParameters);
        } catch (\Exception $e) {
            $responseData = $this->serializer->normalize(
                    json_decode(substr($e->getMessage(), strpos($e->getMessage(), 'response:') + strlen('response:')))
                ) ?? $e->getMessage();

            $return = $this->failedResponse(
                $response,
                $responseData['desc'] ?? $e->getMessage(),
                $responseData['code'] ?? $e->getCode()
            );
            $this->last_response = $return;

            return $return;
        }

        $data = $this->serializer->normalize($reply->getData());

        $return = $this->successResponse($response, $data);
        $this->last_response = $return;

        return $return;
    }

    /**
     * @return ApiConfig
     */
    public function getConfig(): ApiConfig
    {
        return $this->api_config;
    }

    /**
     * @param Response $response
     * @param array $data
     *
     * @return Response
     */
    private function successResponse(Response $response, array $data): Response
    {
        $response->setTotal($data['total'] ?? 0);
        unset($data['total']);

        $response->setCode($data['code'] ?? 0);
        unset($data['code']);

        $response->setData($data);

        return $response;
    }

    /**
     * @param Response $response
     * @param string $message
     * @param int $code
     *
     * @return Response
     */
    private function failedResponse(Response $response, string $message, int $code): Response
    {
        $response->setMessage($message);
        $response->setCode($code);

        return $response;
    }

    /**
     * @return LastRequest
     */
    public function getLastRequest(): LastRequest
    {
        return $this->last_request;
    }

    /**
     * @return Response
     */
    public function getLastResponse(): Response
    {
        return $this->last_response;
    }
}
