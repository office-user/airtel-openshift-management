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

function createScorecard($cnx, $project, $scorecardPath) {
    $sql = "SELECT * FROM projectList WHERE project = ?";
    $stmt = $cnx->prepare($sql);
    error_log("createScorecard: Preparing SQL statement: $sql");
    if (!$stmt) {
        error_log("createScorecard: Error preparing SQL statement: " . $cnx->error);
        return ['success' => false, 'error' => $cnx->error];
    }
    $stmt->bind_param("s", $project);
    $stmt->execute();
    error_log("createScorecard: SQL statement executed");
    $result = $stmt->get_result();
    $clusters = [];
    $prodDcList = [];
    $dmzDcList = [];
    $emailAddresses = [];
    while ($row = $result->fetch_assoc()) {
        error_log("createScorecard: Looping for - " . $row['cluster'] . ", " . $row['dc'] . ", " . $row['typeName']);
        if ($row['typeName'] == 'PROD') {
            error_log("createScorecard: Captured in prodDcList");
            if (!in_array($row['dc'], $prodDcList)) {
                $prodDcList[] = $row['dc'];
            }
            $targetclusters[] = $row['cluster'];
        } elseif ($row['typeName'] == 'DMZ') {
            error_log("createScorecard: Captured in dmzDcList");
            if (!in_array($row['dc'], $dmzDcList)) {
                $dmzDcList[] = $row['dc'];
            }
            $targetclusters[] = $row['cluster'];
        }
        $emailAddresses[] = $row['emailAddresses'];
        $clusterlist[] = $row['cluster'];
    }
    $emailAddresses = array_unique(explode(", ", implode(", ", $emailAddresses)));

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
    <script src="../jquery-3.6.0.min.js"></script>
    <script src="../dashboard.js"></script>
</head>
<body>
    <header class="topbar" id="topbar">
        <div class="topbar-logo">
            <img class="logo" src="../airtel-logo.png" alt="Airtel Logo">
            <h1 class="title">Openshift Management Dashboard</h1>
        </div>
    </header>
    <div class="full-view-main-content">
    <div id="scorecard-result-replace-div" class="scorecard-result-replace-div">
    <div class="dialog-container">
        <div id="remarkDialog" class="dialog dialog-remark" style="display: none;">
            <div class="dialog-content">
                <p id="remarkDialogContent"></p>
            </div>
            <button id="remarkDialogClose" class="dialog-close" onclick="closeRemarkDialog()">X</button>
        </div>
    </div>
        <h1 class="heading">Scorecard for ' . $project . '</h1>
        <h4 class="email-address">Email Address(s) =>' . implode(", ", $emailAddresses) . '</h4>
        <h4 class="heading">Cluster List => ' . implode(", ", $clusterlist) . '</h4>
        <p>Last Checked => ' . date('F d, Y h:i:s A') . '</p>
    ';

    $score = 0;
    if (!empty($targetclusters)) {
        $numOfProdAvailability = count($prodDcList);
        $numOfDMZAvailability = count($dmzDcList);
        $DCScore = ($numOfProdAvailability > 2 || $numOfDMZAvailability > 2) ? 10 : (($numOfProdAvailability == 2 || $numOfDMZAvailability == 2) ? 6 : 3);
        $clusterCount = count($targetclusters);
        $deploymentNames = [];
        $deploymentCounts = [];
        $deploymentFailedReplicaClusters = [];
        $deploymentMissingRequiredAntiAffinities = [];
        $deploymentMissingAntiAffinities = [];
        $deploymentMissingLivenessProbes = [];
        $deploymentMissingReadinessProbes = [];
        $deploymentMissingStartupProbes = [];
        $deploymentUsingExternalImages = [];
        $deploymentUsing3rdPartyImages = [];
        $deploymentMissingIfNotPresentPullPolicies = [];
        $deploymentHPAs = [];
        $deploymentConfigNames = [];
        $deploymentConfigCounts = [];
        $deploymentConfigFailedReplicaClusters = [];
        $deploymentConfigMissingRequiredAntiAffinities = [];
        $deploymentConfigMissingAntiAffinities = [];
        $deploymentConfigMissingLivenessProbes = [];
        $deploymentConfigMissingReadinessProbes = [];
        $deploymentConfigMissingStartupProbes = [];
        $deploymentConfigUsingExternalImages = [];
        $deploymentConfigUsing3rdPartyImages = [];
        $deploymentConfigMissingIfNotPresentPullPolicies = [];
        $deploymentConfigHPAs = [];
        $statefulsetNames = [];
        $statefulsetCounts = [];
        $statefulsetFailedReplicaClusters = [];
        $statefulsetMissingRequiredAntiAffinities = [];
        $statefulsetMissingAntiAffinities = [];
        $statefulsetMissingLivenessProbes = [];
        $statefulsetMissingReadinessProbes = [];
        $statefulsetMissingStartupProbes = [];
        $statefulsetUsingExternalImages = [];
        $statefulsetUsing3rdPartyImages = [];
        $statefulsetMissingIfNotPresentPullPolicies = [];
        $statefulsetHPAs = [];
        $hpaInfo = [
            'Deployment' => [],
            'DeploymentConfig' => [],
            'StatefulSet' => []
        ];
        foreach ($targetclusters as $cluster) {
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
                    $deploymentReplica = $item['status']['replicas'];
                    if ($deploymentReplica > 0) {
                        $deploymentName = $item['metadata']['name'];
                        $deploymentReadyReplica = $item['status']['readyReplicas'];
                        if (!in_array($deploymentName, $deploymentNames)) {
                            $deploymentNames[] = $deploymentName;
                            $deploymentCounts[$deploymentName] = 1;
                        } else {
                            $deploymentCounts[$deploymentName]++;
                        }
                        if ($deploymentReadyReplica < 2) {
                            $deploymentFailedReplicaClusters[$deploymentName][] = ' less than 2 replicsa on ' . $cluster;
                        } elseif ($deploymentReadyReplica < $deploymentReplica) {
                            $deploymentFailedReplicaClusters[$deploymentName][] = ($deploymentReplica - $deploymentReadyReplica) . ' failed replicas on ' . $cluster;
                        }
                        if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity'])) {
                            $deploymentMissingAntiAffinities[$deploymentName][] = $cluster;
                        } elseif (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])) {
                            $deploymentMissingRequiredAntiAffinities[$deploymentName][] = $cluster;
                        }
                        foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                            if (!isset($container['livenessProbe']) && !in_array($cluster, $deploymentMissingLivenessProbes[$deploymentName])) {
                                $deploymentMissingLivenessProbes[$deploymentName][] = $cluster;
                            }
                            if (!isset($container['readinessProbe']) && !in_array($cluster, $deploymentMissingReadinessProbes[$deploymentName])) {
                                $deploymentMissingReadinessProbes[$deploymentName][] = $cluster;
                            }
                            if (!isset($container['startupProbe']) && !in_array($cluster, $deploymentMissingStartupProbes[$deploymentName])) {
                                $deploymentMissingStartupProbes[$deploymentName][] = $cluster;
                            }
                            preg_match('/^([^:\/])+/', $container['image'], $matches);
                            $imageRegistry = $matches[0];
                            if (strpos($imageRegistry, '.india.airtel.itm') === false && strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false && !in_array($cluster, $deploymentUsingExternalImages[$deploymentName])) {
                                $deploymentUsingExternalImages[$deploymentName][] = $cluster;
                            } elseif (strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false  && strpos($imageRegistry, 'cellregistry.india.airtel.itm') === false && !in_array($cluster, $deploymentUsing3rdPartyImages[$deploymentName])) {
                                $deploymentUsing3rdPartyImages[$deploymentName][] = $cluster;
                            }
                            preg_match('/^([^:\/])+/', $container['imagePullPolicy'], $matches);
                            $imagePullPolicy = $matches[0];
                            if (strpos($imagePullPolicy, 'IfNotPresent') === false && !in_array($cluster, $deploymentMissingIfNotPresentPullPolicies[$deploymentName])) {
                                $deploymentMissingIfNotPresentPullPolicies[$deploymentName][] = $cluster;
                            }
                        }
                    }
                }
            }
            if (isset($clusterStatefulsets['items'])) {
                foreach ($clusterStatefulsets['items'] as $item) {
                    $statefulsetReplica = $item['status']['replicas'];
                    if ($statefulsetReplica > 0) {
                        $statefulsetName = $item['metadata']['name'];
                        $statefulsetReadyReplica = $item['status']['readyReplicas'];
                        $statefulsetReplica = $item['status']['replicas'];
                        if (!in_array($statefulsetName, $statefulsetNames)) {
                            $statefulsetNames[] = $statefulsetName;
                            $statefulsetCounts[$statefulsetName] = 1;
                        } else {
                            $statefulsetCounts[$statefulsetName]++;
                        }
                        if ($statefulsetReadyReplica < 2) {
                            $statefulsetFailedReplicaClusters[$statefulsetName][] = ' less than 2 replicsa on ' . $cluster;
                        } elseif ($statefulsetReadyReplica < $statefulsetReplica) {
                            $statefulsetFailedReplicaClusters[$statefulsetName][] = ($statefulsetReplica - $statefulsetReadyReplica) . ' failed replicas on ' . $cluster;
                        }
                        if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity'])) {
                            $statefulsetMissingAntiAffinities[$statefulsetName][] = $cluster;
                        } elseif (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])) {
                            $statefulsetMissingRequiredAntiAffinities[$statefulsetName][] = $cluster;
                        }
                        foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                            if (isset($container['livenessProbe']) && !in_array($cluster, $statefulsetMissingLivenessProbes[$statefulsetName])) {
                                $statefulsetMissingLivenessProbes[$statefulsetName][] = $cluster;
                            }
                            if (isset($container['readinessProbe']) && !in_array($cluster, $statefulsetMissingReadinessProbes[$statefulsetName])) {
                                $statefulsetMissingReadinessProbes[$statefulsetName][] = $cluster;
                            }
                            if (isset($container['startupProbe']) && !in_array($cluster, $statefulsetMissingStartupProbes[$statefulsetName])) {
                                $statefulsetMissingStartupProbes[$statefulsetName][] = $cluster;
                            }
                            preg_match('/^([^:\/])+/', $container['image'], $matches);
                            $imageRegistry = $matches[0];
                            if (strpos($imageRegistry, '.india.airtel.itm') === false && strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false && !in_array($cluster, $statefulsetUsingExternalImages[$statefulsetName])) {
                                $statefulsetUsingExternalImages[$statefulsetName][] = $cluster;
                            } elseif (strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false  && strpos($imageRegistry, 'cellregistry.india.airtel.itm') === false && !in_array($cluster, $statefulsetUsing3rdPartyImages[$statefulsetName])) {
                                $statefulsetUsing3rdPartyImages[$statefulsetName][] = $cluster;
                            }
                            preg_match('/^([^:\/])+/', $container['imagePullPolicy'], $matches);
                            $imagePullPolicy = $matches[0];
                            if (strpos($imagePullPolicy, 'IfNotPresent') === false && !in_array($cluster, $statefulsetMissingIfNotPresentPullPolicies[$statefulsetName])) {
                                $statefulsetMissingIfNotPresentPullPolicies[$statefulsetName][] = $cluster;
                            }  
                        }
                    }
                }
            }
            if (isset($clusterDeploymentConfigs['items'])) {
                foreach ($clusterDeploymentConfigs['items'] as $item) {
                    $deploymentConfigReplica = $item['status']['replicas'];
                    if ($deploymentConfigReplica > 0) {
                        $deploymentConfigName = $item['metadata']['name'];
                        $deploymentConfigReadyReplica = $item['status']['readyReplicas'];
                        if (!in_array($deploymentConfigName, $deploymentConfigNames)) {
                            $deploymentConfigNames[] = $deploymentConfigName;
                            $deploymentConfigCounts[$deploymentConfigName] = 1;
                        } else {
                            $deploymentConfigCounts[$deploymentConfigName]++;
                        }
                        if ($deploymentConfigReadyReplica < 2) {
                            $deploymentConfigFailedReplicaClusters[$deploymentConfigName][] = ' less than 2 replicsa on ' . $cluster;
                        } elseif ($deploymentConfigReadyReplica < $deploymentConfigReplica) {
                            $deploymentConfigFailedReplicaClusters[$deploymentConfigName][] = ($deploymentConfigReplica - $deploymentConfigReadyReplica) . ' failed replicas on ' . $cluster;
                        }
                        if (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity'])) {
                            $deploymentConfigMissingAntiAffinities[$deploymentConfigName][] = $cluster;
                        } elseif (!isset($item['spec']['template']['spec']['affinity']['podAntiAffinity']['requiredDuringSchedulingIgnoredDuringExecution'])) {
                            $deploymentConfigMissingRequiredAntiAffinities[$deploymentConfigName][] = $cluster;
                        }
                        foreach ($item['spec']['template']['spec']['containers'] as $i => $container) {
                            if (isset($container['livenessProbe']) && !in_array($cluster, $deploymentConfigMissingLivenessProbes[$deploymentConfigName])) {
                                $deploymentConfigMissingLivenessProbes[$deploymentConfigName][] = $cluster;
                            }
                            if (isset($container['readinessProbe']) && !in_array($cluster, $deploymentConfigMissingReadinessProbes[$deploymentConfigName])) {
                                $deploymentConfigMissingReadinessProbes[$deploymentConfigName][] = $cluster;
                            }
                            if (isset($container['startupProbe']) && !in_array($cluster, $deploymentConfigMissingStartupProbes[$deploymentConfigName])) {
                                $deploymentConfigMissingStartupProbes[$deploymentConfigName][] = $cluster;
                            }
                            preg_match('/^([^:\/])+/', $container['image'], $matches);
                            $imageRegistry = $matches[0];
                            if (strpos($imageRegistry, '.india.airtel.itm') === false && strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false && !in_array($cluster, $deploymentConfigUsingExternalImages[$deploymentConfigName])) {
                                $deploymentConfigUsingExternalImages[$deploymentConfigName][] = $cluster;
                            } elseif (strpos($imageRegistry, 'image-registry.openshift-image-registry.svc') === false  && strpos($imageRegistry, 'cellregistry.india.airtel.itm') === false && !in_array($cluster, $deploymentConfigUsing3rdPartyImages[$deploymentConfigName])) {
                                $deploymentConfigUsing3rdPartyImages[$deploymentConfigName][] = $cluster;
                            }
                            preg_match('/^([^:\/])+/', $container['imagePullPolicy'], $matches);
                            $imagePullPolicy = $matches[0];
                            if (strpos($imagePullPolicy, 'IfNotPresent') === false && !in_array($cluster, $deploymentConfigMissingIfNotPresentPullPolicies[$deploymentConfigName])) {
                                $deploymentConfigMissingIfNotPresentPullPolicies[$deploymentConfigName][] = $cluster;
                            }
                        }
                    }
                }
            }
            if (isset($clusterHPAs['items'])) {
                foreach ($clusterHPAs['items'] as $item) {
                    if (isset($item['spec']['minReplicas']) && $item['spec']['minReplicas'] > 0) {
                        $targetType = $item['spec']['scaleTargetRef']['kind'];
                        $targetName = $item['spec']['scaleTargetRef']['name'];
                        if ($targetType == 'Deployment') {
                            $deploymentHPAs[$deploymentName][] = $cluster;
                        } elseif ($targetType == 'DeploymentConfig') {
                            $deploymentConfigHPAs[$deploymentConfigName][] = $cluster;
                        } elseif ($targetType == 'StatefulSet') {
                            $statefulsetHPAs[$statefulsetName][] = $cluster;
                        }
                    }
                }
            }
        }

        $typeMap = [
            'Deployment' => ['names' => $deploymentNames, 'counts' => $deploymentCounts, 'failedReplicaClusters' => $deploymentFailedReplicaClusters, 'missingAntiAffinities' => $deploymentMissingAntiAffinities, 'missingRequiredAntiAffinities' => $deploymentMissingRequiredAntiAffinities, 'missingLivenessProbes' => $deploymentMissingLivenessProbes, 'missingReadinessProbes' => $deploymentMissingReadinessProbes, 'missingStartupProbes' => $deploymentMissingStartupProbes, 'usingExternalImages' => $deploymentUsingExternalImages, 'using3rdPartyImages' => $deploymentUsing3rdPartyImages, 'missingIfNotPresentPullPolicies' => $deploymentMissingIfNotPresentPullPolicies, 'hpas' => $deploymentHPAs],
            'StatefulSet' => ['names' => $statefulsetNames, 'counts' => $statefulsetCounts, 'failedReplicaClusters' => $statefulsetFailedReplicaClusters, 'missingAntiAffinities' => $statefulsetMissingAntiAffinities, 'missingRequiredAntiAffinities' => $statefulsetMissingRequiredAntiAffinities, 'missingLivenessProbes' => $statefulsetMissingLivenessProbes, 'missingReadinessProbes' => $statefulsetMissingReadinessProbes, 'missingStartupProbes' => $statefulsetMissingStartupProbes, 'usingExternalImages' => $statefulsetUsingExternalImages, 'using3rdPartyImages' => $statefulsetUsing3rdPartyImages, 'missingIfNotPresentPullPolicies' => $statefulsetMissingIfNotPresentPullPolicies, 'hpas' => $statefulsetHPAs],
            'DeploymentConfig' => ['names' => $deploymentConfigNames, 'counts' => $deploymentConfigCounts, 'failedReplicaClusters' => $deploymentConfigFailedReplicaClusters, 'missingAntiAffinities' => $deploymentConfigMissingAntiAffinities, 'missingRequiredAntiAffinities' => $deploymentConfigMissingRequiredAntiAffinities, 'missingLivenessProbes' => $deploymentConfigMissingLivenessProbes, 'missingReadinessProbes' => $deploymentConfigMissingReadinessProbes, 'missingStartupProbes' => $deploymentConfigMissingStartupProbes, 'usingExternalImages' => $deploymentConfigUsingExternalImages, 'using3rdPartyImages' => $deploymentConfigUsing3rdPartyImages, 'missingIfNotPresentPullPolicies' => $deploymentConfigMissingIfNotPresentPullPolicies, 'hpas' => $deploymentConfigHPAs],
        ];


        if (count($deploymentNames) == 0 && count($deploymentConfigNames) == 0 && count($statefulsetNames) == 0) {
            
            $htmlcontent .= '
            <p>Currently, This project is empty on all PROD/DMZ clusters.</p>
            ';

        } else {

            $htmlcontent .= '
            <table class="table">
                <thead class="table-head">
                    <tr class="table-row">
                        <th class="table-header">Name</th>
                        <th class="table-header">High Availability</th>
                        <th class="table-header">Pod Replication</th>
                        <th class="table-header">Pod Anti-Affinity</th>
                        <th class="table-header">Liveness Probe</th>
                        <th class="table-header">Readiness Probe</th>
                        <th class="table-header">Startup Probe</th>
                        <th class="table-header">Stateless</th>
                        <th class="table-header">Image Source</th>
                        <th class="table-header">Image Pull Policy</th>
                        <th class="table-header">HPA</th>
                    </tr>
                </thead>
                <tbody class="table-body">
            ';

            $scoreForSuccess = 10;
            $scoreForWarning = 5;

            foreach ($typeMap as $type => $scorecardData) {
                $failedReplicaCluster = [];
                foreach ($scorecardData['names'] as $name) {
                    $htmlcontent .= '<tr class="table-row">';
                    $htmlcontent .= '<td class="table-data">' . $type . '/' . $name . '</td>';
                    $showSuccess = '<td class="table-data success">✅</td>';
                    $count = $scorecardData['counts'][$name];
                    if ($count < $clusterCount) {
                        $Remark = $type . '/' . $name . ' found in only ' . $count . ' out of ' . $clusterCount . ' clusters. It must be created on all PROD/DMZ clusters where project is created. ';
                        if ($DCScore < 10) {
                            $Remark .= 'Also, This project is only available in ';
                            if ($numOfProdAvailability > 0 && $numOfDMZAvailability > 0) {
                                $Remark .= 'PROD Clusters of ' . implode(", ", $prodDcList) . ' DC(s) & DMZ Clusters of ' . implode(", ", $dmzDcList) . ' DC(s). ';
                            } elseif ($numOfProdAvailability == 0 && $numOfDMZAvailability > 0) {
                                $Remark .= 'DMZ Clusters of ' . implode(", ", $dmzDcList) . ' DC(s). ';
                            } else {
                                $Remark .= 'PROD Clusters of ' . implode(", ", $prodDcList) . ' DC(s). ';
                            }
                            $Remark .= ' It must be created in atleast 3 DCs for ✅, or in 2 DCs for ⚠';
                        }
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        if ($DCScore < 10) {
                            $Remark = 'This project is only available in ';
                            if ($numOfProdAvailability > 0 && $numOfDMZAvailability > 0) {
                                $Remark .= 'PROD Clusters of ' . implode(", ", $prodDcList) . ' DC(s) & DMZ Clusters of ' . implode(", ", $dmzDcList) . ' DC(s).';
                            } elseif ($numOfProdAvailability == 0 && $numOfDMZAvailability > 0) {
                                $Remark .= 'DMZ Clusters of ' . implode(", ", $dmzDcList) . ' DC(s).';
                            } else {
                                $Remark .= 'PROD Clusters of ' . implode(", ", $prodDcList) . ' DC(s).';
                            }
                            $Remark .= ' It must be created in atleast 3 DCs for ✅, or in 2 DCs for ⚠';
                            if ($DCScore == 6) {
                                $htmlcontent .= '<td class="table-data warning" onclick="showRemark(\'' . $Remark . '\')">⚠</td>';
                                $score = $score + $scoreForWarning;
                            } else {
                                $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                            }
                        } else {
                            $htmlcontent .= $showSuccess;
                            $score = $score + $scoreForSuccess;
                        }
                    }
                    $failedReplicaCluster = $scorecardData['failedReplicaClusters'][$name];
                    if (count($failedReplicaCluster) > 0) {
                        $Remark = $type . '/' . $name . ' has ' . implode(", ", $failedReplicaCluster) . '.';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $missingAntiAffinity = $scorecardData['missingAntiAffinities'][$name];
                    $missingRequiredAntiAffinity = $scorecardData['missingRequiredAntiAffinities'][$name];
                    if (count($missingAntiAffinity) > 0 && count($missingRequiredAntiAffinity) == 0) {
                        $Remark = $type . '/' . $name . ' is missing podAntiAffinity configuration in ' . implode(", ", $missingAntiAffinity) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } elseif (count($missingRequiredAntiAffinity) > 0 && count($missingAntiAffinity) == 0) {
                        $Remark = $type . '/' . $name . ' is has preffered type podAntiAffinity configured in ' . implode(", ", $missingRequiredAntiAffinity) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data warning" onclick="showRemark(\'' . $Remark . '\')">⚠</td>';
                        $score = $score + $scoreForWarning;
                    } elseif (count($missingRequiredAntiAffinity) > 0 && count($missingAntiAffinity) > 0) {
                        $Remark = $type . '/' . $name . ' is missing podAntiAffinity configuration in ' . implode(", ", $missingAntiAffinity) . ' cluster(s). Also, It has preffered type podAntiAffinity configured in ' . implode(", ", $missingRequiredAntiAffinity) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $missingLivenessProbe = $scorecardData['missingLivenessProbes'][$name];
                    if (count($missingLivenessProbe) > 0) {
                        $Remark = 'One or more containers in ' . $type . '/' . $name . ' is missing livenessProbe in ' . implode(", ", $missingLivenessProbe) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $missingReadinessProbe = $scorecardData['missingReadinessProbes'][$name];
                    if (count($missingReadinessProbe) > 0) {
                        $Remark = 'One or more containers in ' . $type . '/' . $name . ' is missing readinessProbe in ' . implode(", ", $missingReadinessProbe) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $missingStartupProbe = $scorecardData['missingStartupProbes'][$name];
                    if (count($missingStartupProbe) > 0) {
                        $Remark = 'One or more containers in ' . $type . '/' . $name . ' is missing startupProbe in ' . implode(", ", $missingStartupProbe) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    if ($type == 'StatefulSet') {
                        $Remark = $type . '/' . $name . ' is stateful.';
                        $htmlcontent .= '<td class="table-data warning" onclick="showRemark(\'' . $Remark . '\')">⚠</td>';
                        $score = $score + $scoreForWarning;
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $usingExternalImage = $scorecardData['usingExternalImages'][$name];
                    $using3rdPartyImage = $scorecardData['using3rdPartyImages'][$name];
                    if (count($usingExternalImage) > 0) {
                        $Remark = 'One or more containers in ' . $type . '/' . $name . ' is using Image from External Source in ' . implode(", ", $usingExternalImage) . ' cluster(s). ';
                        if (count($using3rdPartyImage) > 0) {
                            $Remark .= 'One or more containers in ' . $type . '/' . $name . ' is using Image from 3rd Party Source in ' . implode(", ", $using3rdPartyImage) . ' cluster(s).';
                        }
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } elseif (count($using3rdPartyImage) > 0) {
                        $Remark = 'One or more containers in ' . $type . '/' . $name . ' is using Image from 3rd Party Source in ' . implode(", ", $using3rdPartyImage) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data warning" onclick="showRemark(\'' . $Remark . '\')">⚠</td>';
                        $score = $score + $scoreForWarning;
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $missingIfNotPresentPullPolicy = $scorecardData['missingIfNotPresentPullPolicies'][$name];
                    if (count($missingIfNotPresentPullPolicy) > 0) {
                        $Remark = 'One or more containers in ' . $type . '/' . $name . ' is missing IfNotPresent Image Pull Policy in ' . implode(", ", $missingIfNotPresentPullPolicy) . ' cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        $htmlcontent .= $showSuccess;
                        $score = $score + $scoreForSuccess;
                    }
                    $hpa = $scorecardData['hpas'][$name];
                    if (!isset($hpa)) {
                        $Remark = 'HPA not found for ' . $type . '/' . $name . 'on any of the target cluster(s).';
                        $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                    } else {
                        if (count($hpa) < $targetclusters) {
                            $Remark = 'HPA for ' . $type . '/' . $name . ' only found on ' . implode(", ", $hpa). ' cluster(s).';
                            $htmlcontent .= '<td class="table-data danger" onclick="showRemark(\'' . $Remark . '\')">❌</td>';
                        } else {
                            $htmlcontent .= $showSuccess;
                            $score = $score + $scoreForSuccess; 
                        }
                    }
                    $htmlcontent .= '</tr>';
                }
            }

            $htmlcontent .= '
                </tbody>
            </table>
            ';

            $score = $score / (count($deploymentNames) + count($deploymentConfigNames) + count($statefulsetNames));
        }
    } else {

        $htmlcontent .= '
        <p>Currently, This project is not deployed on any PROD/DMZ clusters to generate scorecard.</p>
        ';

    }

    $htmlcontent .= '
        <div class="scorebar" id="scorebar">
            <p>Average Score Per Configuration = ' . $score . '</p>
        </div>
    </div>
    </div>
</body>
</html>
    ';

    $handle = fopen($scorecardPath, 'w');
    fwrite($handle, $htmlcontent);
    fclose($handle);
    return true;
}

function generateScorecard($cnx, $project) {
    $scorecardPath = '../scorecard/' . $project . '.html';
    if (!createScorecard($cnx, $project, $scorecardPath)) {
        $warningMessage = '<p style="color: red;"> Warning: Failed to Generate Scorecard. Showing Previously Generated Scorecard. </p>';
        $handle = fopen($scorecardPath, 'a');
        fwrite($handle, $warningMessage);
        fclose($handle);
    }
    if (file_exists($scorecardPath)) {
        return $scorecardPath;
    } else {
        http_response_code(500);
        return json_encode(['success' => false, 'error' => 'updateScorecard succeeded, but scorecard file not created. Please check server issue.']);
    }
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