<?php

include_once ("ProcessManager.php");
include_once ("Site.php");

$p = new ProcessManager();
$procRun = $p->getProceRunning("svn");
$procInDb = $p->getProcInDb();
$data['running'] = array();
foreach ($procInDb as $pid => $site) {
    if (in_array($pid, $procRun)) {
        $data['running'][] = $site;
    } else {
        $site = new Site($site);
        if (file_exists(BASE_PATH . $site)) {
            $site->migrateWp();
            $site->cleanFiles();
        }
        $site->removeProcessRunning($pid);
    }
}
header('content-type: application/json; charset=utf-8');
echo json_encode($data) . "\n";
