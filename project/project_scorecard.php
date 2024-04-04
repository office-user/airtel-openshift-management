<?php

header('Content-Type: application/json');
$referer = $_SERVER['HTTP_REFERER'];
error_log("Referer: $referer");
require_once '../dbconfig.php';
require_once '../getfunction.php';

session_start();
if (!isset($_SESSION['role'])) {
    die(json_encode(['success' => false, 'errors' => 'Session Expired. Please login again.']));
}

function getScorecardData($clusters, $project) {
    $ocPath = "/usr/local/bin/oc";
    $scorecardData = [];

    foreach ($clusters as $cluster) {
        $numOfSingleOrFailedReplicas = 0;
        $numWithMultipleReplicas = 0;
        $numOfHPAs = 0;
        $numOfMissingRequiredAntiPodAffinities = 0;
        $numOfMissingAntiPodAffinities = 0;
        $numOfLivenessProbes = 0;
        $numOfReadinessProbes = 0;
        $numOfStartupProbes = 0;
        $numOfStatefulsets = 0;
        $numOf3rdPartyImages = 0;
        $numOfExternalImages = 0;
        $numOfMissingIfNotPresentImagePullPolicies = 0;

        error_log("getScorecardData: curl to get token for $cluster.");
        $token = makeGetRequest('https://oauth-openshift.apps.' . $cluster . '.india.airtel.itm/oauth/authorize?response_type=token&client_id=openshift-challenging-client', 'basic');

        error_log("getScorecardData: curl to get deployments for $cluster.");
        $clusterDeployments = json_decode(makeGetRequest('https://api.' . $cluster . '.india.airtel.itm:6443/apis/apps/v1/namespaces/' . $project . '/deployments', 'bearer', $token), true);

        error_log("getScorecardData: curl to get deploymentconfigs for $cluster.");
        $clusterDeploymentConfigs = json_decode(makeGetRequest('https://api.' . $cluster . '.india.airtel.itm:6443/apis/apps.openshift.io/v1/namespaces/' . $project . '/deploymentconfigs', 'bearer', $token), true);

        error_log("getScorecardData: curl to get statefulsets for $cluster.");
        $clusterStatefulsets = json_decode(makeGetRequest('https://api.' . $cluster . '.india.airtel.itm:6443/apis/apps/v1/namespaces/' . $project . '/statefulsets', 'bearer', $token), true);

        error_log("getScorecardData: curl to get horizontalpodautoscalers for $cluster.");
        $clusterHPAs = json_decode(makeGetRequest('https://api.' . $cluster . '.india.airtel.itm:6443/apis/autoscaling/v2/namespaces/' . $project . '/horizontalpodautoscalers', 'bearer', $token), true);

        if (isset($clusterDeployments['items'])) {
            foreach ($clusterDeployments['items'] as $item) {
                if (isset($item['status']['replicas']) && $item['status']['replicas'] > 0) {
                    $numWithMultipleReplicas++;
                    if (isset($item['status']['readyReplicas']) && $item['status']['readyReplicas'] < 2) {
                        $numOfSingleOrFailedReplicas++;
                    }
                    if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])) {
                        $numOfMissingRequiredAntiPodAffinities++;
                        if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity'])) {
                            $numOfMissingAntiPodAffinities++;   
                        }
                    }
                    foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                        if (isset($container['livenessProbe'])) {
                            $numOfLivenessProbes++;
                        }
                        if (isset($container['readinessProbe'])) {
                            $numOfReadinessProbes++;
                        }
                        if (isset($container['startupProbe'])) {
                            $numOfStartupProbes++;
                        }
                    }
                    foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                        preg_match('/^([^:\/])+/', $container['image'], $matches);
                        $imageRegistry = $matches[0];
                        error_log("Used Image Registry: $imageRegistry");
                        if (strpos($imageRegistry, '.india.airtel.itm') === false && strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false) {
                            $numOfExternalImages++;
                        } elseif (strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false  && strpos($imageRegistry, 'cellregistry.india.airtel.itm')) {
                            $numOf3rdPartyImages++;
                        }
                        preg_match('/^([^:\/])+/', $container['imagePullPolicy'], $matches);
                        $imagePullPolicy = $matches[0];
                        if (strpos($imagePullPolicy, 'IfNotPresent') === false) {
                            $numOfMissingIfNotPresentImagePullPolicies++;
                        }
                    }
                }
            }
        }
        if (isset($clusterDeploymentConfigs['items'])) {
            foreach ($clusterDeploymentConfigs['items'] as $item) {
                if (isset($item['status']['replicas']) && $item['status']['replicas'] > 0) {
                    $numWithMultipleReplicas++;
                    if (isset($item['status']['readyReplicas']) && $item['status']['readyReplicas'] < 2) {
                        $numOfSingleOrFailedReplicas++;
                    }
                    if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])) {
                        $numOfMissingRequiredAntiPodAffinities++;
                        if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity'])) {
                            $numOfMissingAntiPodAffinities++;   
                        }
                    }
                    foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                        if (isset($container['livenessProbe'])) {
                            $numOfLivenessProbes++;
                        }
                        if (isset($container['readinessProbe'])) {
                            $numOfReadinessProbes++;
                        }
                        if (isset($container['startupProbe'])) {
                            $numOfStartupProbes++;
                        }
                    }
                    foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                        preg_match('/^([^:\/])+/', $container['image'], $matches);
                        $imageRegistry = $matches[0];
                        error_log("Used Image Registry: $imageRegistry");
                        if (strpos($imageRegistry, '.india.airtel.itm') === false && strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false) {
                            $numOfExternalImages++;
                        } elseif (strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false  && strpos($imageRegistry, 'cellregistry.india.airtel.itm')) {
                            $numOf3rdPartyImages++;
                        }
                        preg_match('/^([^:\/])+/', $container['imagePullPolicy'], $matches);
                        $imagePullPolicy = $matches[0];
                        if (strpos($imagePullPolicy, 'IfNotPresent') === false) {
                            $numOfMissingIfNotPresentImagePullPolicies++;
                        }
                    }
                }
            }
        }
        if (isset($clusterStatefulsets['items'])) {
            foreach ($clusterStatefulsets['items'] as $item) {
                if (isset($item['status']['replicas']) && $item['status']['replicas'] > 0) {
                    $numWithMultipleReplicas++;
                    $numOfStatefulsets++;
                    if (isset($item['status']['readyReplicas']) && $item['status']['readyReplicas'] < 2) {
                        $numOfSingleOrFailedReplicas++;
                    }
                    if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])) {
                        $numOfMissingRequiredAntiPodAffinities++;
                        if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity'])) {
                            $numOfMissingAntiPodAffinities++;   
                        }
                    }
                    foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                        if (isset($container['livenessProbe'])) {
                            $numOfLivenessProbes++;
                        }
                        if (isset($container['readinessProbe'])) {
                            $numOfReadinessProbes++;
                        }
                        if (isset($container['startupProbe'])) {
                            $numOfStartupProbes++;
                        }
                    }
                    foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                        preg_match('/^([^:\/])+/', $container['image'], $matches);
                        $imageRegistry = $matches[0];
                        error_log("Used Image Registry: $imageRegistry");
                        if (strpos($imageRegistry, '.india.airtel.itm') === false && strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false) {
                            $numOfExternalImages++;
                        } elseif (strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false  && strpos($imageRegistry, 'cellregistry.india.airtel.itm')) {
                            $numOf3rdPartyImages++;
                        }
                        preg_match('/^([^:\/])+/', $container['imagePullPolicy'], $matches);
                        $imagePullPolicy = $matches[0];
                        if (strpos($imagePullPolicy, 'IfNotPresent') === false) {
                            $numOfMissingIfNotPresentImagePullPolicies++;
                        }
                    }
                }
            }
        }
        if (isset($clusterHPAs['items'])) {
            foreach ($clusterHPAs['items'] as $item) {
                if (isset($item['spec']['minReplicas']) && $item['spec']['minReplicas'] > 0) {
                    $numOfHPAs++;
                }
            }
        }

        $numOfMissingHPAs = ($numOfHPAs == 0) ? $numWithMultipleReplicas : $numWithMultipleReplicas - $numOfHPAs;
        $numOfMissingLivenessProbes = ($numOfLivenessProbes == 0) ? $numWithMultipleReplicas : $numWithMultipleReplicas - $numOfLivenessProbes;
        $numOfMissingReadinessProbes = ($numOfReadinessProbes == 0) ? $numWithMultipleReplicas : $numWithMultipleReplicas - $numOfReadinessProbes;
        $numOfMissingStartupProbes = ($numOfStartupProbes == 0) ? $numWithMultipleReplicas : $numWithMultipleReplicas - $numOfStartupProbes;

        $scorecardData[$cluster] = [
            'numOfSingleOrFailedReplicas' => $numOfSingleOrFailedReplicas,
            'numWithMultipleReplicas' => $numWithMultipleReplicas,
            'numOfMissingHPAs' => $numOfMissingHPAs,
            'numOfMissingRequiredAntiPodAffinities' => $numOfMissingRequiredAntiPodAffinities,
            'numOfMissingAntiPodAffinities' => $numOfMissingAntiPodAffinities,
            'numOfMissingLivenessProbes' => $numOfMissingLivenessProbes,
            'numOfMissingReadinessProbes' => $numOfMissingReadinessProbes,
            'numOfMissingStartupProbes' => $numOfMissingStartupProbes,
            'numOfStatefulsets' => $numOfStatefulsets,
            'numOfExternalImages' => $numOfExternalImages,
            'numOf3rdPartyImages' => $numOf3rdPartyImages,
            'numOfMissingIfNotPresentImagePullPolicies' => $numOfMissingIfNotPresentImagePullPolicies,
        ];
        error_log("getScorecardData: $cluster => numOfSingleOrFailedReplicas = $numOfSingleOrFailedReplicas, numWithMultipleReplicas = $numWithMultipleReplicas, numOfHPAs = $numOfHPAs, numOfMissingHPAs = $numOfMissingHPAs, numOfMissingRequiredAntiPodAffinities = $numOfMissingRequiredAntiPodAffinities, numOfMissingAntiPodAffinities = $numOfMissingAntiPodAffinities, numOfLivenessProbes = $numOfLivenessProbes, numOfMissingLivenessProbes = $numOfMissingLivenessProbes, numOfReadinessProbes = $numOfReadinessProbes, numOfMissingReadinessProbes = $numOfMissingReadinessProbes, numOfStartupProbes = $numOfStartupProbes, numOfMissingStartupProbes = $numOfMissingStartupProbes, numOfStatefulsets = $numOfStatefulsets, numOfExternalImages = $numOfExternalImages, numOf3rdPartyImages = $numOf3rdPartyImages, numOfMissingIfNotPresentImagePullPolicies = $numOfMissingIfNotPresentImagePullPolicies");
    }

    return $scorecardData;
}

