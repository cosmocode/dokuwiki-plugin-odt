<?php
/**
 * ODT Plugin: Manage the templates
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Aurelien Bompard <aurelien@bompard.org>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');
 
class admin_plugin_odt extends DokuWiki_Admin_Plugin {
 
    var $messages = array();
 
    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }
 
    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 999;
    }
 
    function reportSuccess($message) {
        return '<p style="border:1px solid green">'.$message.'</p>';
    }

    function reportFailure($message) {
        return '<p style="border:1px solid red">'.$message.'</p>';
    }

    /**
     * handle user request
     */
    function handle() {
        $tpl_path = DOKU_PLUGIN.'odt/templates';
        if (isset($_REQUEST["delete"])) {
            foreach ($_REQUEST["del_tpl"] as $tpl) {
                if (strpos($tpl, "/") !== FALSE) continue; // security: dont cross directories
                if (unlink($tpl_path."/".$tpl)) {
                    $this->messages []= $this->reportSuccess(sprintf($this->getLang('success_del'), $tpl));
                } else {
                    $this->messages []= $this->reportFailure(sprintf($this->getLang('failure_del'), $tpl));
                }
            }
        } elseif (isset($_REQUEST["upload"])) {
            print_r($_FILES);
            $filename = $_FILES['new_tpl']['name'];
            $extension = substr($filename, strrpos($filename, '.')+1);
            if ($extension != "odt") {
                $this->messages []= $this->reportFailure(sprintf($this->getLang('failure_upload_type'), $filename));
                return;
            }
            if (move_uploaded_file($_FILES['new_tpl']['tmp_name'], $tpl_path."/".$filename) ) {
                $this->messages []= $this->reportSuccess(sprintf($this->getLang('success_upload'),  $filename));
            } else {
                $this->messages []= $this->reportFailure(sprintf($this->getLang('failure_upload'),  $filename));
            }
        }
    }
 
    /**
     * output appropriate html
     */
    function html() {
        ptln('<h1>'.$this->getLang('manage_tpl').'</h1>');
        ptln('<div class="level1"><p>'.$this->getLang('contain').'</p></div>');
        $tpl_path = DOKU_PLUGIN.'odt/templates';

        // read the templates dir
        $templates = array();
        $dir = opendir($tpl_path);
        while (false !== ($filename = readdir($dir))) {
            if ($filename == "." || $filename == "..") continue;
            $extension = substr($filename, strrpos($filename, '.')+1);
            if (is_file($tpl_path.'/'.$filename) && $extension == "odt") {
                $templates []= $filename;
            }
        }

        // no access to the templates dir
        if (!is_writable($tpl_path)) {
            ptln('<div class="level1"><p>'.$this->getLang('no_access').'</p>');
            ptln('<ul>');
            foreach ($templates as $filename) {
                ptln('<li>'.htmlentities($filename).'</li>');
            }
            ptln('<ul></div>');
            return;
        }

        // messages
        foreach ($this->messages as $msg) {
            ptln($msg);
        }

        // form
        ptln('<form action="'.wl($ID).'" method="post">');
 
        // output hidden values to ensure dokuwiki will return back to this plugin
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
 
        ptln('<h2>'.$this->getLang('delete_existing').'</h2>');
        ptln('  <div class="level2"><p>');
        foreach ($templates as $filename) {
            ptln('<input type="checkbox" name="del_tpl[]" value="'.htmlentities($filename).'" /> '.htmlentities($filename).'<br />');
        }
        ptln('  <input type="submit" name="delete"  value="'.$this->getLang('btn_delete').'" />');
        ptln(' </p></div>');
        ptln('<h2>'.$this->getLang('upload_new').'</h2><div class="level2">');
        ptln('  <input type="file" name="new_tpl" />');
        ptln('  <input type="submit" name="upload"  value="'.$this->getLang('btn_upload').'" />');
        ptln(' </div>');

        ptln('</form>');
    }
 
}
