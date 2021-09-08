<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'openprovider_api.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'response.php';

/**
 * It returns Openprovider api client or null if credentials are incorrect.
 *
 * @param string $username OP username
 * @param string $password OP password
 *
 * @return OpenproviderApi|null
 *
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 */
function getPleskApi(string $username, string $password): ?OpenproviderApi
{
    $api = new OpenproviderApi();
    $api->getConfig()->setHost(OpenproviderApi::API_CTE_URL);

    $tokenRequest = makeApiCall($api, 'generateAuthTokenRequest', [
        'username' => $username,
        'password' => $password,
    ]);

    if ($tokenRequest->getCode() != 0) {
        return null;
    }

    $token = $tokenRequest->getData()['token'];

    $api->getConfig()->setToken($token);

    return $api;
}

/**
 * @param OpenproviderApi $apiClient configured api client
 * @param string $cmd api command
 * @param array $args arguments for api call
 *
 * @return Response response data.
 */
function makeApiCall(OpenproviderApi $apiClient, string $cmd, array $args = []): Response
{
    $apiResponse = $apiClient->call($cmd, $args);

    logApiCall($apiClient);

    return $apiResponse;
}

/**
 * @param OpenproviderApi $apiClient
 *
 * @return void
 */
function logApiCall(OpenproviderApi $apiClient): void
{
    \logModuleCall(
        META_DISPLAY_NAME,
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
 * @return array|string[] [
 *                          'license_number' => 'license number name',
 *                          'activation_code' => 'activation code name'
 *                        ]
 */
function getCustomFieldNames(): array
{
    $configs = getConfigs();

    return [
        'license_number' => $configs['service_custom_fields_0'] ?? 'License Number',
        'activation_code' => $configs['service_custom_fields_1'] ?? 'Activation Code',
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
    return <<<HTML
<div class="tab-content bg-white product-details-tab-container">
<div class="tab-pane fade text-center active show" role="tabpanel" id="additionalinfo">
<div class="row">
<div class="col-sm-5">
<strong>$licenseNumberLabel</strong>
</div>
<div class="col-sm-7 text-left">
$licenseNumber
</div>
</div>
<div class="row">
<div class="col-sm-5">
<strong>$activationCodeLabel</strong>
</div>
<div class="col-sm-7 text-left">
$activationCode
</div>
</div>
</div>
</div>
HTML;
}
