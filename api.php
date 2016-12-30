<?php

include_once ("ProcessManager.php");
include_once ("Site.php");

$p = new ProcessManager();
$procRun = $p->getProceRunning("svn");
$procInDb = $p->getProcInDb();
$data['running'] = array();
$data['reload']=false;
foreach ($procInDb as $pid => $site) {
    if (in_array($pid, $procRun)) {
        $data['running'][] = $site;
    } else {
        $s = new Site($site);
        if (file_exists(BASE_PATH . $site)) {
            $s->migrateWp();
            $s->cleanFiles();
        }
        $s->removeProcessRunning($pid);
        $data['reload'] = true;
    }
}
header('content-type: application/json; charset=utf-8');
echo json_encode($data) . "\n";
