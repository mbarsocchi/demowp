<?php

include_once("Executer.php");

/**
 * Description of SubversionWrapper
 *
 * @author Miro
 */
class SubversionWrapper {

    private $repos;
    private $username;
    private $password;

    function __construct($repos, $username, $password) {
        $this->repos = $repos;
        $this->username = $username;
        $this->password = $password;
        $this->exec = new Executer();
    }

    public function getRepos() {
        return $this->repos;
    }

    public function setRepos($repos) {
        $this->repos = $repos;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getHasError() {
        return $this->hasError;
    }

    function cechkoutDBFile() {
        $command = "svn export http://" . SVN_SERVER . "/svn/" . $this->repos . "/db_" . $this->repos . ".sql tmp/db_" . $this->repos . ".sql --username " . SVN_USER . " --password " . SVN_PASSWORD . " --force";
        $this->exec->execute($command, false);
        $res['code'] = $this->exec->getRetCode();
        $res['output'] = $this->exec->getOutput();
        return $res;
    }

    function checkout() {
        $command = "svn export http://" . SVN_SERVER . "/svn/" . $this->repos . " " . BASE_PATH . $this->repos . " --username " . SVN_USER . " --password " . SVN_PASSWORD. " --force";
        $this->exec->execute($command, true);
        $res['code'] = $this->exec->getRetCode();
        $res['output'] = $this->exec->getOutput();
        $res['pid'] = $this->exec->getPid();
        return $res;
    }

    function listAllRepo() {
        $useragent = "Mozilla Firefox";
        $ch = curl_init();
        $url = 'http://' . SVN_SERVER . '/list.php?l=1';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, SVN_USER_ADMIN . ":" . SVN_PASSWORD_ADMIN);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $result = curl_exec($ch);
        curl_close($ch);
        $resAsArray = json_decode($result, true);
        if ($resAsArray["return_var"] == 0 && isset($resAsArray["output"])) {
            return $resAsArray["output"];
        }
    }

}