function updateScorecard($cnx, $project) {
    $sql = "SELECT * FROM projectList WHERE project = ?";
    $stmt = $cnx->prepare($sql);
    error_log("updateScorecard: Preparing SQL statement: $sql");
    if (!$stmt) {
        error_log("updateScorecard: Error preparing SQL statement: " . $cnx->error);
        return ['success' => false, 'error' => $cnx->error];
    }
    $stmt->bind_param("s", $project);
    $stmt->execute();
    error_log("updateScorecard: SQL statement executed");
    $result = $stmt->get_result();
    $clusters = [];
    $prodDcList = [];
    $dmzDcList = [];
    while ($row = $result->fetch_assoc()) {
        error_log("updateScorecard: Looping for - " . $row['cluster'] . ", " . $row['dc'] . ", " . $row['typeName']);
        if ($row['typeName'] == 'PROD') {
            error_log("updateScorecard: Captured in prodDcList");
            $prodDcList[] = $row['dc'];
            $clusters[] = $row['cluster'];
        } elseif ($row['typeName'] == 'DMZ') {
            error_log("updateScorecard: Captured in dmzDcList");
            $dmzDcList[] = $row['dc'];
            $clusters[] = $row['cluster'];
        }
    }
    $numOfProdAvailability = count(array_unique($prodDcList));
    $numOfDMZAvailability = count(array_unique($dmzDcList));
    error_log("updateScorecard: numOfProdAvailability = $numOfProdAvailability & numOfDMZAvailability = $numOfDMZAvailability");
    if (!empty($clusters)) {
        error_log("updateScorecard: Calling getScorecardData for $project for - " . implode(", ", $clusters));
        $clusterData = getScorecardData($clusters, $project);
        $sql = "UPDATE projectList SET numOfProdAvailability = ?, numOfDMZAvailability = ?, numOfSingleOrFailedReplicas = ?, numWithMultipleReplicas = ?, numOfMissingHPAs = ?, numOfMissingRequiredAntiPodAffinities = ?, numOfMissingAntiPodAffinities = ?, numOfMissingLivenessProbes = ?, numOfMissingReadinessProbes = ?, numOfMissingStartupProbes = ?, numOfStatefulsets = ?, numOfExternalImages = ?, numOf3rdPartyImages = ?, numOfMissingIfNotPresentImagePullPolicies = ?, lastUpdated = NOW() WHERE cluster = ? AND project = ?";
        foreach ($clusterData as $cluster => $data) {
            $scorecardData[$cluster] = [
                'numOfSingleOrFailedReplicas' => $data['numOfSingleOrFailedReplicas'],
                'numWithMultipleReplicas' => $data['numWithMultipleReplicas'],
                'numOfMissingHPAs' => $data['numOfMissingHPAs'],
                'numOfMissingRequiredAntiPodAffinities' => $data['numOfMissingRequiredAntiPodAffinities'],
                'numOfMissingAntiPodAffinities' => $data['numOfMissingAntiPodAffinities'],
                'numOfMissingLivenessProbes' => $data['numOfMissingLivenessProbes'],
                'numOfMissingReadinessProbes' => $data['numOfMissingReadinessProbes'],
                'numOfMissingStartupProbes' => $data['numOfMissingStartupProbes'],
                'numOfStatefulsets' => $data['numOfStatefulsets'],
                'numOfExternalImages' => $data['numOfExternalImages'],
                'numOf3rdPartyImages' => $data['numOf3rdPartyImages'],
                'numOfMissingIfNotPresentImagePullPolicies' => $data['numOfMissingIfNotPresentImagePullPolicies'],
            ];
            $stmt = $cnx->prepare($sql);
            error_log("updateScorecard: Preparing SQL statement: $sql");
            if (!$stmt) {
                error_log("updateScorecard: Error preparing SQL statement: " . $cnx->error);
                http_response_code(500);
                return ['success' => false, 'error' => $cnx->error];
            }
            $stmt->bind_param("iiiiiiiiiiiiiiss", $numOfProdAvailability, $numOfDMZAvailability, $scorecardData[$cluster]['numOfSingleOrFailedReplicas'], $scorecardData[$cluster]['numWithMultipleReplicas'], $scorecardData[$cluster]['numOfMissingHPAs'], $scorecardData[$cluster]['numOfMissingRequiredAntiPodAffinities'], $scorecardData[$cluster]['numOfMissingAntiPodAffinities'], $scorecardData[$cluster]['numOfMissingLivenessProbes'], $scorecardData[$cluster]['numOfMissingReadinessProbes'], $scorecardData[$cluster]['numOfMissingStartupProbes'], $scorecardData[$cluster]['numOfStatefulsets'], $scorecardData[$cluster]['numOfExternalImages'], $scorecardData[$cluster]['numOf3rdPartyImages'], $scorecardData[$cluster]['numOfMissingIfNotPresentImagePullPolicies'], $cluster, $project);
            error_log("updateScorecard: Executing SQL statement");
            if (!$stmt->execute()) {
                http_response_code(500);
                return ['success' => false, 'error' => $cnx->error];
            }
        }
    }
    return ['success' => true];
}

