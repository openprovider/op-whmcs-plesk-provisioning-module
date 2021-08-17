<?php

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
    $licenseTypes = getLicenseTypes();

    return [
        'license' => [
            'FriendlyName' => 'License type',
            'Type' => 'dropdown',
            'SimpleMode' => true,
            'Options' => $licenseTypes,
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
        'useIp' => [
            'FriendlyName' => 'Should use the IP address of the VPS or regular server',
            'Type' => 'yesno',
            'SimpleMode' => false,
            'Default' => 'no',
        ],
        'ipRequired' => [
            'FriendlyName' => 'IP Address is required, and there is no option for IP binding',
            'Type' => 'yesno',
            'SimpleMode' => false,
            'Default' => 'no',
        ],
        'welcomeEmail' => [
            'FriendlyName' => 'Welcome Email',
            'Type' => 'textarea',
            'SimpleMode' => false,
            'Description' => 'Enter your welcome email.'
        ]
    ];
}
