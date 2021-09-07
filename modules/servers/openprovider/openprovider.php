<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

const DISPLAY_NAME = 'Openprovider Plesk';

const LICENSE_TYPE_CONFIGURATION_NAME = 'License Type';
const LICENSE_PERIOD_CONFIGURATION_NAME = 'License period (months)';

const ERROR_API_CLIENT_NOT_CONFIGURED = 'Credentials are incorrect or api is not configured!';

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related abilities and
 * settings.
 *
 * @see https://developers.whmcs.com/provisioning-modules/meta-data-params/
 *
 * @return array
 */
function openprovider_MetaData()
{
    return [
        'DisplayName' => DISPLAY_NAME,
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => false, // Set true if module requires a server to work
        'DefaultNonSSLPort' => '1111', // Default Non-SSL Connection Port
        'DefaultSSLPort' => '1112', // Default SSL Connection Port
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    ];
}

function openprovider_ConfigOptions()
{
    return [
        'license' => [
            'FriendlyName' => LICENSE_TYPE_CONFIGURATION_NAME,
            'Type' => 'text',
            'SimpleMode' => true,
        ],
        'period' => [
            'FriendlyName' => LICENSE_PERIOD_CONFIGURATION_NAME,
            'Type' => 'dropdown',
            'SimpleMode' => true,
            'Options' => [1 => 1, 12 => 12, 24 => 24],
        ],
    ];
}

function openprovider_CreateAccount($params)
{
    $licenseType = $params['configoption1'];
    $period = $params['configoption2'] ?? 1;

    $api = getPleskApi();

    if (is_null($api)) {
        return ERROR_API_CLIENT_NOT_CONFIGURED;
    }

    $argsCreatePleskLicense = [
        'items' => [
            $licenseType
        ],
        'period' => $period,
    ];

    $createPleskLicenseResponse = makeApiCall($api, 'createPleskLicenseRequest', $argsCreatePleskLicense);

    // I don't know why, but it works
    sleep(1);

    if ($createPleskLicenseResponse->getCode() != 0) {
        return $createPleskLicenseResponse->getMessage();
    }

    $pleskLicenseKeyId = $createPleskLicenseResponse->getData()['key_id'];

    $getPleskLicenseResponse = makeApiCall($api, 'retrievePleskLicenseRequest', [
        'key_id' => $pleskLicenseKeyId
    ]);

    if ($getPleskLicenseResponse->getCode() != 0) {
        return $getPleskLicenseResponse->getMessage();
    }

    $pleskLicense = $getPleskLicenseResponse->getData();

    $licenseNumber = $pleskLicense['key_number'];
    $activationCode = $pleskLicense['activation_code'];

    try {
        $customFieldNames = getCustomFieldNames();

        $params['model']->serviceProperties->save([$customFieldNames[0] => $licenseNumber]);
        $params['model']->serviceProperties->save([$customFieldNames[1] => $activationCode]);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

/**
 * @param array $params available parameters from whmcs
 *
 * @return string|void HTML template for op-plesk product and product addon
 */
function openprovider_ClientArea($params)
{
    $customFieldNames = array_keys($params['customfields']);

    $licenseNumber = $params['model']->serviceProperties->get($customFieldNames[0]);
    $activationCode = $params['model']->serviceProperties->get($customFieldNames[1]);

    return getHtmlTemplateFieldsForProductAddon($customFieldNames[0], $licenseNumber, $customFieldNames[1], $activationCode);
}
