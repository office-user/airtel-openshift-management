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

function populateProjectList($cnx) {
    $sql = "SELECT DISTINCT project FROM projectList ORDER BY project ASC";
    $stmt = $cnx->prepare($sql);
    error_log("populateProjectList: Preparing SQL statement: $sql");
        if (!$stmt) {
        $errors[] = ['message' => $cnx->error];
        error_log("populateProjectList: Error preparing SQL statement: " . $cnx->error);
        return ['success' => false, 'errors' => $errors];
    }
    $stmt->execute();
    error_log("populateProjectList: SQL statement executed");
    $result = $stmt->get_result();
    $projectList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $projectList[] = $row['project'];
        }
    }
    return ['success' => true, 'projectList' => $projectList];
}

function populateProjectScorecardRecord($cnx) {
    $projectScorecardRecord = [];
    if ($handle = opendir('../scorecard/')) {
        while (false !== ($entry = readdir($handle))) {
            if (pathinfo($entry, PATHINFO_EXTENSION) == 'html') {
                $projectScorecardRecord[] = ['project' => pathinfo($entry, PATHINFO_FILENAME), 'creationDate' => filemtime('../scorecard/' . $entry)];
            }
        }
        closedir($handle);
        usort($projectScorecardRecord, function($a, $b) {
            if ($a['creationDate'] === $b['creationDate']) {
                return strcmp($a['project'], $b['project']);
            }
            return $b['creationDate'] - $a['creationDate'];
        });
        foreach ($projectScorecardRecord as &$scorecard) {
            $scorecard['creationDate'] = ($scorecard['creationDate'] !== '') ? date('F d, Y h:i:s A', $scorecard['creationDate']) : '' ;
        }
    }
    return ['success' => true, 'projectScorecardRecord' => $projectScorecardRecord];
}

function getEmailAddresses($cluster, $project, $token) {
    $projectRoleBindings = json_decode(makeGetRequest('https://api.' . $cluster . '.india.airtel.itm:6443/apis/rbac.authorization.k8s.io/v1/namespaces/' . $project . '/rolebindings', 'bearer', $token), true);
    if (isset($projectRoleBindings['items'])) {
        $projectAdminGroups = [];
        foreach ($projectRoleBindings['items'] as $projectRoleBinding) {
            if ($projectRoleBinding['roleRef']['name'] == 'admin') {
                foreach ($projectRoleBinding['subjects'] as $subject) {
                    if ($subject['kind'] == 'Group') {
                        $projectAdminGroups[] = $subject['name'];
                    }
                }
            }
        }
        $projectEmails = array_map(function($groupName) {
            return $groupName . '@airtel.com';
        }, $projectAdminGroups);
        return implode(', ', $projectEmails);
    }
    return '';
}

