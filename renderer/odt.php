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

/**
 * The Renderer
 */
class renderer_plugin_odt_odt extends Doku_Renderer {

    /** @var ZipLib OpenDocument is just a ZIP file */
    protected $ZIP = null;

    /** @var string the template to use (media ID relative to the configured template namespace) */
    protected $template = '';

    /** @var string temporary directory to assemble the zip file contents */
    protected $temp_dir;

    /** @var array the document metadata */
    protected $meta;

    /** @var string keeps $doc during footnote processing */
    protected $store = '';

    protected $manifest = array();
    protected $headers = array();
    protected $fields = array();

    /** @var bool flag for open list items */
    protected $in_list_item = false;

    /** @var bool flag for open paragraph */
    protected $in_paragraph = false;

    /** @var int CSS converted styles counter */
    protected $count_cssstyles = 1;

    /** @var int number of footnotes seen */
    protected $count_footnotes = 0;

    // Automatic styles. Will always be added to content.xml and styles.xml
    protected $autostyles = array(
        "pm1"              => '
            <style:page-layout style:name="pm1">
                <style:page-layout-properties fo:page-width="21cm" fo:page-height="29.7cm" style:num-format="1" style:print-orientation="portrait" fo:margin-top="2cm" fo:margin-bottom="2cm" fo:margin-left="2cm" fo:margin-right="2cm" style:writing-mode="lr-tb" style:footnote-max-height="0cm">
                    <style:footnote-sep style:width="0.018cm" style:distance-before-sep="0.1cm" style:distance-after-sep="0.1cm" style:adjustment="left" style:rel-width="25%" style:color="#000000"/>
                </style:page-layout-properties>
                <style:header-style/>
                <style:footer-style/>
            </style:page-layout>',
        "sub"              => '
            <style:style style:name="sub" style:family="text">
                <style:text-properties style:text-position="-33% 80%"/>
            </style:style>',
        "sup"              => '
            <style:style style:name="sup" style:family="text">
                <style:text-properties style:text-position="33% 80%"/>
            </style:style>',
        "del"              => '
            <style:style style:name="del" style:family="text">
                <style:text-properties style:text-line-through-style="solid"/>
            </style:style>',
        "underline"        => '
            <style:style style:name="underline" style:family="text">
              <style:text-properties style:text-underline-style="solid"
                 style:text-underline-width="auto" style:text-underline-color="font-color"/>
            </style:style>',
        "media"            => '
            <style:style style:name="media" style:family="graphic" style:parent-style-name="Graphics">
                <style:graphic-properties style:run-through="foreground" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit"
                   style:wrap-contour="false" style:vertical-pos="top" style:vertical-rel="baseline" style:horizontal-pos="left"
                   style:horizontal-rel="paragraph"/>
            </style:style>',
        "medialeft"        => '
            <style:style style:name="medialeft" style:family="graphic" style:parent-style-name="Graphics">
              <style:graphic-properties style:run-through="foreground" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit"
                 style:wrap-contour="false" style:horizontal-pos="left" style:horizontal-rel="paragraph"/>
            </style:style>',
        "mediaright"       => '
            <style:style style:name="mediaright" style:family="graphic" style:parent-style-name="Graphics">
              <style:graphic-properties style:run-through="foreground" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit"
                 style:wrap-contour="false" style:horizontal-pos="right" style:horizontal-rel="paragraph"/>
            </style:style>',
        "mediacenter"      => '
            <style:style style:name="mediacenter" style:family="graphic" style:parent-style-name="Graphics">
               <style:graphic-properties style:run-through="foreground" style:wrap="none" style:horizontal-pos="center"
                  style:horizontal-rel="paragraph"/>
            </style:style>',
        "tablealigncenter" => '
            <style:style style:name="tablealigncenter" style:family="paragraph" style:parent-style-name="Table_20_Contents">
                <style:paragraph-properties fo:text-align="center"/>
            </style:style>',
        "tablealignright"  => '
            <style:style style:name="tablealignright" style:family="paragraph" style:parent-style-name="Table_20_Contents">
                <style:paragraph-properties fo:text-align="end"/>
            </style:style>',
        "tablealignleft"   => '
            <style:style style:name="tablealignleft" style:family="paragraph" style:parent-style-name="Table_20_Contents">
                <style:paragraph-properties fo:text-align="left"/>
            </style:style>',
        "tableheader"      => '
            <style:style style:name="tableheader" style:family="table-cell">
                <style:table-cell-properties fo:padding="0.05cm" fo:border-left="0.002cm solid #000000" fo:border-right="0.002cm solid #000000" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000"/>
            </style:style>',
        "tablecell"        => '
            <style:style style:name="tablecell" style:family="table-cell">
                <style:table-cell-properties fo:padding="0.05cm" fo:border-left="0.002cm solid #000000" fo:border-right="0.002cm solid #000000" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000"/>
            </style:style>',
        "legendcenter"     => '
            <style:style style:name="legendcenter" style:family="paragraph" style:parent-style-name="Illustration">
                <style:paragraph-properties fo:text-align="center"/>
            </style:style>',
    );
    // Regular styles. May not be present if in template mode, in which case they will be added to styles.xml
    protected $styles = array(
        "Source_20_Text"       => '
            <style:style style:name="Source_20_Text" style:display-name="Source Text" style:family="text">
                <style:text-properties style:font-name="Bitstream Vera Sans Mono" style:font-name-asian="Bitstream Vera Sans Mono" style:font-name-complex="Bitstream Vera Sans Mono"/>
            </style:style>',
        "Preformatted_20_Text" => '
            <style:style style:name="Preformatted_20_Text" style:display-name="Preformatted Text" style:family="paragraph" style:parent-style-name="Standard" style:class="html">
                <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.2cm"/>
                <style:text-properties style:font-name="Bitstream Vera Sans Mono" style:font-name-asian="Bitstream Vera Sans Mono" style:font-name-complex="Bitstream Vera Sans Mono"/>
            </style:style>',
        "Source_20_Code"       => '
            <style:style style:name="Source_20_Code" style:display-name="Source Code" style:family="paragraph" style:parent-style-name="Preformatted_20_Text">
                <style:paragraph-properties fo:padding="0.05cm" style:shadow="none" fo:border="0.002cm solid #8cacbb" fo:background-color="#f7f9fa"/>
            </style:style>',
        "Source_20_File"       => '
            <style:style style:name="Source_20_File" style:display-name="Source File" style:family="paragraph" style:parent-style-name="Preformatted_20_Text">
                <style:paragraph-properties fo:padding="0.05cm" style:shadow="none" fo:border="0.002cm solid #8cacbb" fo:background-color="#f1f4f5"/>
            </style:style>',
        "Horizontal_20_Line"   => '
            <style:style style:name="Horizontal_20_Line" style:display-name="Horizontal Line" style:family="paragraph" style:parent-style-name="Standard" style:next-style-name="Text_20_body" style:class="html">
                <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.5cm" style:border-line-width-bottom="0.002cm 0.035cm 0.002cm" fo:padding="0cm" fo:border-left="none" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.04cm double #808080" text:number-lines="false" text:line-number="0" style:join-border="false"/>
                <style:text-properties fo:font-size="6pt" style:font-size-asian="6pt" style:font-size-complex="6pt"/>
            </style:style>',
        "Footnote"             => '
            <style:style style:name="Footnote" style:family="paragraph" style:parent-style-name="Standard" style:class="extra">
                <style:paragraph-properties fo:margin-left="0.5cm" fo:margin-right="0cm" fo:text-indent="-0.5cm" style:auto-text-indent="false" text:number-lines="false" text:line-number="0"/>
                <style:text-properties fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
            </style:style>',
        "Emphasis"             => '
            <style:style style:name="Emphasis" style:family="text">
                <style:text-properties fo:font-style="italic" style:font-style-asian="italic" style:font-style-complex="italic"/>
            </style:style>',
        "Strong_20_Emphasis"   => '
            <style:style style:name="Strong_20_Emphasis" style:display-name="Strong Emphasis" style:family="text">
                <style:text-properties fo:font-weight="bold" style:font-weight-asian="bold" style:font-weight-complex="bold"/>
            </style:style>',
    );
    // Font definitions. May not be present if in template mode, in which case they will be added to styles.xml
    protected $fonts = array(
        "StarSymbol"               => '<style:font-face style:name="StarSymbol" svg:font-family="StarSymbol"/>', // for bullets
        "Bitstream Vera Sans Mono" => '<style:font-face style:name="Bitstream Vera Sans Mono" svg:font-family="\'Bitstream Vera Sans Mono\'" style:font-family-generic="modern" style:font-pitch="fixed"/>', // for source code
    );

