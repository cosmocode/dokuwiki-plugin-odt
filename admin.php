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
 
    var $output = 'world';
 
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
 
    /**
     * handle user request
     */
    function handle() {
 
        if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do
 
        $this->output = 'invalid';
 
        if (!is_array($_REQUEST['cmd'])) return;
 
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'hello' : $this->output = 'again'; break;
            case 'goodbye' : $this->output = 'goodbye'; break;
        }      
    }
 
    /**
     * output appropriate html
     */
    function html() {
        ptln('<h1>'.$this->getLang('manage_tpl').'</h1>');
        ptln('<form action="'.wl($ID).'" method="post">');
 
        // output hidden values to ensure dokuwiki will return back to this plugin
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
 
        ptln('<h2>'.$this->getLang('delete_existing').'</h2>');
        ptln('  <div class="level2"><p>');
        $tpl_path = DOKU_PLUGIN.'odt/templates';
        $dir = opendir($tpl_path);
        while (false !== ($filename = readdir($dir))) {
            if ($filename == "." || $filename == "..") continue;
            $extension = substr($filename, strrpos($filename, '.')+1);
            if (is_file($tpl_path.'/'.$filename) && $extension == "odt") {
                ptln('<input type="checkbox" name="'.htmlentities($filename).'" /> '.htmlentities($filename).'<br />');
            }
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
