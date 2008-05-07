<?php
/**
 * ODT Plugin: Exports to ODT
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Aurelien Bompard <aurelien@bompard.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_odt extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'normal';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 319; // Before image detection, which uses {{...}} and is 320
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~ODT~~',$mode,'plugin_odt');
        $this->Lexer->addSpecialPattern('{{odt>.+?}}',$mode,'plugin_odt');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        // Export button
        if ($match == '~~ODT~~') { return array(); }
        // Extended info
        $match = substr($match,6,-2); //strip markup
        $extinfo = explode(':',$match);
        $info_type = $extinfo[0];
        if (count($extinfo) < 2) { // no value
            $info_value = '';
        } elseif (count($field) == 2) {
            $info_value = $extinfo[1];
        } else { // value may contain colons
            $info_value = implode(array_slice($extinfo,1), ':');
        }
        return array($info_type, $info_value);
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID;
        if (!$data) { // Export button
            if($format != 'xhtml') return false;

            $renderer->doc .= '<a href="'.exportlink($ID, 'odt').'" title="'.$this->getLang('view').'">';
            $renderer->doc .= '<img src="'.DOKU_BASE.'lib/plugins/odt/odt.png" align="right" alt="'.$this->getLang('view').'" width="48" height="48" />';
            $renderer->doc .= '</a>';
            return true;
        } else { // Extended info
            list($info_type, $info_value) = $data;
            if ($info_type == "field" or $info_type == "property") { // User-defined fields
                $field = explode('=',$info_value);
                $fname = $field[0];
                if (count($field) < 2) { // no value -> get the field
                    if ($format == 'odt') {
                        if ($info_type == "field")
                            $renderer->_odtInsertUserField($fname);
                        if ($info_type == "property")
                            $renderer->_odtInsertProperty($fname);
                    } elseif ($format == 'xhtml' && isset($renderer->fields) && array_key_exists($fname, $renderer->fields)) {
                        $renderer->doc .= $renderer->fields[$fname];
                    }
                    return true;
                }
                // set field
                if (count($field) == 2) {
                    $fvalue = $field[1];
                } else { // field value may contain equal signs
                    $fvalue = implode(array_slice($field,1), '=');
                }
                if ($format == 'odt') {
                    if ($info_type == "field")
                        $renderer->_odtAddUserField($fname, $fvalue);
                    if ($info_type == "property")
                        $renderer->_odtAddProperty($fname, $fvalue);
                } elseif ($format == 'xhtml') {
                    if (!isset($renderer->fields)) {
                        $renderer->fields = array();
                    }
                    $renderer->fields[$fname] = $fvalue;
                }
                return true;
            } elseif ($info_type == "template") { // Template-based
                $renderer->template = $info_value;
            }
        }
        return false;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