    /**
     * Initializes the document
     */
    protected function open() {
        global $ID;

        // prepare the zipper
        $this->ZIP = new ZipLib();

        // mime type should always be the very first entry
        $this->ZIP->add_File($this->getMimeType(), 'mimetype', 0);

        // initialize temp dir
        $this->temp_dir = io_mktmpdir();
        if(!$this->temp_dir) die('temp dir creation failed');

        // prepare meta data
        $this->meta = array(
            'meta:generator'        => 'DokuWiki ' . getversion(),
            'meta:initial-creator'  => 'Generated',
            'meta:creation-date'    => date('Y-m-d\\TH::i:s', null), //FIXME
            'dc:creator'            => 'Generated',
            'dc:date'               => date('Y-m-d\\TH::i:s', null),
            'dc:language'           => 'en-US',
            'meta:editing-cycles'   => '1',
            'meta:editing-duration' => 'PT0S',
        );

        // store the content type headers in metadata
        $output_filename = str_replace(':', '-', $ID) . '.' . $this->getFormat();
        $headers         = array(
            'Content-Type'        => $this->getMimeType(),
            'Content-Disposition' => 'attachment; filename="' . $output_filename . '";',
        );
        p_set_metadata($ID, array('format' => array('odt_odt' => $headers)));
    }

    /**
     * Finalizes the document
     */
    protected function close() {
        global $conf;

        // strip empty paragraphs
        $this->doc = preg_replace('#<text:p[^>]*>\s*</text:p>#', '', $this->doc);

        // Apply the template, zip up everything and store it in $doc for caching and output
        $tpl       = $this->getTemplateFile();
        $this->doc = $this->applyTemplate($tpl);
    }

    /**
     * Returns the mime type entry
     *
     * @return string
     */
    protected function getMimeType() {
        return 'application/vnd.oasis.opendocument.text';
    }

