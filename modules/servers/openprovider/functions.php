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
 * @return array plesk license types
 *
 * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
 */
function getLicenseTypes(): array
{
    $filenameMainPart = 'license-types-';
    $daysInterval = '3';

    // searching license types in file
    $files = scandir(__DIR__);
    $licenseTypes = [];

    foreach ($files as $file) {
        if (strpos($file, $filenameMainPart) !== false) {
            $filenamePattern = __DIR__ . DIRECTORY_SEPARATOR . $filenameMainPart . '*';
            $filePath = __DIR__ . DIRECTORY_SEPARATOR . $file;

            $createdDatePart = substr($file, strlen($filenameMainPart));
            try {
                $createdDate = new DateTime($createdDatePart);
            } catch (Exception $e) {
                array_map('unlink', glob($filenamePattern));

                break;
            }

            $untilDate = $createdDate->add(new DateInterval('P' . $daysInterval . 'D'));

            if ($untilDate < (new DateTime('now'))) {
                array_map('unlink', glob($filenamePattern));

                break;
            }

            $licenseTypes = (array) json_decode(file_get_contents($filePath));

            break;
        }
    }

    if (!empty($licenseTypes)) {
        return $licenseTypes;
    }

    // if there is no licenses in cache
    $api = getApi();

    if (is_null($api)) {
        return [];
    }

    $searchLicensesArgs = ['product' => 'plesk', 'limit' => 500];
    $searchLicensesRequest = $api->call('searchPleskAndVirtuozzoItemRequest', $searchLicensesArgs);

    if ($searchLicensesRequest->getCode() != 0) {
        return [];
    }

    foreach ($searchLicensesRequest->getData()['results'] as $item) {
        $licenseTypes[$item['item']] = $item['title'];
    }

    $newFilename = (__DIR__ .
        DIRECTORY_SEPARATOR .
        $filenameMainPart .
        (new DateTime('now'))->format('Y-m-d'));

    file_put_contents($newFilename, json_encode($licenseTypes));

    return $licenseTypes;
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
