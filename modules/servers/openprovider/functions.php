<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'openprovider_api.php';


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

    $api->getConfig()->setHost(OpenProviderApi::API_CTE_URL);

    $tokenRequest = $api->call('generateAuthTokenRequest', getCredentials());

    if ($tokenRequest->getCode() != 0) {
        return null;
    }

    $token = $tokenRequest->getData()['token'];

    $api->getConfig()->setToken($token);

    return $api;
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

    return [
        'username' => $username,
        'password' => $password,
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