    /**
     * Load the given template file and apply the aggregated content to it
     *
     * @param string $tpl full path to the template file
     * @return string the binary ZIP file contents
     */
    function applyTemplate($tpl) {
        global $conf, $ID; // for the temp dir

        // Extract template
        $this->ZIP->Extract($tpl, $this->temp_dir);

        // Prepare content
        $autostyles    = $this->_odtAutoStyles();
        $missingstyles = $this->_odtStyles();
        $missingfonts  = $this->_odtFonts();
        $userfields    = $this->_odtUserFields();

        // Insert content
        $old_content = io_readFile($this->temp_dir . '/content.xml');
        if(strpos($old_content, 'DOKUWIKI-ODT-INSERT') !== false) { // Replace the mark
            $this->_odtReplaceInFile(
                '/<text:p[^>]*>DOKUWIKI-ODT-INSERT<\/text:p>/',
                $this->doc, $this->temp_dir . '/content.xml', true
            );
        } else { // Append to the template
            $this->_odtReplaceInFile('</office:text>', $this->doc . '</office:text>', $this->temp_dir . '/content.xml');
        }

        // Cut off unwanted content
        if(strpos($old_content, 'DOKUWIKI-ODT-CUT-START') !== false
            && strpos($old_content, 'DOKUWIKI-ODT-CUT-STOP') !== false
        ) {
            $this->_odtReplaceInFile(
                '/DOKUWIKI-ODT-CUT-START.*DOKUWIKI-ODT-CUT-STOP/',
                '', $this->temp_dir . '/content.xml', true
            );
        }

        // Insert userfields
        if(strpos($old_content, "text:user-field-decls") === false) { // no existing userfields
            $escapedUserFields = $this->preg_replacement_quote($userfields);
            $this->_odtReplaceInFile('/<office:text([^>]*)>/U', '<office:text\1>' . $escapedUserFields, $this->temp_dir . '/content.xml', true, false);
        } else {
            $this->_odtReplaceInFile('</text:user-field-decls>', substr($userfields, 23), $this->temp_dir . '/content.xml');
        }

        // Insert styles & fonts
        $this->_odtReplaceInFile('</office:automatic-styles>', substr($autostyles, 25), $this->temp_dir . '/content.xml');
        $this->_odtReplaceInFile('</office:automatic-styles>', substr($autostyles, 25), $this->temp_dir . '/styles.xml');
        $this->_odtReplaceInFile('</office:styles>', $missingstyles . '</office:styles>', $this->temp_dir . '/styles.xml');
        $this->_odtReplaceInFile('</office:font-face-decls>', $missingfonts . '</office:font-face-decls>', $this->temp_dir . '/styles.xml');

        // Add manifest data
        $this->_odtReplaceInFile('</manifest:manifest>', $this->_odtGetManifest() . '</manifest:manifest>', $this->temp_dir . '/META-INF/manifest.xml');

        // Build the Zip
        $this->ZIP->Compress(null, $this->temp_dir, null);
        io_rmdir($this->temp_dir, true);
        return $this->ZIP->get_file();
    }

    function _odtGetManifest() {
        $value = '';
        foreach($this->manifest as $path => $type) {
            $value .= '<manifest:file-entry manifest:media-type="' . $this->_xmlEntities($type) .
                '" manifest:full-path="' . $this->_xmlEntities($path) . '"/>';
        }
        return $value;
    }

    /**
     * Prepare settings.xml
     */
    function _odtSettings() {
        $value = '<' . '?xml version="1.0" encoding="UTF-8"?' . ">\n";
        $value .= '<office:document-settings xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0" office:version="1.0"><office:settings><config:config-item-set config:name="dummy-settings"><config:config-item config:name="MakeValidatorHappy" config:type="boolean">true</config:config-item></config:config-item-set></office:settings></office:document-settings>';
        $this->ZIP->add_File($value, 'settings.xml');
    }

    public function setTemplate($tpl) {
        $this->template = $tpl;
    }

    /**
     * Return the file name of the template to use
     *
     * Handles URL parameters and previosly set templates
     *
     * @return string
     */
    protected function getTemplateFile() {
        global $INPUT;

        // check if a template was given in the URL or a default was configured
        $this->template = $INPUT->str('odt-template', $this->template); // backwards compatibility
        $this->template = $INPUT->str('tpl', $this->template); // preferred name
        if(!$this->template) $this->template = $this->getConf('tpl_default');

        $tplfile = '';
        if($this->template) {
            // locate the template
            $tplns   = $this->getConf('tpl_ns');
            $tplfile = mediaFN($tplns . ':' . $this->template);

            // got it?
            if(!file_exists($tplfile)) {
                $this->doc = '<text:p text:style-name="Text_20_body"><text:span text:style-name="Strong_20_Emphasis">'
                    . $this->_xmlEntities(sprintf($this->getLang('tpl_not_found'), $this->template, $tplns))
                    . '</text:span></text:p>' . $this->doc;
            }
        }

        if(!$tplfile) {
            $tplfile = __DIR__ . '/../default.' . $this->getFormat(); // fall back to default
        }

        return $tplfile;
    }

    /**
     * Escape a text used in preg_replace to be safe against back references
     *
     * @param $str
     * @return mixed
     */
    function preg_replacement_quote($str) {
        return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
    }

    function _odtReplaceInFile($from, $to, $file, $regexp = false, $escapeTo = true) {
        $value = io_readFile($file);
        if($regexp) {
            if($escapeTo) {
                $to = $this->preg_replacement_quote($to);
            }
            $value = preg_replace($from, $to, $value);
        } else {
            $value = str_replace($from, $to, $value);
        }
        $file_f = fopen($file, 'w');
        fwrite($file_f, $value);
        fclose($file_f);
    }

