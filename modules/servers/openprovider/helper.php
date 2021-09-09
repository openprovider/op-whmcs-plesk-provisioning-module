<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'openprovider_api.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'response.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'command_names.php';

class OpenproviderPleskModuleHelper
{
    /**
     * @var OpenproviderApi|null
     */
    private $api;

    /**
     * @var array module configuration
     */
    private $configs;

    public function __construct()
    {
        $this->configs = $this->loadConfigs();
    }

    /**
     * The method inits Openprovider api client.
     * It returns true if client was successfully initialized.
     *
     * @param string $username OP username
     * @param string $password OP password
     *
     * @return bool
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function initApi(string $username, string $password)
    {
        $this->api = new OpenproviderApi();
        $this->api->getConfig()->setHost(OpenproviderApi::API_CTE_URL);

        $tokenRequest = $this->call(ApiCommandNames::GENERATE_AUTH_TOKEN_REQUEST, [
            'username' => $username,
            'password' => $password,
        ]);

        if ($tokenRequest->getCode() != 0) {
            return false;
        }

        $token = $tokenRequest->getData()['token'];

        $this->api->getConfig()->setToken($token);

        return true;
    }

    /**
     * Method load configs from configs.php file.
     *
     * @return array
     */
    public function loadConfigs(): array
    {
        $configsFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'configs.json';

        if ($configs = file_get_contents($configsFilePath)) {
            return (array) json_decode($configs);
        }

        return [];
    }

    /**
     * Method makes api request to OP.
     *
     * @param string $cmd api command
     * @param array $args arguments for api call
     *
     * @return Response
     */
    public function call(string $cmd, array $args = []): Response
    {
        $apiResponse = $this->api->call($cmd, $args);

        $this->logApiCall();

        return $apiResponse;
    }

    /**
     * Return Custom field names from config.php file
     *
     * @return array|string[] [
     *                          'license_number' => 'license number name',
     *                          'activation_code' => 'activation code name'
     *                        ]
     */
    public function getCustomFieldNames(): array
    {
        return [
            'license_number' => $this->configs['service_custom_fields_0'] ?? 'License Number',
            'activation_code' => $this->configs['service_custom_fields_1'] ?? 'Activation Code',
        ];
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
    public function getHtmlTemplateFieldsForProductAddon(
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

    /**
     * Log api calls
     *
     * @return void
     */
    private function logApiCall(): void
    {
        \logModuleCall(
            META_DISPLAY_NAME,
            $this->api->getLastRequest()->getCommand(),
            json_encode($this->api->getLastRequest()->getArgs()),
            json_encode([
                'code' => $this->api->getLastResponse()->getCode(),
                'message' => $this->api->getLastResponse()->getMessage(),
                'data' => $this->api->getLastResponse()->getData(),
            ]),
            null,
            isset($this->api->getLastRequest()->getArgs()['password']) ? [
                $this->api->getLastRequest()->getArgs()['password'],
                htmlentities($this->api->getLastRequest()->getArgs()['password'])
            ] : []
        );
    }
}
