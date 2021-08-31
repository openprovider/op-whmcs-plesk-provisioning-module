<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

const DISPLAY_NAME = 'Openprovider Plesk';

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
            'FriendlyName' => 'License type',
            'Type' => 'text',
            'SimpleMode' => true,
        ],
        'forWhat' => [
            'FriendlyName' => 'Use for VPS or regular server',
            'Type' => 'dropdown',
            'SimpleMode' => true,
            'Options' => [
                'vps' => 'VPS',
                'server' => 'Regular server',
            ],
        ],
        'period' => [
            'FriendlyName' => 'License period (months)',
            'Type' => 'dropdown',
            'SimpleMode' => true,
            'Options' => [1 => 1, 12 => 12, 24 => 24],
        ],
        'ipRequired' => [
            'FriendlyName' => 'IP Address is required, and there is no option for IP binding',
            'Type' => 'yesno',
            'SimpleMode' => true,
            'Default' => 'no',
        ],
    ];
}

function openprovider_CreateAccount($params)
{
    $licenseType = $params['configoption1'];
    $period = $params['configoption3'] ?? 1;
    // These parameters need for future
//    $restrictIpBinding = !empty($params['configoption4']) && $params['configoption4'] == 'on';
//    $ipAddressBinding = array_values($params['customfields'])[2];

    $api = initApi();

    if (is_null($api)) {
        return "Credentials are incorrect or api is not configured!";
    }

    $argsCreatePleskLicense = [
        'items' => [
            $licenseType
        ],
        'period' => $period,
        // These parameters need for future
//        'restrictIpBinding' => $restrictIpBinding,
//        'ipAddressBinding' => $ipAddressBinding,
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
//    Mocked data to test creating plesk license
//    $pleskLicense = [
//        'key_number' => 123456789,
//        'activation_code' => 987,
//    ];

    $productId = $params['pid'];
    $serviceId = $params['serviceid'];

    $licenseNumber = $pleskLicense['key_number'];
    $activationCode = $pleskLicense['activation_code'];

    $newCustomFieldsValues = [
        $licenseNumber,
        $activationCode
    ];

    try {
        $customFields = Capsule::table('tblcustomfields')
            ->where('relid', $productId)
            ->get();

        $i = 0;
        foreach ($customFields as $customField) {
            if ($i > 1) {
                break;
            }
            Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $customField->id)
                ->where('relid', $serviceId)
                ->update([
                    'value' => $newCustomFieldsValues[$i++]
                ]);
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}