    function _odtAutoStyles() {
        $value = '<office:automatic-styles>';
        foreach($this->autostyles as $stylename => $stylexml) {
            $value .= $stylexml;
        }
        $value .= '</office:automatic-styles>';
        return $value;
    }

    function _odtUserFields() {
        $value = '<text:user-field-decls>';
        foreach($this->fields as $fname => $fvalue) {
            $value .= '<text:user-field-decl office:value-type="string" text:name="' . $fname . '" office:string-value="' . $fvalue . '"/>';
        }
        $value .= '</text:user-field-decls>';
        return $value;
    }

    /* Add missing styles in the template */
    function _odtStyles() {
        $value           = '';
        $existing_styles = io_readFile($this->temp_dir . '/styles.xml');
        foreach($this->styles as $stylename => $stylexml) {
            if(strpos($existing_styles, 'style:name="' . $stylename . '"') === false) {
                $value .= $stylexml;
            }
        }
        // Loop on bullet/numerotation styles
        if(strpos($existing_styles, 'style:name="List_20_1"') === false) {
            $value .= '<text:list-style style:name="List_20_1" style:display-name="List 1">';
            for($i = 1; $i <= 10; $i++) {
                $value .= '<text:list-level-style-bullet text:level="' . $i . '" text:style-name="Numbering_20_Symbols" text:bullet-char="•">
                               <style:list-level-properties text:space-before="' . (0.4 * ($i - 1)) . 'cm" text:min-label-width="0.4cm"/>
                               <style:text-properties style:font-name="StarSymbol"/>
                           </text:list-level-style-bullet>';
            }
            $value .= '</text:list-style>';
        }
        if(strpos($existing_styles, 'style:name="Numbering_20_1"') === false) {
            $value .= '<text:list-style style:name="Numbering_20_1" style:display-name="Numbering 1">';
            for($i = 1; $i <= 10; $i++) {
                $value .= '<text:list-level-style-number text:level="' . $i . '" text:style-name="Numbering_20_Symbols" style:num-suffix="." style:num-format="1">
                               <style:list-level-properties text:space-before="' . (0.5 * ($i - 1)) . 'cm" text:min-label-width="0.5cm"/>
                           </text:list-level-style-number>';
            }
            $value .= '</text:list-style>';
        }
        return $value;
    }

    /* Add missing fonts in the template */
    function _odtFonts() {
        $value           = '';
        $existing_styles = io_readFile($this->temp_dir . '/styles.xml');
        foreach($this->fonts as $name => $xml) {
            if(strpos($existing_styles, 'style:name="' . $name . '"') === false) {
                $value .= $xml;
            }
        }
        return $value;
    }

    /**
     * static call back to replace spaces
     */
    function _preserveSpace($matches) {
        $spaces = $matches[1];
        $len    = strlen($spaces);
        return '<text:s text:c="' . $len . '"/>';
    }

    function _preformatted($text, $style = "Preformatted_20_Text", $notescaped = true) {
        if($notescaped) {
            $text = $this->_xmlEntities($text);
        }
        if(strpos($text, "\n") !== false and strpos($text, "\n") == 0) {
            // text starts with a newline, remove it
            $text = substr($text, 1);
        }
        $text = str_replace("\n", '<text:line-break/>', $text);
        $text = str_replace("\t", '<text:tab/>', $text);
        $text = preg_replace_callback('/(  +)/', array('renderer_plugin_odt', '_preserveSpace'), $text);

        if($this->in_list_item) { // if we're in a list item, we must close the <text:p> tag
            $this->doc .= '</text:p>';
            $this->doc .= '<text:p text:style-name="' . $style . '">';
            $this->doc .= $text;
            $this->doc .= '</text:p>';
            $this->doc .= '<text:p>';
        } else {
            $this->doc .= '<text:p text:style-name="' . $style . '">';
            $this->doc .= $text;
            $this->doc .= '</text:p>';
        }
    }

    function _highlight($type, $text, $language = null) {
        $style_name = "Source_20_Code";
        if($type == "file") $style_name = "Source_20_File";

        if(is_null($language)) {
            $this->_preformatted($text, $style_name);
            return;
        }

        // from inc/parserutils.php:p_xhtml_cached_geshi()
        require_once(DOKU_INC . 'inc/geshi.php');
        $geshi = new GeSHi($text, $language, DOKU_INC . 'inc/geshi');
        $geshi->set_encoding('utf-8');
        // $geshi->enable_classes(); DO NOT WANT !
        $geshi->set_header_type(GESHI_HEADER_PRE);
        $geshi->enable_keyword_links(false);

        // remove GeSHi's wrapper element (we'll replace it with our own later)
        // we need to use a GeSHi wrapper to avoid <BR> throughout the highlighted text
        $highlighted_code = trim(preg_replace('!^<pre[^>]*>|</pre>$!', '', $geshi->parse_code()), "\n\r");
        // remove useless leading and trailing whitespace-newlines
        $highlighted_code = preg_replace('/^&nbsp;\n/', '', $highlighted_code);
        $highlighted_code = preg_replace('/\n&nbsp;$/', '', $highlighted_code);
        // replace styles
        $highlighted_code = str_replace("</span>", "</text:span>", $highlighted_code);
        $highlighted_code = preg_replace_callback('/<span style="([^"]+)">/', array('renderer_plugin_odt', '_convert_css_styles'), $highlighted_code);
        // cleanup leftover span tags
        $highlighted_code = preg_replace('/<span[^>]*>/', "<text:span>", $highlighted_code);
        $highlighted_code = str_replace("&nbsp;", "&#xA0;", $highlighted_code);
        $this->_preformatted($highlighted_code, $style_name, false);
    }

