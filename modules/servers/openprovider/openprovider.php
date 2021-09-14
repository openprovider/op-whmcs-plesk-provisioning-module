<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'helper.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'command_names.php';

const META_DISPLAY_NAME = 'Openprovider Plesk';
const META_API_VERSION = '1.1';
const META_REQUIRES_SERVER = false;
const META_DEFAULT_NON_SSL_PORT = '1111';
const META_DEFAULT_SSL_PORT = '1112';
const META_SERVICE_SINGLE_SIGN_ON_LABEL = 'Login to Panel as User';
const META_ADMIN_SINGLE_SIGN_ON_LABEL = 'Login to Panel as Admin';

const CONFIG_OPTION_USERNAME = 'Username';
const CONFIG_OPTION_PASSWORD = 'Password';
const CONFIG_OPTION_LICENSE_TYPE = 'License Type';
const CONFIG_OPTION_LICENSE_PERIOD = 'License period (months)';

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
function openprovider_MetaData(): array
{
    return [
        'DisplayName' => META_DISPLAY_NAME,
        'APIVersion' => META_API_VERSION,
        'RequiresServer' => META_REQUIRES_SERVER,
        'DefaultNonSSLPort' => META_DEFAULT_NON_SSL_PORT,
        'DefaultSSLPort' => META_DEFAULT_SSL_PORT,
        'ServiceSingleSignOnLabel' => META_SERVICE_SINGLE_SIGN_ON_LABEL,
        'AdminSingleSignOnLabel' => META_ADMIN_SINGLE_SIGN_ON_LABEL,
    ];
}

/**
 * Return array to configure module
 *
 * @see https://developers.whmcs.com/provisioning-modules/config-options/
 *
 * @return array[]
 */
function openprovider_ConfigOptions(): array
{
    return [
        'username' => [
            'FriendlyName' => CONFIG_OPTION_USERNAME,
            'Type' => 'text',
            'SimpleMode' => true,
        ],
        'password' => [
            'FriendlyName' => CONFIG_OPTION_PASSWORD,
            'Type' => 'password',
            'SimpleMode' => true,
        ],
        'license' => [
            'FriendlyName' => CONFIG_OPTION_LICENSE_TYPE,
            'Type' => 'text',
            'SimpleMode' => true,
        ],
        'period' => [
            'FriendlyName' => CONFIG_OPTION_LICENSE_PERIOD,
            'Type' => 'dropdown',
            'SimpleMode' => true,
            'Options' => [1 => 1, 12 => 12, 24 => 24],
        ],
    ];
}

/**
 * Function creates plesk license in Openprovider
 *
 * @see https://developers.whmcs.com/provisioning-modules/supported-functions/
 *
 * @param array $params available parameters from whmcs
 *
 * @return string
 *
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 */
function openprovider_CreateAccount($params): string
{
    $username = $params['configoption1'];
    $password = $params['configoption2'];
    $licenseType = $params['configoption3'];
    $period = $params['configoption4'] ?? 1;

    $moduleHelper = new OpenproviderPleskModuleHelper();

    if (!$moduleHelper->initApi($username, $password)) {
        return ERROR_API_CLIENT_NOT_CONFIGURED;
    }

    $argsCreatePleskLicense = [
        'items' => [
            $licenseType
        ],
        'period' => $period,
    ];

    $createPleskLicenseResponse = $moduleHelper->call(ApiCommandNames::CREATE_PLESK_LICENSE_REQUEST, $argsCreatePleskLicense);

    if ($createPleskLicenseResponse->getCode() != 0) {
        return $createPleskLicenseResponse->getMessage();
    }

    $pleskLicenseKeyId = $createPleskLicenseResponse->getData()['key_id'];
    $getPleskLicenseResponse = $moduleHelper->call(ApiCommandNames::RETRIEVE_PLESK_LICENSE_REQUEST, [
        'key_id' => $pleskLicenseKeyId
    ]);

    if ($getPleskLicenseResponse->getCode() != 0) {
        return $getPleskLicenseResponse->getMessage();
    }

    $pleskLicense = $getPleskLicenseResponse->getData();
    $licenseNumber = $pleskLicense['key_number'];
    $activationCode = $pleskLicense['activation_code'];

    try {
        $customFieldNames = $moduleHelper->getCustomFieldNames();
        $params['model']->serviceProperties->save([$customFieldNames['license_number'] => $licenseNumber]);
        $params['model']->serviceProperties->save([$customFieldNames['activation_code'] => $activationCode]);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    return 'success';
}

/**
 * Function returns HTML template for op-plesk product and product addon to display license data
 *
 * @see https://developers.whmcs.com/provisioning-modules/client-area-output/
 *
 * @param array $params available parameters from whmcs
 *
 * @return string
 */
function openprovider_ClientArea($params): string
{
    $customFieldNames = array_keys($params['customfields']);
    $moduleHelper = new OpenproviderPleskModuleHelper();
    $customFieldNamesDefault = $moduleHelper->getCustomFieldNames();

    $licenseNumber = $params['model']->serviceProperties->get($customFieldNames[0]);
    $activationCode = $params['model']->serviceProperties->get($customFieldNames[1]);

    return $moduleHelper->getHtmlTemplateFieldsForProductAddon(
        $customFieldNames[0] ?? $customFieldNamesDefault['license_number'],
        $licenseNumber ?? '',
        $customFieldNames[1] ?? $customFieldNamesDefault['activation_code'],
        $activationCode ?? ''
    );
}
