<?php


/**
 * Description of Executer
 *
 * @author Miro
 */
class Executer {

    public $pid;
    private $stdOut = null;
    private $retCode;

    function execute($command, $background = false) {
        if (DEBUG) {
            echo $command . "</br>";
        }
        $output = array();
        if ($background) {
            if (substr(php_uname(), 0, 7) == "Windows") {
                $WshShell = new COM("WScript.Shell");
                $oExec = $WshShell->exec($command);
                $this->pid = ( $oExec->ProcessID );
            } else {
                exec('nohup '.$command.' > /dev/null 2>&1 & echo $!', $output, $return_var);
                $this->pid = (int) $output[0];
                $this->stdOut = $output;
                $this->retCode = $return_var;
            }
        } else {
            set_time_limit(PHP_INT_MAX);
            exec($command . " 2>&1", $output, $return_var);
            $this->stdOut = $output;
            $this->retCode = $return_var;
        }
    }

    public function getPid() {
        return $this->pid;
    }

    public function getStdOut() {
        return $this->stdOut;
    }

    public function getRetCode() {
        return $this->retCode;
    }

    public function getOutput() {
        $result = "";
        if ($this->stdOut != null) {
            if (is_array($this->stdOut)) {
                foreach ($this->stdOut as $line) {
                    $result .= $line . "</br>";
                }
            } else {
                $result .= $this->stdOut . "</br>";
            }
        }
        return $result;
    }

}

?>
