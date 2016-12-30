<?php

include_once ("TemplatePagePlugin.php");
include_once ("SubversionWrapper.php");
include_once ("ProcessManager.php");
include_once ("config.php");
include_once ("Site.php");

if (isset($_GET['f'])) {
    $site = new Site($_GET['n']);
    switch ($_GET['f']) {
        case 'c':
            $r = $site->getFromRepo();
            break;
        case 'd':
            $site->delete();
            break;
        case 'u':
            $r = $site->update();
            break;
        default:
            break;
    }
    if ($r['code'] != 0) {
        echo $r['output'];
    } else {
        sleep(5);
        header("Location: index.php");
    }
}
$data['site'] = array();
$p = new ProcessManager();
$procRun = $p->getProceRunning("svn");
$procInDb = $p->getProcInDb();
foreach ($procInDb as $pid => $site) {
    if (in_array($pid, $procRun)) {
        $data['running'][] = $site;
    }
}
$svnCli = new SubversionWrapper(null, SVN_USER, SVN_PASSWORD);
$files = glob(BASE_PATH . "*");
$data['site'] = $svnCli->listAllRepo();
foreach ($files as $file) {
    $basename = basename($file);
    if (is_dir($file) && ($key = array_search($basename, $data['site'])) !== false) {
        $data['isLocal'][$basename] = true;
    }
}
$tmpl = new TemplatePagePlugin(__DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'home.php', $data);
echo $tmpl->render();
