<?php
/**
 * ODT Plugin: Exports to ODT
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 * @author     Aurelien Bompard <aurelien@bompard.org>
 *
 * @deprecated this is for backwards compatibility only and will be removed soonish
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


class syntax_plugin_odt_modifier extends DokuWiki_Syntax_Plugin {

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType() {
        return 'normal';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 319; // Before image detection, which uses {{...}} and is 320
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{odt>.+?}}', $mode, 'plugin_odt');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        // Extended info
        $match     = substr($match, 6, -2); //strip markup
        $extinfo   = explode(':', $match);
        $info_type = $extinfo[0];
        if(count($extinfo) < 2) { // no value
            $info_value = '';
        } elseif(count($extinfo) == 2) {
            $info_value = $extinfo[1];
        } else { // value may contain colons
            $info_value = implode(array_slice($extinfo, 1), ':');
        }
        return array($info_type, $info_value);
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID;
        list($info_type, $template) = $data;
        if($info_type != 'template') return false;

        if($format == 'metadata') {
            if($template) $renderer->meta['relation']['odt'] = array('template' => $template);
            return true;
        } elseif($format = 'odt') {
            if($template) $renderer->template = $template;
            return true;
        }
        return false;
    }

}

// vim: set et ts=4 sw=4 fileencoding=utf-8 :