function updateProjectList($cnx) {
    $ocPath = "/usr/local/bin/oc";
    $errors = [];
    $sql = "SELECT cluster, dc, typeName FROM clusterList";
    $stmt = $cnx->prepare($sql);
    error_log("updateProjectList: Preparing SQL statement: $sql");
    if (!$stmt) {
        $errors[] = ['message' => $cnx->error];
        error_log("updateProjectList: Error preparing SQL statement: " . $cnx->error);
        return ['success' => false, 'errors' => $errors];
    }
    $stmt->execute();
    error_log("updateProjectList: SQL statement executed");
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        error_log("updateProjectList: Cluster List Empty");
        return ['success' => true];
    }
    $cnx->begin_transaction();
    $sql = "DELETE FROM projectList";
    $stmt = $cnx->prepare($sql);
    error_log("updateProjectList: Preparing SQL statement: $sql");
    if (!$stmt) {
        $errors[] = ['message' => $cnx->error];
        $cnx->rollback();
        error_log("updateProjectList: Error preparing SQL statement: " . $cnx->error);
        return ['success' => false, 'errors' => $errors];
    }
    $stmt->execute();
    error_log("updateProjectList: SQL statement executed");
    //$sql = "INSERT IGNORE INTO projectList (dc, typeName, cluster, project, emailAddresses) VALUES (?, ?, ?, ?, ?)";
    //$stmt = $cnx->prepare($sql);
    //error_log("updateProjectList: Preparing SQL statement: $sql");
    //if (!$stmt) {
    //    $errors[] = ['message' => $cnx->error];
    //    $cnx->rollback();
    //    error_log("updateProjectList: Error preparing SQL statement: " . $cnx->error);
    //    return ['success' => false, 'errors' => $errors];
    //}
    $projectData = [];
    while ($row = $result->fetch_assoc()) {
        $token = makeGetRequest('https://oauth-openshift.apps.' . $row['cluster'] . '.india.airtel.itm/oauth/authorize?response_type=token&client_id=openshift-challenging-client', 'basic');
        if ($token == '') {
            $errors[] = ['cluster' => $row['cluster'], 'message' => 'Error fetching Token.'];
            error_log("updateProjectList: Error Fetching Token for " . $row['cluster']);
        } else {
            $clusterProjects = json_decode(makeGetRequest('https://api.' . $row['cluster'] . '.india.airtel.itm:6443/apis/project.openshift.io/v1/projects/?fieldSelector=status.phase%3DActive', 'bearer', $token), true);
            if (isset($clusterProjects['items'])) {
                foreach ($clusterProjects['items'] as $item) {
                    $projectName = $item['metadata']['name'];
                    if (preg_match('/^(openshift|kube-|default$|ldap-sync$)/', $projectName) === 0) {
                        $projectEmails = getEmailAddresses($row['cluster'], $projectName, $token);
                        if ($projectEmails !== '') {
                            $projectData[] = "('" . $row['dc'] . "', '" . $row['typeName'] . "', '" . $row['cluster'] . "', '" . $projectName . "', '" . $projectEmails . "')";
                            //error_log("Inserting => " . $row['dc'] . ' ' . $row['typeName'] . ' ' . $row['cluster'] . ' ' . $projectName . ' ' . $projectEmails);
                            //$stmt->bind_param("sssss", $row['dc'], $row['typeName'], $row['cluster'], $projectName, $projectEmails);
                            //if (!$stmt->execute()) {
                            //    error_log("updateProjectList: Error executing SQL statement: " . $cnx->error);
                            //    $errors[] = ['cluster' => $cluster, 'message' => $cnx->error];
                            //}
                        }
                    }
                }
            }
        }
    }
    if (!empty(projectData)) {
        $values = implode(', ', $projectData);
        $stmt = $cnx->prepare("INSERT IGNORE INTO projectList (dc, typeName, cluster, project, emailAddresses) VALUES $values");
        if (!$stmt->execute()) {
            error_log("updateProjectList: Errorexecuting SQL statement: " . $cnx->error);
            $errors[] = ['message' => $cnx->error];
        }
    }
    if (count($errors) > 0) {
        $cnx->rollback();
        return ['success' => false, 'errors' => $errors];
    }
    $cnx->commit();
    return ['success' => true];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
error_log("Action: $action");

switch ($action) {
    case 'populateProjectList':
        $populateProjectListResult = populateProjectList($cnx);
        error_log("populateProjectListResult: " . json_encode($populateProjectListResult));
        echo json_encode($populateProjectListResult);
        break;
    case 'populateProjectScorecardRecord':
        $populateProjectScorecardRecord = populateProjectScorecardRecord($cnx);
        error_log("populateProjectScorecardRecord: " . json_encode($populateProjectScorecardRecord));
        echo json_encode($populateProjectScorecardRecord);
        break;
    case 'updateProjectList':
        $updateProjectListResult = updateProjectList($cnx);
        error_log("updateProjectListResult: " . json_encode($updateProjectListResult));
        echo json_encode($updateProjectListResult);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => 'Invalid request']);
        break;
}

$cnx->close();

?>