    function _convert_css_styles($matches) {
        $all_css_styles = $matches[1];
        // parse the CSS attribute
        $css_styles = array();
        foreach(explode(";", $all_css_styles) as $css_style) {
            $css_style_array = explode(":", $css_style);
            if(!trim($css_style_array[0]) or !trim($css_style_array[1])) {
                continue;
            }
            $css_styles[trim($css_style_array[0])] = trim($css_style_array[1]);
        }
        // create the ODT xml style
        $style_name = "highlight." . $this->count_cssstyles;
        $this->count_cssstyles += 1;
        $style_content = '
            <style:style style:name="' . $style_name . '" style:family="text">
                <style:text-properties ';
        foreach($css_styles as $style_key => $style_value) {
            // Hats off to those who thought out the OpenDocument spec: styling syntax is similar to CSS !
            $style_content .= 'fo:' . $style_key . '="' . $style_value . '" ';
        }
        $style_content .= '/>
            </style:style>';
        // add the style to the library
        $this->autostyles[$style_name] = $style_content;
        // now make use of the new style
        return '<text:span text:style-name="' . $style_name . '">';
    }

    /**
     * Add a hyperlink, handling Images correctly
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function _doLink($url, $name) {
        $url = $this->_xmlEntities($url);
        if(is_array($name)) {
            // Images
            if($url) $this->doc .= '<draw:a xlink:type="simple" xlink:href="' . $url . '">';

            if($name['type'] == 'internalmedia') {
                $this->internalmedia(
                    $name['src'],
                    $name['title'],
                    $name['align'],
                    $name['width'],
                    $name['height'],
                    $name['cache'],
                    $name['linking']
                );
            }

            if($url) $this->doc .= '</draw:a>';
        } else {
            // Text
            if($url) $this->doc .= '<text:a xlink:type="simple" xlink:href="' . $url . '">';
            $this->doc .= $name; // we get the name already XML encoded
            if($url) $this->doc .= '</text:a>';
        }
    }

    /**
     * Construct a title and handle images in titles
     *
     * @author Harry Fuecks <hfuecks@gmail.com>
     */
    function _getLinkTitle($title, $default, & $isImage, $id = null) {
        global $conf;

        $isImage = false;
        if(is_null($title)) {
            if($conf['useheading'] && $id) {
                $heading = p_get_first_heading($id);
                if($heading) {
                    return $this->_xmlEntities($heading);
                }
            }
            return $this->_xmlEntities($default);
        } else if(is_array($title)) {
            $isImage = true;
            return $title;
        }

        return $this->_xmlEntities($title);
    }

    /**
     * Creates a linkid from a headline
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param string  $title   The headline title
     * @param boolean $create  Create a new unique ID?
     * @return string
     */
    function _headerToLink($title, $create = false) {
        $title = str_replace(':', '', cleanID($title));
        $title = ltrim($title, '0123456789._-');
        if(empty($title)) $title = 'section';

        if($create) {
            // make sure tiles are unique
            $num = '';
            while(in_array($title . $num, $this->headers)) {
                ($num) ? $num++ : $num = 1;
            }
            $title           = $title . $num;
            $this->headers[] = $title;
        }

        return $title;
    }

    function _xmlEntities($value) {
        return str_replace(array('&', '"', "'", '<', '>'), array('&#38;', '&#34;', '&#39;', '&#60;', '&#62;'), $value);
    }

    function _odtAddImage($src, $width = null, $height = null, $align = null, $title = null, $style = null) {
        if(file_exists($src)) {
            list($ext, $mime) = mimetype($src);
            $name = 'Pictures/' . md5($src) . '.' . $ext;
            if(!$this->manifest[$name]) {
                $this->manifest[$name] = $mime;
                $this->ZIP->add_File(io_readfile($src, false), $name, 0);
            }
        } else {
            $name = $src;
        }
        // make sure width and height are available
        if(!$width || !$height) {
            list($width, $height) = $this->_odtGetImageSize($src, $width, $height);
        }

        if($align) {
            $anchor = 'paragraph';
        } else {
            $anchor = 'as-char';
        }

        if(!$style or !array_key_exists($style, $this->autostyles)) {
            $style = 'media' . $align;
        }

        if($title) {
            $this->doc .= '<draw:frame draw:style-name="' . $style . '" draw:name="' . $this->_xmlEntities($title) . ' Legend"
                            text:anchor-type="' . $anchor . '" draw:z-index="0" svg:width="' . $width . '">';
            $this->doc .= '<draw:text-box>';
            $this->doc .= '<text:p text:style-name="legendcenter">';
        }
        $this->doc .= '<draw:frame draw:style-name="' . $style . '" draw:name="' . $this->_xmlEntities($title) . '"
                        text:anchor-type="' . $anchor . '" draw:z-index="0"
                        svg:width="' . $width . '" svg:height="' . $height . '" >';
        $this->doc .= '<draw:image xlink:href="' . $this->_xmlEntities($name) . '"
                        xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>';
        $this->doc .= '</draw:frame>';
        if($title) {
            $this->doc .= $this->_xmlEntities($title) . '</text:p></draw:text-box></draw:frame>';
        }
    }

