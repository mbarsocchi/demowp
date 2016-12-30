<?php

include_once ("SubversionWrapper.php");
include_once ("config.php");
include_once ("DBCloner.php");

/**
 * Description of Template
 *
 * @author mbarsocchi
 */
class Site {

    private $name;
    private $dbName;
    private $svnCli;
    private $dbClone;

    function __construct($name) {
        $this->name = $name;
        $this->dbName = "db_" . $name;
        $this->svnCli = new SubversionWrapper($name, SVN_USER, SVN_PASSWORD);
        $this->dbClone = new DBCloner(MYSQL_USER_NAME, MYSQL_PASSWORD, MYSQL_HOST);
        $this->dbClone->setDestName($this->name);
    }

    function getName() {
        return $this->name;
    }

    function getDbName() {
        return $this->dbName;
    }

    private function delTree($dir) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
    
    function removeProcessRunning($pid){
        $sql = "DELETE FROM `" . DB_SITEDEMO_NAME . "`.`processrunning` 
WHERE pid = " . $pid;
        if (!$this->dbClone->getCon()->query($sql)) {
            return false;
        }
        return true;
    }

    function insertProcessRunning($site, $pid) {
        $sql = "INSERT INTO `" . DB_SITEDEMO_NAME . "`.`processrunning` 
(`site`,`pid`) 
VALUES
(\"" . $site . "\", " . $pid . ")";
        if (!$this->dbClone->getCon()->query($sql)) {
            return false;
        }
        return true;
    }

    private function importDb() {
        $res = $this->svnCli->cechkoutDBFile();
        if ($res['code'] == 0) {
            $res = $this->dbClone->migrate($this->dbName, "tmp" . DIRECTORY_SEPARATOR . $this->dbName . ".sql", $this->name, $this->name);
            if ($res['code'] == 0) {
                unlink("tmp" . DIRECTORY_SEPARATOR . $this->dbName . ".sql");
                return true;
            } else {
                echo $this->dbClone->errormsg;
                return false;
            }
        } else {
            echo $res['output'];
            return false;
        }
    }

    function migrateWp() {
        $wpconfigFilename = BASE_PATH . $this->name . DIRECTORY_SEPARATOR . "wp-config.php";
        $content = file_get_contents($wpconfigFilename);
        $pattern = "/define\('DB_NAME', '.+'\);/i";
        $content = preg_replace($pattern, "define('DB_NAME', '" . $this->dbName . "');", $content);
        $pattern = "/define\('DB_HOST', '.+'\);/i";
        $content = preg_replace($pattern, "define('DB_HOST', '" . MYSQL_HOST . "');", $content);
        $pattern = "/define\('DB_USER', '.+'\);/i";
        $content = preg_replace($pattern, "define('DB_USER', '" . MYSQL_USER_NAME . "');", $content);
        $pattern = "/define\('DB_PASSWORD', '.+'\);/i";
        $content = preg_replace($pattern, "define('DB_PASSWORD', '" . MYSQL_PASSWORD . "');", $content);
        file_put_contents($wpconfigFilename, $content);

        $htaccessFilename = BASE_PATH . $this->name . DIRECTORY_SEPARATOR . ".htaccess";
        $content = file_get_contents($htaccessFilename);
        $pattern = "/RewriteBase \/.+/i";
        $content = preg_replace($pattern, "RewriteBase /" . $this->name, $content);
        $pattern = "/RewriteRule . \/.+\//i";
        $content = preg_replace($pattern, "RewriteRule . /" . $this->name . "/", $content);
        file_put_contents($htaccessFilename, $content);
    }
    
    function cleanFiles(){
        unlink(BASE_PATH.$this->name.DIRECTORY_SEPARATOR.$this->name. "st.obj");
        unlink(BASE_PATH.$this->name.DIRECTORY_SEPARATOR.$this->dbName.".sql");
    }

    function getFromRepo() {
        if ($this->dbClone->createDb($this->dbName)) {
            return $this->update();
        } else {
            echo $this->dbClone->errormsg;
        }
    }

    function update() {
        if ($this->importDb()) {
            $res = $this->svnCli->checkout();
            $this->insertProcessRunning($this->name, $res['pid']);
        } else {
            $res['code']=-1;
            $res['output']=$this->dbClone->errormsg;
        }
        return $res;
    }

    function delete() {
        $this->dbClone->cancellDB($this->dbName);
        $this->delTree(BASE_PATH . $this->name);
    }

}
