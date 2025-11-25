<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function dokploy_MetaData()
{
    return [
        'DisplayName' => 'Dokploy Module',
        'APIVersion' => '0.0.1',
        'ModuleVersion' => '1.0.0',
        'RequiresServer' => true,
    ];
}

function dokploy_GetHostName(array $params) {
    $apiHost = rtrim($params['serverhostname'], '/');
    if ($apiHost == '') throw new Exception('Could not find hostname for Dokploy - Is this configured?');

    foreach([
        'DOT'=> '.',
        'DASH' => '-',
    ] AS $from => $to) {
        $apiHost = str_replace($from, $to, $apiHost);
    }

    if(ip2long($apiHost) !== false) $apiHost = 'https://'. $apiHost;
    else $apiHost = ($params['serversecure'] ? 'https://' : 'http://') . $apiHost;

    return (rtrim($apiHost, '/'));
}

function dokploy_CreateAccount(array $params)
{
    require_once __DIR__ . '/lib/api.php';
    require_once __DIR__ . '/lib/helper.php';

    $hostName = dokploy_GetHostName($params);
    $apiKey = $params['serverpassword'];

    $api = new DokployAPI($hostName, $apiKey);

    $orgName = trim($params['clientsdetails']['companyname']) ?: trim($params['clientsdetails']['fullname']);
    if (!$orgName) {
        $orgName = 'WHMCS-Client-' . ($params['clientsdetails']['userid'] ?? uniqid());
    }

    try {
        $org = $api->createOrganization([
            "name" => $orgName,
            "logo" => "https://nodebyte.host/_next/image?url=%2Flogo.png&w=96&q=75"
        ]);
    } catch (Exception $e) {
        logModuleCall(
            'dokploy',
            'CreateOrganization',
            ['name' => $orgName],
            $e->getMessage()
        );
        return 'Error: could not create organization — ' . $e->getMessage();
    }

    if (empty($org) || empty($org['id'])) {
        logModuleCall('dokploy', 'CreateOrganization', ['name' => $orgName], $org);
        return 'Error: invalid response creating organization';
    }

    $orgId = $org['id'];

    try {
        $dokployUser = $api->createUser([
            "email" => $params['clientsdetails']['email'],
            "organizationId" => $orgId,
            "role" => "owner"
        ]);
        logModuleCall('dokploy', 'CreateUser', $dokployUser, ['name'=> $orgName], $org);
    } catch (Exception $e) {
        logModuleCall('dokploy', 'CreateUser', $dokployUser, $e->getMessage());
        return 'Warning: User was not created — ' . $e->getMessage();
    }

    $userId = $dokployUser['id'] ?? null;

    if (!$userId) {
        logModuleCall('dokploy', 'CreateUser', $userPayload, $dokployUser);
        return 'Warning: Dokploy returned invalid user response.';
    }

    saveServiceCustomField($params['serviceid'], 'DokployOrganizationID', $orgId);

    if (!empty($deployResult['deployment_id'])) {
        saveServiceCustomField($params['serviceid'], 'DokployDeploymentID', $deployResult['deployment_id']);
    }

    saveServiceCustomField($params['serviceid'], 'DokployUserID', $userId);

    logModuleCall('dokploy', 'CreateAccount', $params, ['org' => $org, 'deploy' => $deployResult]);

    return 'success';
}

function dokploy_TerminateAccount(array $params)
{
    require_once __DIR__ . '/lib/api.php';
    require_once __DIR__ . '/lib/helper.php';

    $hostName = dokploy_GetHostName($params);
    $apiKey = $params['serverpassword'];

    $api = new DokployAPI($hostName, $apiKey);

    $orgId = $params['customfields']['DokployOrganizationID'] ?? null;

    if ($orgId) {
        try {
            $api->deleteOrganization(["organizationId" => $orgId]);
            logModuleCall('dokploy', 'TerminateAccount', ['orgId' => $orgId], 'deleted');
        } catch (Exception $e) {
            logModuleCall('dokploy', 'TerminateAccount', ['orgId' => $orgId], $e->getMessage());
        }
    }

    return 'success';
}