    function _odtGetImageSize($src, $width = null, $height = null) {
        if(file_exists($src)) {
            $info = getimagesize($src);
            if(!$width) {
                $width  = $info[0];
                $height = $info[1];
            } else {
                $height = round(($width * $info[1]) / $info[0]);
            }
        }

        // convert from pixel to centimeters
        if($width) $width = (($width / 96.0) * 2.54);
        if($height) $height = (($height / 96.0) * 2.54);

        if($width && $height) {
            // Don't be wider than the page
            if($width >= 17) { // FIXME : this assumes A4 page format with 2cm margins
                $width  = $width . 'cm"  style:rel-width="100%';
                $height = $height . 'cm"  style:rel-height="scale';
            } else {
                $width  = $width . 'cm';
                $height = $height . 'cm';
            }
        } else {
            // external image and unable to download, fallback
            if($width) {
                $width = $width . "cm";
            } else {
                $width = '" svg:rel-width="100%';
            }
            if($height) {
                $height = $height . "cm";
            } else {
                $height = '" svg:rel-height="100%';
            }
        }
        return array($width, $height);
    }

    #region implemented render methods (overrides)

    /**
     * Returns the format produced by this renderer.
     */
    function getFormat() {
        return "odt";
    }

    /**
     * Do not make multiple instances of this class
     */
    function isSingleton() {
        return true;
    }

    /**
     * Initialize the rendering
     */
    function document_start() {
        $this->open();
    }

    /**
     * Closes the document
     */
    function document_end() {
        $this->close();
    }

    // not supported - use OpenOffice builtin tools instead!
    function render_TOC() {
        return '';
    }

    function toc_additem($id, $text, $level) {
    }

    function cdata($text) {
        $this->doc .= $this->_xmlEntities($text);
    }

    function p_open($style = 'Text_20_body') {
        if(!$this->in_paragraph) { // opening a paragraph inside another paragraph is illegal
            $this->in_paragraph = true;
            $this->doc .= '<text:p text:style-name="' . $style . '">';
        }
    }

    function p_close() {
        if($this->in_paragraph) {
            $this->in_paragraph = false;
            $this->doc .= '</text:p>';
        }
    }

    function header($text, $level, $pos) {
        $hid = $this->_headerToLink($text, true);
        $this->doc .= '<text:h text:style-name="Heading_20_' . $level . '" text:outline-level="' . $level . '">';
        $this->doc .= '<text:bookmark-start text:name="' . $hid . '"/>';
        $this->doc .= $this->_xmlEntities($text);
        $this->doc .= '<text:bookmark-end text:name="' . $hid . '"/>';
        $this->doc .= '</text:h>';
    }

    function hr() {
        $this->doc .= '<text:p text:style-name="Horizontal_20_Line"/>';
    }

    function linebreak() {
        $this->doc .= '<text:line-break/>';
    }

    function strong_open() {
        $this->doc .= '<text:span text:style-name="Strong_20_Emphasis">';
    }

    function strong_close() {
        $this->doc .= '</text:span>';
    }

    function emphasis_open() {
        $this->doc .= '<text:span text:style-name="Emphasis">';
    }

    function emphasis_close() {
        $this->doc .= '</text:span>';
    }

    function underline_open() {
        $this->doc .= '<text:span text:style-name="underline">';
    }

    function underline_close() {
        $this->doc .= '</text:span>';
    }

    function monospace_open() {
        $this->doc .= '<text:span text:style-name="Source_20_Text">';
    }

    function monospace_close() {
        $this->doc .= '</text:span>';
    }

    function subscript_open() {
        $this->doc .= '<text:span text:style-name="sub">';
    }

    function subscript_close() {
        $this->doc .= '</text:span>';
    }

    function superscript_open() {
        $this->doc .= '<text:span text:style-name="sup">';
    }

    function superscript_close() {
        $this->doc .= '</text:span>';
    }

    function deleted_open() {
        $this->doc .= '<text:span text:style-name="del">';
    }

    function deleted_close() {
        $this->doc .= '</text:span>';
    }

    /*
     * Tables
     */
    function table_open($maxcols = null, $numrows = null) {
        $this->doc .= '<table:table>';
        for($i = 0; $i < $maxcols; $i++) {
            $this->doc .= '<table:table-column />';
        }
    }

    function table_close() {
        $this->doc .= '</table:table>';
    }

    function tablerow_open() {
        $this->doc .= '<table:table-row>';
    }

    function tablerow_close() {
        $this->doc .= '</table:table-row>';
    }

    function tableheader_open($colspan = 1, $align = "left", $rowspan = 1) {
        $this->doc .= '<table:table-cell office:value-type="string" table:style-name="tableheader" ';
        //$this->doc .= ' table:style-name="tablealign'.$align.'"';
        if($colspan > 1) {
            $this->doc .= ' table:number-columns-spanned="' . $colspan . '"';
        }
        if($rowspan > 1) {
            $this->doc .= ' table:number-rows-spanned="' . $rowspan . '"';
        }
        $this->doc .= '>';
        $this->p_open('Table_20_Heading');
    }

    function tableheader_close() {
        $this->p_close();
        $this->doc .= '</table:table-cell>';
    }

    function tablecell_open($colspan = 1, $align = "left", $rowspan = 1) {
        $this->doc .= '<table:table-cell office:value-type="string" table:style-name="tablecell" ';
        if($colspan > 1) {
            $this->doc .= ' table:number-columns-spanned="' . $colspan . '"';
        }
        if($rowspan > 1) {
            $this->doc .= ' table:number-rows-spanned="' . $rowspan . '"';
        }
        $this->doc .= '>';
        if(!$align) $align = "left";
        $style = "tablealign" . $align;
        $this->p_open($style);
    }

