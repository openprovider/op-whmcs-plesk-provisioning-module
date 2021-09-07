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
function getPleskApi(): ?OpenProviderApi
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
 * @param array $args arguments for api call
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
 * @return array|string[] [
 *                          0 => 'license number name',
 *                          1 => 'activation code name'
 *                        ]
 */
function getCustomFieldNames(): array
{
    $configs = getConfigs();

    return [
        $configs['service_custom_fields_0'] ?? 'License Number',
        $configs['service_custom_fields_1'] ?? 'Activation Code',
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
 * Return HTML template to render on product and product addon page
 *
 * @param string $licenseNumberLabel label for license number
 * @param string $licenseNumber license number
 * @param string $activationCodeLabel label for activation code
 * @param string $activationCode activation code
 *
 * @return string
 */
function getHtmlTemplateFieldsForProductAddon(
    string $licenseNumberLabel,
    string $licenseNumber,
    string $activationCodeLabel,
    string $activationCode
): string
{
    return sprintf('
<div class="tab-content bg-white product-details-tab-container">
<div class="tab-pane fade text-center active show" role="tabpanel" id="additionalinfo">
<div class="row">
<div class="col-sm-5">
<strong>%s</strong>
</div>
<div class="col-sm-7 text-left">
%s
</div>
</div>
<div class="row">
<div class="col-sm-5">
<strong>%s</strong>
</div>
<div class="col-sm-7 text-left">
%s
</div>
</div>
</div>
</div>
    ', $licenseNumberLabel, $licenseNumber, $activationCodeLabel, $activationCode);
}
