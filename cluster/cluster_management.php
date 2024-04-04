<?php

header('Content-Type: application/json');
$referer = $_SERVER['HTTP_REFERER'];
require_once '../dbconfig.php';
require_once '../getfunction.php';

session_start();
if (!isset($_SESSION['role'])) {
    die(json_encode(['success' => false, 'error' => 'Session Expired. Please login again.']));
}

function getDCList($cnx) {
    $sql = "SELECT dc FROM dcList";
    $stmt = $cnx->prepare($sql);
    if (!$stmt) {
        $errors[] = ['message' => $cnx->error];
        return ['success' => false, 'errors' => $errors];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $dcList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $dcList[] = $row['dc'];
        }
    }
    return ['success' => true, 'dcList' => $dcList];
}

function getTypeList($cnx) {
    $sql = "SELECT typeName FROM typeList";
    $stmt = $cnx->prepare($sql);
        if (!$stmt) {
        $errors[] = ['message' => $cnx->error];
        return ['success' => false, 'errors' => $errors];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $typeList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $typeList[] = $row['typeName'];
        }
    }
    return ['success' => true, 'typeList' => $typeList];
}

function getClusterList($cnx) {
    $sql = "SELECT cluster, dc, typeName FROM clusterList";
    $stmt = $cnx->prepare($sql);
        if (!$stmt) {
        $errors[] = ['message' => $cnx->error];
        return ['success' => false, 'errors' => $errors];
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $clusterList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $clusterList[] = $row;
        }
    }
    return ['success' => true, 'clusterList' => $clusterList];
}

function addDC($cnx, $dc) {
    $dc = strtoupper($dc);
    $sql = "INSERT INTO dcList (dc) VALUES (?)";
    $stmt = $cnx->prepare($sql);
    $stmt->bind_param("s", $dc);
    if ($stmt->execute()) {
        return (['success' => true]);
    } else {
        return (['success' => false, 'error' => $cnx->error]);
    }
}

function addCluster($cnx, $dc, $type, $cluster) {
    $cluster = strtolower($cluster);
    $nslookup_api = dns_get_record('api.' . $cluster . '.india.airtel.itm', DNS_A);
    $nslookup_apps = dns_get_record('*.apps.' . $cluster . '.india.airtel.itm', DNS_A);
    if ($nslookup_api == false || $nslookup_apps == false) {
        return (['success' => false, 'error' => 'DNS_GET_RECORD failed. Please create "A" record for api.' . $cluster . '.india.airtel.itm & *.apps.' . $cluster . '.india.airtel.itm']);
    }
    $api_ip = $nslookup_api[0]['ip'];
    $apps_ip = $nslookup_apps[0]['ip'];
    $ping_api = shell_exec('ping -c 1 ' . $api_ip . ' | grep \'1 packets transmitted, 1 received, 0% packet loss\'');
    $ping_apps = shell_exec('ping -c 1 ' . $apps_ip . ' | grep \'1 packets transmitted, 1 received, 0% packet loss\'');
    if ($ping_api == false || $ping_api == '' || $ping_apps == false || $ping_apps == '') {
        return (['success' => false, 'error' => 'PING failed. Make sure that ' . $api_ip . ' & ' . $apps_ip . ' are pingable from Reporting Server.']);
    }
    $telnet_api = @fsockopen($api_ip, 6443, $errno, $errstr, 1);
    fclose($telnet_api);
    $telnet_apps = @fsockopen($apps_ip, 443, $errno, $errstr, 1);
    fclose($telnet_apps);
    if (!$telnet_api || !$telnet_apps) {
        return (['success' => false, 'error' => 'TELNET failed. Please allow communication to ' . $api_ip . ':6443 & ' . $apps_ip . ':443 from Reporting Server.']);
    }
    $token = makeGetRequest('https://oauth-openshift.apps.' . $cluster . '.india.airtel.itm/oauth/authorize?response_type=token&client_id=openshift-challenging-client', 'basic');
    if ($token == '') {
        return (['success' => false, 'error' => 'Unable to fetch TOKEN of monitor user for ' . $cluster . 'cluster. Make sure that you can login with monitor user to the cluster.']);
    }
    $sql = "INSERT INTO clusterList (dc, cluster, typeName) VALUES (?, ?, ?)";
    $stmt = $cnx->prepare($sql);
    $stmt->bind_param("sss", $dc, $cluster, $type);
    if ($stmt->execute()) {
        return (['success' => true]);
    } else {
        return (['success' => false, 'error' => $cnx->error]);
    }
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

switch ($action) {
    case 'getDCList':
        $dcList = getDCList($cnx);
        echo json_encode($dcList);
        break;
    case 'getTypeList':
        $typeList = getTypeList($cnx);
        echo json_encode($typeList);
        break;
    case 'getClusterList':
        $clusterList = getClusterList($cnx);
        echo json_encode($clusterList);
        break;
    case 'addDC':
        if ($_SESSION['role'] !== 'admin') {
            die(json_encode(['success' => false, 'error' => 'Cannot Perform Administration Action for Read-Only User. Please re-login with Admin privileges.']));
        }
        $addDCResult = addDC($cnx, $_POST['dc']);
        echo json_encode($addDCResult);
        break;
    case 'addCluster':
        if ($_SESSION['role'] !== 'admin') {
            die(json_encode(['success' => false, 'error' => 'Cannot Perform Administration Action for Read-Only User. Please re-login with Admin privileges.']));
        }
        $addClusterResult = addCluster($cnx, $_POST['dc'], $_POST['type'], $_POST['cluster']);
        echo json_encode($addClusterResult);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        break;
}

$cnx->close();

?>