    function tablecell_close() {
        $this->p_close();
        $this->doc .= '</table:table-cell>';
    }

    /**
     * Start a footnote
     *
     * All following content will go to the footnote instead of
     * the document. To achieve this the previous rendered content
     * is moved to $store and $doc is cleared
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function footnote_open() {

        // move current content to store and record footnote
        $this->store = $this->doc;
        $this->doc   = '';
    }

    /**
     * End a footnote
     *
     * All rendered content is wrapped into footnote syntax, the old document is restored from
     * $store and the footnote is appended.
     *
     * Please note: since OpenDocument places footnotes at the end of a page instead of doing
     * end of document notes, footnotes are not de-duplicated here.
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function footnote_close() {
        // recover footnote into the stack and restore old content
        $footnote    = $this->doc;
        $this->doc   = $this->store;
        $this->store = '';

        $this->count_footnotes++;
        $this->doc .= '<text:note text:id="ftn' . $this->count_footnotes . '" text:note-class="footnote">';
        $this->doc .= '<text:note-citation>' . $this->count_footnotes . '</text:note-citation>';
        $this->doc .= '<text:note-body>';
        $this->doc .= '<text:p text:style-name="Footnote">';
        $this->doc .= $footnote;
        $this->doc .= '</text:p>';
        $this->doc .= '</text:note-body>';
        $this->doc .= '</text:note>';
    }

    function listu_open() {
        $this->p_close();
        $this->doc .= '<text:list text:style-name="List_20_1">';
    }

    function listu_close() {
        $this->doc .= '</text:list>';
    }

    function listo_open() {
        $this->p_close();
        $this->doc .= '<text:list text:style-name="Numbering_20_1">';
    }

    function listo_close() {
        $this->doc .= '</text:list>';
    }

    function listitem_open($level) {
        $this->in_list_item = true;
        $this->doc .= '<text:list-item>';
    }

    function listitem_close() {
        $this->in_list_item = false;
        $this->doc .= '</text:list-item>';
    }

    function listcontent_open() {
        $this->doc .= '<text:p text:style-name="Text_20_body">';
    }

    function listcontent_close() {
        $this->doc .= '</text:p>';
    }

    function unformatted($text) {
        $this->doc .= $this->_xmlEntities($text);
    }

    function acronym($acronym) {
        $this->doc .= $this->_xmlEntities($acronym);
    }

    function smiley($smiley) {
        if(array_key_exists($smiley, $this->smileys)) {
            $src = DOKU_INC . "lib/images/smileys/" . $this->smileys[$smiley];
            $this->_odtAddImage($src);
        } else {
            $this->doc .= $this->_xmlEntities($smiley);
        }
    }

    function entity($entity) {
        # UTF-8 entity decoding is broken in PHP <5
        if(version_compare(phpversion(), "5.0.0") and array_key_exists($entity, $this->entities)) {
            # decoding may fail for missing Multibyte-Support in entity_decode
            $dec = @html_entity_decode($this->entities[$entity], ENT_NOQUOTES, 'UTF-8');
            if($dec) {
                $this->doc .= $this->_xmlEntities($dec);
            } else {
                $this->doc .= $this->_xmlEntities($entity);
            }
        } else {
            $this->doc .= $this->_xmlEntities($entity);
        }
    }

    function multiplyentity($x, $y) {
        $this->doc .= $x . '×' . $y;
    }

    function singlequoteopening() {
        global $lang;
        $this->doc .= $lang['singlequoteopening'];
    }

    function singlequoteclosing() {
        global $lang;
        $this->doc .= $lang['singlequoteclosing'];
    }

    function apostrophe() {
        global $lang;
        $this->doc .= $lang['apostrophe'];
    }

    function doublequoteopening() {
        global $lang;
        $this->doc .= $lang['doublequoteopening'];
    }

    function doublequoteclosing() {
        global $lang;
        $this->doc .= $lang['doublequoteclosing'];
    }

    function php($text, $wrapper = 'dummy') {
        $this->monospace_open();
        $this->doc .= $this->_xmlEntities($text);
        $this->monospace_close();
    }

    function phpblock($text) {
        $this->file($text);
    }

    function html($text, $wrapper = 'dummy') {
        $this->monospace_open();
        $this->doc .= $this->_xmlEntities($text);
        $this->monospace_close();
    }

    function htmlblock($text) {
        $this->file($text);
    }

    function preformatted($text) {
        $this->_preformatted($text);
    }

    function file($text, $language = null, $filename = null) {
        $this->_highlight('file', $text, $language);
    }

    function quote_open() {
        if(!$this->in_paragraph) { // only start a new par if we're not already in one
            $this->p_open();
        }
        $this->doc .= "&gt;";
    }

    function quote_close() {
        if($this->in_paragraph) { // only close the paragraph if we're actually in one
            $this->p_close();
        }
    }

    function code($text, $language = null, $filename = null) {
        $this->_highlight('code', $text, $language);
    }

    function internalmedia($src, $title = null, $align = null, $width = null,
                           $height = null, $cache = null, $linking = null) {
        global $ID;
        resolve_mediaid(getNS($ID), $src, $exists);
        list(, $mime) = mimetype($src);

        if(substr($mime, 0, 5) == 'image') {
            $file = mediaFN($src);
            $this->_odtAddImage($file, $width, $height, $align, $title);
        } else {
            // FIXME build absolute medialink and call externallink()
            $this->code('FIXME internalmedia: ' . $src);
        }
    }

    function externalmedia($src, $title = null, $align = null, $width = null,
                           $height = null, $cache = null, $linking = null) {
        global $conf;
        list($ext, $mime) = mimetype($src);

        if(substr($mime, 0, 5) == 'image') {
            $tmp_dir    = $conf['tmpdir'] . "/odt";
            $tmp_name   = $tmp_dir . "/" . md5($src) . '.' . $ext;
            $final_name = 'Pictures/' . md5($tmp_name) . '.' . $ext;
            if(!isset($this->manifest[$final_name])) {
                $client = new DokuHTTPClient;
                $img    = $client->get($src);
                if($img === false) {
                    $tmp_name = $src; // fallback to a simple link
                } else {
                    if(!is_dir($tmp_dir)) io_mkdir_p($tmp_dir);
                    $tmp_img = fopen($tmp_name, "w") or die("Can't create temp file $tmp_img");
                    fwrite($tmp_img, $img);
                    fclose($tmp_img);
                }
            }
            $this->_odtAddImage($tmp_name, $width, $height, $align, $title);
            if(file_exists($tmp_name)) unlink($tmp_name);
        } else {
            $this->externallink($src, $title);
        }
    }

    function camelcaselink($link) {
        $this->internallink($link, $link);
    }

    function reference($id, $name = null) {
        $this->doc .= '<text:a xlink:type="simple" xlink:href="#' . $id . '"';
        if($name) {
            $this->doc .= '>' . $this->_xmlEntities($name) . '</text:a>';
        } else {
            $this->doc .= '/>';
        }
    }

    /**
     * Render an internal Wiki Link
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function internallink($id, $name = null) {
        global $ID;
        // default name is based on $id as given
        $default = $this->_simpleTitle($id);
        // now first resolve and clean up the $id
        resolve_pageid(getNS($ID), $id, $exists);
        $name = $this->_getLinkTitle($name, $default, $isImage, $id);

        // build the absolute URL (keeping a hash if any)
        list($id, $hash) = explode('#', $id, 2);
        $url = wl($id, '', true);
        if($hash) $url .= '#' . $hash;

        if($ID == $id) {
            $this->reference($hash, $name);
        } else {
            $this->_doLink($url, $name);
        }
    }

    /**
     * Add external link
     */
    function externallink($url, $name = null) {
        $name = $this->_getLinkTitle($name, $url, $isImage);
        $this->_doLink($url, $name);
    }

