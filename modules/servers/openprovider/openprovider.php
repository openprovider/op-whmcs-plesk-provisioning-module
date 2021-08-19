<?php

use Illuminate\Database\Capsule\Manager as Capsule;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

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
        'DisplayName' => 'Openprovider-plesk',
        'APIVersion' => '1.1', // Use API Version 1.1
        'RequiresServer' => true, // Set true if module requires a server to work
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
            'Options' => [1, 12, 24],
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
    $billingCycles = [
        'monthly' => 1,
        'annually' => 12,
        'biennially' => 24,
    ];

    $licenseType = $params['configoption1'];
    $restrictIpBinding = !empty($params['configoption4']) && $params['configoption4'] == 'on';
    $period = $params['configoption3'] ?? 1;
    $ipAddressBinding = array_values($params['customfields'])[2];

    $api = getApi();

    $argsCreatePleskLicense = [
        'items' => [
            $licenseType
        ],
        'period' => $period,
        'restrictIpBinding' => $restrictIpBinding,
        'ipAddressBinding' => $ipAddressBinding,
    ];

    $createPleskLicenseResponse = $api->call('createPleskLicenseRequest', $argsCreatePleskLicense);

    if ($createPleskLicenseResponse->getCode() != 0) {
        return $createPleskLicenseResponse->getMessage();
    }

    $pleskLicenseKeyId = $createPleskLicenseResponse->getData()['keyId'];

    $getPleskLicenseResponse = $api->call('retrievePleskLicenseRequest', [
        'keyId' => $pleskLicenseKeyId
    ]);

    if ($getPleskLicenseResponse->getCode() != 0) {
        return $getPleskLicenseResponse->getMessage();
    }

    $pleskLicense = $getPleskLicenseResponse->getData();
//    Mocked data to test creating plesk license
//    $pleskLicense = [
//        'keyNumber' => 123456789,
//        'activationCode' => 987,
//    ];

    $productId = $params['pid'];
    $serviceId = $params['serviceid'];

    $licenseNumber = $pleskLicense['keyNumber'];
    $activationCode = $pleskLicense['activationCode'];

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
