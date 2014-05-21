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


class syntax_plugin_odt_export extends DokuWiki_Syntax_Plugin {

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
        $this->Lexer->addSpecialPattern('~~OD[TP](?: [^~]+)~~', $mode, 'plugin_odt_export');

    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        $match = substr($match,2,-2); // strip '~~'

        list($type, $template) = explode(' ', $match);
        $template = cleanID($template);
        $type = strtolower($type);

        return array($type, $template);
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID, $REV;

        list($type, $template) = $data;
        switch ($format) {
            case 'xhtml':
                // display export button
                $renderer->doc .= '<a href="' . exportlink($ID, 'odt_'.$type, ($REV != '' ? 'rev=' . $REV : '')) . '" title="' . $this->getLang('view') . '">';
                $renderer->doc .= '<img src="' . DOKU_BASE . 'lib/plugins/odt/'.$type.'.png" align="right" alt="' . $this->getLang('view') . '" width="48" height="48" />';
                $renderer->doc .= '</a>';
                return true;
            case $type:
                // set template directly in the renderer
                if($template) $renderer->template = $template;
                return true;
            case 'metadata':
                // store template in metadata (for cache adjustment)
                if($template) $renderer->meta['relation']['odt'] = array('template' => $template);
                return true;
        }

        return false;
    }

}

// vim: set et ts=4 sw=4 fileencoding=utf-8 :