    /**
     * Just print local links
     *
     * @fixme add image handling
     */
    function locallink($hash, $name = null) {
        $name = $this->_getLinkTitle($name, $hash, $isImage);
        $this->doc .= $name;
    }

    /**
     * InterWiki links
     */
    function interwikilink($match, $name = null, $wikiName, $wikiUri) {
        $name = $this->_getLinkTitle($name, $wikiUri, $isImage);
        $url  = $this->_resolveInterWiki($wikiName, $wikiUri);
        $this->_doLink($url, $name);
    }

    /**
     * Just print WindowsShare links
     *
     * @fixme add image handling
     */
    function windowssharelink($url, $name = null) {
        $name = $this->_getLinkTitle($name, $url, $isImage);
        $this->doc .= $name;
    }

    /**
     * Just print email links
     *
     * @fixme add image handling
     */
    function emaillink($address, $name = null) {
        $name = $this->_getLinkTitle($name, $address, $isImage);
        $this->_doLink("mailto:" . $address, $name);
    }

    function rss($url, $params) {
        global $lang;
        global $conf;

        require_once(DOKU_INC . 'inc/FeedParser.php');
        $feed = new FeedParser();
        $feed->feed_url($url);

        //disable warning while fetching
        if(!defined('DOKU_E_LEVEL')) {
            $elvl = error_reporting(E_ERROR);
        }
        $rc = $feed->init();
        if(!defined('DOKU_E_LEVEL')) {
            error_reporting($elvl);
        }

        //decide on start and end
        if($params['reverse']) {
            $mod   = -1;
            $start = $feed->get_item_quantity() - 1;
            $end   = $start - ($params['max']);
            $end   = ($end < -1) ? -1 : $end;
        } else {
            $mod   = 1;
            $start = 0;
            $end   = $feed->get_item_quantity();
            $end   = ($end > $params['max']) ? $params['max'] : $end;;
        }

        $this->listu_open();
        if($rc) {
            for($x = $start; $x != $end; $x += $mod) {
                $item = $feed->get_item($x);
                $this->listitem_open(0);
                $this->listcontent_open();
                $this->externallink(
                    $item->get_permalink(),
                    $item->get_title()
                );
                if($params['author']) {
                    $author = $item->get_author(0);
                    if($author) {
                        $name = $author->get_name();
                        if(!$name) $name = $author->get_email();
                        if($name) $this->cdata(' ' . $lang['by'] . ' ' . $name);
                    }
                }
                if($params['date']) {
                    $this->cdata(' (' . $item->get_date($conf['dformat']) . ')');
                }
                if($params['details']) {
                    $this->cdata(strip_tags($item->get_description()));
                }
                $this->listcontent_close();
                $this->listitem_close();
            }
        } else {
            $this->listitem_open(0);
            $this->listcontent_open();
            $this->emphasis_open();
            $this->cdata($lang['rssfailed']);
            $this->emphasis_close();
            $this->externallink($url);
            $this->listcontent_close();
            $this->listitem_close();
        }
        $this->listu_close();
    }

    #endregion
}

// vim: set et ts=4 sw=4 fileencoding=utf-8 :