function generateScorecardHTML($cnx, $project) {
    $sql = "SELECT * FROM projectList WHERE project = ?";
    $stmt = $cnx->prepare($sql);
    error_log("generateScorecardHTML: Preparing SQL statement: $sql");
    if (!$stmt) {
        error_log("generateScorecardHTML: Error preparing SQL statement: " . $cnx->error);
        return ['success' => false, 'error' => $cnx->error];
    }
    $stmt->bind_param("s", $project);
    $stmt->execute();
    error_log("generateScorecardHTML: SQL statement executed");
    $result = $stmt->get_result();
    $numOfProdAvailability = 0;
    $numOfDMZAvailability = 0;
    $numOfSingleOrFailedReplicas = [];
    $numWithMultipleReplicas = [];
    $numOfEmptyProjects = 0;
    $numOfMissingHPAs = [];
    $numOfMissingRequiredAntiPodAffinities = [];
    $numOfMissingAntiPodAffinities = [];
    $numOfMissingLivenessProbes = [];
    $numOfMissingReadinessProbes = [];
    $numOfMissingStartupProbes = [];
    $numOfStatefulsets = [];
    $numOfExternalImages = [];
    $numOf3rdPartyImages = [];
    $numOfMissingIfNotPresentImagePullPolicies = [];
    $emailAddresses = [];
    $lastUpdated = '';

    while ($row = $result->fetch_assoc()) {
        if ($row['typeName'] == 'PROD' || $row['typeName'] == 'DMZ') {
            $lastUpdated = $row['lastUpdated'];
            $numWithMultipleReplicas[] = ['cluster' => $row['cluster'], 'message' => $row['numWithMultipleReplicas']];
            if (empty($lastUpdated)) {
                return '';
            }
            $numOfProdAvailability = $row['numOfProdAvailability'];
            $numOfDMZAvailability = $row['numOfDMZAvailability'];
            if ($row['numWithMultipleReplicas'] > 0 ) {
                if ($row['numOfSingleOrFailedReplicas'] > 0 ) {
                    $numOfSingleOrFailedReplicas[] = ['cluster' => $row['cluster'], 'message' => $row['numOfSingleOrFailedReplicas']];
                }
                if ($row['numOfMissingHPAs'] > 0 ) {
                    $numOfMissingHPAs[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingHPAs']];
                }
                if ($row['numOfMissingRequiredAntiPodAffinities'] > 0 ) {
                    $numOfMissingRequiredAntiPodAffinities[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingRequiredAntiPodAffinities']];
                    if ($row['numOfMissingAntiPodAffinities'] > 0 ) {
                        $numOfMissingAntiPodAffinities[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingAntiPodAffinities']];
                    }
                }
                if ($row['numOfMissingLivenessProbes'] > 0 ) {
                    $numOfMissingLivenessProbes[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingLivenessProbes']];
                }
                if ($row['numOfMissingReadinessProbes'] > 0 ) {
                    $numOfMissingReadinessProbes[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingReadinessProbes']];
                }
                if ($row['numOfMissingStartupProbes'] > 0 ) {
                    $numOfMissingStartupProbes[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingStartupProbes']];
                }
                if ($row['numOfStatefulsets'] > 0 ) {
                    $numOfStatefulsets[] = ['cluster' => $row['cluster'], 'message' => $row['numOfStatefulsets']];
                }
                if ($row['numOfExternalImages'] > 0 ) {
                    $numOfExternalImages[] = ['cluster' => $row['cluster'], 'message' => $row['numOfExternalImages']];
                }
                if ($row['numOf3rdPartyImages'] > 0 ) {
                    $numOf3rdPartyImages[] = ['cluster' => $row['cluster'], 'message' => $row['numOf3rdPartyImages']];
                }
                if ($row['numOfMissingIfNotPresentImagePullPolicies'] > 0 ) {
                    $numOfMissingIfNotPresentImagePullPolicies[] = ['cluster' => $row['cluster'], 'message' => $row['numOfMissingIfNotPresentImagePullPolicies']];
                }
            } else {
                $numOfEmptyProjects++;
            }
        }
        $clusterlist[] = $row['cluster'];
        $emailAddresses[] = $row['emailAddresses'];
    }

    $emailAddresses = array_unique(explode(", ", implode(", ", $emailAddresses)));

    if ($lastUpdated == '') {
        $lastUpdated = 'Not Applicable';
    }

    error_log("generateScorecardHTML: Starting to calculate Score & generate Remark");

    $DCScore = ($numOfProdAvailability > 2 || $numOfDMZAvailability > 2) ? 10 : (($numOfProdAvailability == 2 || $numOfDMZAvailability == 2) ? 6 : (($numOfProdAvailability == 1 || $numOfDMZAvailability == 1) ? 3 : 0));
    error_log("generateScorecardHTML: DC Score : $DCScore");

    $configCount = [];
    foreach ($numWithMultipleReplicas as $numWithMultipleReplica) {
        $configCount[] = $numWithMultipleReplica['message'];
    }

    $htmlcontent = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta name="description" content="Scorecard for ' . $project . '">
    <title>Scorecard for ' . $project . '</title>
    <link rel="shortcut icon" type="image/x-icon" href="data:image/x-icon;," />
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <header class="topbar" id="topbar">
        <div class="topbar-logo">
            <img class="logo" src="../airtel-logo.png" alt="Airtel Logo">
            <h1 class="title">Openshift Management Dashboard</h1>
        </div>
    </header>
<div id="scorecard-result-replace-div" class="scorecard-result-replace-div">
    <h1 class="heading">Scorecard for ' . $project . '</h1>
    <h4 class="email-address">Email Address(s) =>' . implode(", ", $emailAddresses) . '</h4>
    <h4 class="heading">Cluster List => ' . implode(", ", $clusterlist) . '</h4>
    <p>Last Updated => ' . $lastUpdated . '</p>
    ';

    $Remark = '';
    if ($DCScore > 0) {
        if ($DCScore < 10) {
            $Remark .= '<br>This project is only available in ';
            if ($numOfProdAvailability > 0 && $numOfDMZAvailability > 0) {
                $Remark .= 'PROD Clusters of ' . $numOfProdAvailability . ' DC(s) & DMZ Clusters of ' . $numOfDMZAvailability . ' DC(s). <br>';
            } elseif ($numOfProdAvailability == 0 && $numOfDMZAvailability > 0) {
                $Remark .= 'DMZ Clusters of ' . $numOfDMZAvailability . ' DC(s). <br>';
            } else {
                $Remark .= 'PROD Clusters of ' . $numOfProdAvailability . ' DC(s). <br>';
            }
        }
        $ProjectScore = (count(array_unique($configCount)) == 1 && $numOfEmptyProjects == 0) ? 10 : 0;
        error_log("generateScorecardHTML: Project Score : $ProjectScore");
        $ReplicaScore = (count($numOfSingleOrFailedReplicas) == 0 && $ProjectScore !== 0) ? 10 : 0;
        error_log("generateScorecardHTML: Replica Score : $ReplicaScore");
        if ($ReplicaScore < 10)  {
            $Remark .= '<br>Number of Deployment,DeploymentConfig & Statefulset with less than 2 pods in ready state, excluding those with 0 desired pods => <br>';
            foreach ($numOfSingleOrFailedReplicas as $numOfSingleOrFailedReplica) {
                $cluster = $numOfSingleOrFailedReplica['cluster'];
                $count = $numOfSingleOrFailedReplica['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
        }
        $HPAScore = (count($numOfMissingHPAs) == 0 && $ProjectScore !== 0) ? 10 : 0;
        error_log("generateScorecardHTML: HPA Score : $HPAScore");
        if ($HPAScore < 10) {
            $Remark .= '<br>Number of Deployment,DeploymentConfig & Statefulset with missing HPA configuration, excluding those with 0 desired pods => <br>';
            foreach ($numOfMissingHPAs as $numOfMissingHPA) {
                $cluster = $numOfMissingHPA['cluster'];
                $count = $numOfMissingHPA['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
        }
        $AntiPodAffnityScore = (count($numOfMissingRequiredAntiPodAffinities) == 0 && $ProjectScore !== 0) ? 10 : ((count($numOfMissingAntiPodAffinities) == 0 && $ProjectScore !== 0) ? 6 : 0);
        error_log("generateScorecardHTML: Anti Pod Affinity Score : $AntiPodAffnityScore");
        if ($AntiPodAffnityScore == 6) {
            $Remark .= '<br>Preferred AntiPodAffinity is configured for all Deployment,DeploymentConfig & Statefulset. <br>';
            $Remark .= 'Number of Deployment,DeploymentConfig & Statefulset where Required AntiPodAffinity Score is not configured => <br>';
            foreach ($numOfMissingRequiredAntiPodAffinities as $numOfMissingRequiredAntiPodAffinity) {
                $cluster = $numOfMissingRequiredAntiPodAffnity['cluster'];
                $count = $numOfMissingRequiredAntiPodAffnity['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
        } elseif ($AntiPodAffnityScore == 0) {
            $Remark .= '<br>Number of Deployment,DeploymentConfig & Statefulset where AntiPodAffinity is not configured => <br>';
            foreach ($numOfMissingAntiPodAffinities as $numOfMissingAntiPodAffinity) {
                $cluster = $numOfMissingAntiPodAffinity['cluster'];
                $count = $numOfMissingAntiPodAffinity['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
            $Remark .= '<i>Preferred AntiPodAffinity for all provides 6 Score, and Required AntiPodAffinity for all provides 10 Score.</i><br>';
        }
        $HealthProbeScore = ((count($numOfMissingLivenessProbes) > 0 && count($numOfMissingReadinessProbes) > 0 && count($numOfMissingStartupProbes) > 0) || $ProjectScore == 0) ? 0 : (10 - (count($numOfMissingLivenessProbes) > 0 ? 4 : 0) - (count($numOfMissingReadinessProbes) > 0 ? 4 : 0) - (count($numOfMissingStartupProbes) > 0 ? 2 : 0));
        error_log("generateScorecardHTML: Health Probe Score : $HealthProbeScore");
        if ($HealthProbeScore < 10) {
            if (count($numOfMissingLivenessProbes) > 0) {
                $Remark .= '<br>Number of Deployment,DeploymentConfig & Statefulset where LivenessProbe worth 4 Score is missing => <br>';
                foreach ($numOfMissingLivenessProbes as $numOfMissingLivenessProbe) {
                    $cluster = $numOfMissingLivenessProbe['cluster'];
                    $count = $numOfMissingLivenessProbe['message'];
                    $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
                }
            } else {
                $Remark .= '<br>LivenessProbe is configured for all Deployment,DeploymentConfig & Statefulset. <br>';
            }
                if (count($numOfMissingReadinessProbes) > 0) {
                $Remark .= 'Number of Deployment,DeploymentConfig & Statefulset where ReadiessProbe worth 4 Score is missing => <br>';
                foreach ($numOfMissingReadinessProbes as $numOfMissingReadinessProbe) {
                    $cluster = $numOfMissingReadinessProbe['cluster'];
                    $count = $numOfMissingReadinessProbe['message'];
                    $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
                }
            } else {
                $Remark .= 'ReadinessProbe is configured for all Deployment,DeploymentConfig & Statefulset. <br>';
            }
                if (count($numOfMissingStartupProbes) > 0) {
                $Remark .= 'Number of Deployment,DeploymentConfig & Statefulset where StartupProbe worth 2 Score is missing => <br>';
                foreach ($numOfMissingStartupProbes as $numOfMissingStartupProbe) {
                    $cluster = $numOfMissingStartupProbe['cluster'];
                    $count = $numOfMissingStartupProbe['message'];
                    $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
                }
            } else {
                $Remark .= 'StartupProbe is configured for all Deployment,DeploymentConfig & Statefulset <br>';
            }
        }
        $StatelessScore = (count($numOfStatefulsets) == 0) ? 10 : 6;
        error_log("generateScorecardHTML: Stateless Score : $StatelessScore");
        if ($StatelessScore == 6) {
            $Remark .= '<br>Number of Statefulset, excluding those with 0 desired pods => <br>';
            foreach ($numOfStatefulsets as $numOfStatefulset) {
                $cluster = $numOfStatefulset['cluster'];
                $count = $numOfStatefulset['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
        }
        $ImageScore = (count($numOfExternalImages) == 0 && count($numOf3rdPartyImages) == 0 && $ProjectScore !== 0) ? 10 : ((count($numOfExternalImages) == 0 && $ProjectScore !== 0) ? 6 : 0);
        error_log("generateScorecardHTML: Image Score : $ImageScore");
        if ($ImageScore < 10) {
            $Remark .= '<br>';
            if (count($numOf3rdPartyImages) > 0) {
                $Remark .= 'Number of Deployment,DeploymentConfig & Statefulset where 3rd Party Image is used, excluding those with 0 desired pods => <br>';
                foreach ($numOf3rdPartyImages as $numOf3rdPartyImage) {
                    $cluster = $numOf3rdPartyImage['cluster'];
                    $count = $numOf3rdPartyImage['message'];
                    $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
                }
            }
            if (count($numOfExternalImages) > 0) {
                $Remark .= 'Number of Deployment,DeploymentConfig & Statefulset where External Image is used, excluding those with 0 desired pods => <br>';
                foreach ($numOfExternalImages as $numOfExternalImage) {
                    $cluster = $numOfExternalImage['cluster'];
                    $count = $numOfExternalImage['message'];
                    $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
                }
            }
        }
        $ImagePullPolicyScore = (count($numOfMissingIfNotPresentImagePullPolicies) == 0 && $ProjectScore !== 0) ? 10 : 0;
        error_log("generateScorecardHTML: Image Pull Policy Score : $ImagePullPolicyScore");
        if ($ImagePullPolicyScore < 10) {
            $Remark .= '<br>Number of Deployment,DeploymentConfig & Statefulset where Image Pull Policy is not set to IfNotPresent, excluding those with 0 desired pods => <br>';
            foreach ($numOfMissingIfNotPresentImagePullPolicies as $numOfMissingIfNotPresentImagePullPolicy) {
                $cluster = $numOfMissingIfNotPresentImagePullPolicy['cluster'];
                $count = $numOfMissingIfNotPresentImagePullPolicy['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
        }
        if ($ProjectScore < 10 && $numOfEmptyProjects < count($numWithMultipleReplicas)) {
            $Remark = '<br>Total Count of Deployment,DeploymentConfig & Statefulset is inconsistant on different clusters, as follow => <br>';
            foreach ($numWithMultipleReplicas as $numWithMultipleReplica) {
                $cluster = $numWithMultipleReplica['cluster'];
                $count = $numWithMultipleReplica['message'];
                $Remark .= 'Cluster: ' . $cluster . ', Count: ' . $count . '<br>';
            }
        } elseif ($ProjectScore < 10 && $numOfEmptyProjects == count($numWithMultipleReplicas) ) {
            $Remark = '<br>Project is empty on all clusters.';
        }
        $TotalScore = $DCScore + $ReplicaScore + $HPAScore + $AntiPodAffnityScore + $HealthProbeScore + $StatelessScore + $ImageScore + $ProjectScore + $ImagePullPolicyScore;
        $Total = 90;
        error_log("generateScorecardHTML: Total Score : $TotalScore");
        $htmlcontent .= '
    <table class="table">
        <thead class="table-head">
            <tr class="table-row">
                <th class="table-header">Parameter</th>
                <th class="table-header">Score</th>
            </tr>
        </thead>
        <tbody class="table-body">
            <tr class="table-row">
                <td class="table-data"> DC Redundancy </td>
                <td class="table-data">' . $DCScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Project Redundancy </td>
                <td class="table-data">' . $ProjectScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Replica Availability </td>
                <td class="table-data">' . $ReplicaScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> HPA Availability </td>
                <td class="table-data">' . $HPAScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> AntiPodAffinity Configured </td>
                <td class="table-data">' . $AntiPodAffnityScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Health Probe Configured </td>
                <td class="table-data">' . $HealthProbeScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Stateless Configuration </td>
                <td class="table-data">' . $StatelessScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Image Registry </td>
                <td class="table-data">' . $ImageScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Image Pull Policy </td>
                <td class="table-data">' . $ImagePullPolicyScore . '/10</td>
            </tr>
            <tr class="table-row">
                <td class="table-data"> Total Score </td>
                <td class="table-data">' . $TotalScore . '/' . $Total . '</td>
            </tr>
        </tbody>
    </table>
    <table class="table table-gap">
        <thead class="table-head">
            <tr class="table-row">
                <th class="table-header">Remarks</th>
            </tr>
        </thead>
        <tbody class="table-body">
            <tr class="table-row">
                <td>' . $Remark . '</td>
            </tr>
        </tbody>
    </table>
        ';
    } else {
        $htmlcontent .= '
    <p>Currently, This project is not deployed on any PROD/DMZ clusters to generate scorecard.</p>
        ';
    }
    $htmlcontent .= '
</div>
</body>
    ';

    return $htmlcontent;
}

function generateScorecard($cnx, $project) {
    $scorecardPath = '../scorecard/' . $project . '.html';
    $warningMessage = '';
    $updateScorecardResult = updateScorecard($cnx, $project);
    if ($updateScorecardResult.success == false) {
        $warningMessage = ' (Warning: Failed to Generate Scorecard. Showing Previously Generated Scorecard.)';
    }
    $htmlContents = generateScorecardHTML($cnx, $project);
    if ($warningMessage !== '') {
        $htmlContents .= '<p style="color: red;">' . $warningMessage . '</p>';
    }
    $handle = fopen($scorecardPath, 'w');
    fwrite($handle, $htmlContents);
    fclose($handle);
    return $scorecardPath;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
error_log("Action: $action");

switch ($action) {
    case 'generateScorecard':
        $scorecardPath = generateScorecard($cnx, $_POST['project']);
        error_log("scorecardPath: $scorecardPath");
        echo json_encode($scorecardPath);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        break;
}

$cnx->close();

?>