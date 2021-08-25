<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'openprovider_api.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'response.php';


/**
 * This method takes OP credentials from configs.php file.
 * Then it makes request to OP to get token.
 * If token is correct it returns Openprovider api client.
 * Else it returns null.
 *
 * @return OpenProviderApi|null
 *
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 */
function getApi(): ?OpenProviderApi
{
    $api = new OpenProviderApi();

    $api->getConfig()->setHost(OpenProviderApi::API_URL);

    $credentials = getCredentials();

    if (!empty($credentials['token'])) {
        $api->getConfig()->setToken($credentials['token']);

        return $api;
    }

    $tokenRequest = makeApiCall($api, 'generateAuthTokenRequest', getCredentials());

    if ($tokenRequest->getCode() != 0) {
        return null;
    }

    $token = $tokenRequest->getData()['token'];

    $api->getConfig()->setToken($token);

    return $api;
}

/**
 * @param OpenProviderApi $apiClient configured api client
 * @param string $cmd api command
 * @param array $args
 *
 * @return Response response data.
 */
function makeApiCall(OpenProviderApi $apiClient, string $cmd, array $args = []): Response
{
    $apiResponse = $apiClient->call($cmd, $args);

    logApiCall($apiClient);

    return $apiResponse;
}

/**
 * @param OpenProviderApi $apiClient
 *
 * @return void
 */
function logApiCall(OpenProviderApi $apiClient): void
{
    \logModuleCall(
        DISPLAY_NAME,
        $apiClient->getLastRequest()->getCommand(),
        json_encode($apiClient->getLastRequest()->getArgs()),
        json_encode([
            'code' => $apiClient->getLastResponse()->getCode(),
            'message' => $apiClient->getLastResponse()->getMessage(),
            'data' => $apiClient->getLastResponse()->getData(),
        ]),
        null,
        isset($apiClient->getLastRequest()->getArgs()['password']) ? [
            $apiClient->getLastRequest()->getArgs()['password'],
            htmlentities($apiClient->getLastRequest()->getArgs()['password'])
        ] : []
    );
}

/**
 * You should configure your credentials before
 *
 * @return array ['username' => Openprovider username, 'password' => openprovider password]
 */
function getCredentials(): array
{
    $configs = getConfigs();

    $username = $configs['username'] ?? '';
    $password = $configs['password'] ?? '';
    $token    = $configs['token'] ?? '';

    return [
        'username' => $username,
        'password' => $password,
        'token'    => $token
    ];
}

/**
 * @return array with configs from configs.php file
 */
function getConfigs(): array
{
    $configsFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'configs.php';

    if ($configs = include $configsFilePath) {
        return $configs;
    }

    return [];
}

/**
 * Print data in browser console
 *
 * @param mixed $data
 */
function consolePrint($data)
{
    $data = json_encode($data);

    echo "
        <script>
            console.log({$data}); 
        </script>
    ";
}
