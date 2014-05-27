<?php

require_once __DIR__ . '/odt.php';

class renderer_plugin_odt_odp extends renderer_plugin_odt_odt {

    /**
     * @var bool is there a slide open already?
     */
    protected $slideopen = false;

    /**
     * @var array all the document data
     */
    protected $data = array();

    /**
     * @var int track the current page (will be increased before first use)
     */
    protected $slidecount = -1;

    /**
     * @var string what part of a page do we have right now?
     */
    protected $currentcontext = '';


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
     * Finalizes the document
     */
    protected function close() {
        // close whatever is open
        $this->finishContext();


        /*
        // strip empty paragraphs
        $this->doc = preg_replace('#<text:p[^>]*>\s*</text:p>#', '', $this->doc);
        */

        // Apply the template, zip up everything and store it in $doc for caching and output
        $tpl       = $this->getTemplateFile();
        $this->doc = $this->applyTemplate($tpl);
    }

    protected function applyTemplate($tpl) {
        // Extract template
        $this->ZIP->Extract($tpl, $this->temp_dir);

        // work on the content file
        $content = io_readFile($this->temp_dir . '/content.xml');

        // find all relevant slides in the presentation
        preg_match_all('/<draw:page.*?(?:<\/draw:page>)/s', $content, $matches, PREG_SET_ORDER);
        $tplpages = array();
        foreach($matches as $match) {
            if(preg_match('/DOKUWIKI-ODP-TITLE|DOKUWIKI-ODP-CONTENT/', $match[0])) {
                $tplpages[] = $match[0];
            }
        }
        $tplpage_count = count($tplpages);
        if(!$tplpage_count) die('Failed to find pages with placeholders in template');

        // apply our aggregated content to the template pages we found
        $new_content = '';
        foreach($this->data as $num => $slide) {
            // see if a matching template page exists, otherwise use last one
            if(isset($tplpages[$num])) {
                $tpl = $tplpages[$num];
            } else {
                $tpl = $tplpages[$tplpage_count-1];
            }

            $page = $tpl;
            $page = preg_replace(
                array(
                     '/ draw:name=".*?" /',
                     '/<text:p>\s*DOKUWIKI-ODP-TITLE\s*<\/text:p>/',
                     '/<text:p>\s*DOKUWIKI-ODP-CONTENT\s*<\/text:p>/'
                ),
                array(
                     ' draw:page="page'.($num+1).'" ',
                     $this->preg_replacement_quote('<text:p>'.$slide['title'].'</text:p>'),
                     $this->preg_replacement_quote($slide['slide'])
                ),
                $page
            );
            $new_content .= $page;
        }

        // now add our content instead of what was in the template
        $content = preg_replace('/<draw:page.*(?:<\/draw:page>)/s', $this->preg_replacement_quote($new_content), $content);
//print $content;
//exit;

        io_saveFile($this->temp_dir . '/content.xml', $content);

        // set styles and stuff
        $this->adjustStyles();

        // Build the Zip
        $this->ZIP->Compress(null, $this->temp_dir, null);
        io_rmdir($this->temp_dir, true);
        return $this->ZIP->get_file();
    }

    /**
     * Initializes a new empty slide structure
     *
     * @param int $slidenum
     */
    protected function initSlide($slidenum) {
        if(!isset($this->data[$slidenum])) {
            $this->data[$slidenum] = array(
                'title' => '',
                'notes' => '',
                'slide' => '',
            );
        }
    }

    /**
     * Finish the current context and stor data in structure
     */
    protected function finishContext() {
        // if no current context (before first header) skip
        if(!$this->currentcontext) return;

        $this->initSlide($this->slidecount);

        // add current $doc to data structure and reset $doc
        $this->data[$this->slidecount][$this->currentcontext] = $this->doc;
        $this->doc = '';

        // reset current context
        $this->currentcontext = '';
    }

    /**
     * Slice the document into pages at headers
     *
     * @param $text
     * @param $level
     * @param $pos
     */
    function header($text, $level, $pos) {
        // finish previous slide
        $this->finishContext();

        // start a new slide
        $this->slidecount++;
        $this->initSlide($this->slidecount);
        $this->data[$this->slidecount]['title'] = $text;
        $this->currentcontext = 'slide';
    }

    /**
     * Horizontal line switches to the notes section of a slide
     */
    function hr() {
        // if we've seen a HR before, ignore this one
        if($this->currentcontext == 'notes') return;

        // finish slide context
        $this->finishContext();

        // start new context
        $this->currentcontext = 'notes';
    }



}