<?php

require_once __DIR__ . '/odt.php';

class renderer_plugin_odt_odp extends renderer_plugin_odt_odt {

    /**
     * @var bool is there a slide open already?
     */
    protected $slideopen = false;

    /**
     * @var int counts the number of slides
     */
    protected $slides = 0;

    /**
     * Returns the mime type entry
     *
     * @return string
     */
    protected function getMimeType() {
        return 'application/vnd.oasis.opendocument.presentation';
    }

    /**
     * returns the format 'odp'
     *
     * @return string
     */
    function getFormat() {
        return 'odp';
    }

    /**
     * Slice the document into pages at headers
     *
     * @todo fix all those inline styles
     *
     * @param $text
     * @param $level
     * @param $pos
     */
    function header($text, $level, $pos) {
        $this->slides++;

        if($level < 3) { // FIXME make configurable
            if($this->slideopen) {
                //close previous slide
                $this->doc .= '</draw:page>';
            }
            //open the new slide
            $this->doc .= '<draw:page draw:name="page"' . $this->slides . ' draw:style-name="dp1" draw:master-page-name="Default">';
            $this->slideopen = true;
        }

        // write the header
        $this->doc .= '<draw:frame presentation:style-name="pr4" draw:layer="layout" svg:width="25.199cm" svg:height="3.256cm" svg:x="1.4cm" svg:y="0.962cm" presentation:class="title">';
        $this->doc .= '<draw:text-box>';
        $this->doc .= '<text:p>';
        $this->doc .= $this->_xmlEntities($text);
        $this->doc .= '</text:p>';
        $this->doc .= '</draw:text-box>';
        $this->doc .= '</draw:frame>';

    }

}