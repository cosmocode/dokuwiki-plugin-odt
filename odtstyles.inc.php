<?php
/**
 * ODT Plugin: Exports to ODT
 *
 * This file contains the default styles library. Override them by using a
 * template as described on the wiki page.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Aurelien Bompard <aurelien@bompard.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


/*
 * Automatic styles. Will always be added to content.xml and styles.xml
 */
$odt_styles_autostyles = array(
    "pm1"=>'
        <style:page-layout style:name="pm1">
            <style:page-layout-properties fo:page-width="21cm" fo:page-height="29.7cm" style:num-format="1" style:print-orientation="portrait" fo:margin-top="2cm" fo:margin-bottom="2cm" fo:margin-left="2cm" fo:margin-right="2cm" style:writing-mode="lr-tb" style:footnote-max-height="0cm">
                <style:footnote-sep style:width="0.018cm" style:distance-before-sep="0.1cm" style:distance-after-sep="0.1cm" style:adjustment="left" style:rel-width="25%" style:color="#000000"/>
            </style:page-layout-properties>
            <style:header-style/>
            <style:footer-style/>
        </style:page-layout>',
    "sub"=>'
        <style:style style:name="sub" style:family="text">
            <style:text-properties style:text-position="-33% 80%"/>
        </style:style>',
    "sup"=>'
        <style:style style:name="sup" style:family="text">
            <style:text-properties style:text-position="33% 80%"/>
        </style:style>',
    "del"=>'
        <style:style style:name="del" style:family="text">
            <style:text-properties style:text-line-through-style="solid"/>
        </style:style>',
    "underline"=>'
        <style:style style:name="underline" style:family="text">
          <style:text-properties style:text-underline-style="solid"
             style:text-underline-width="auto" style:text-underline-color="font-color"/>
        </style:style>',
    "media"=>'
        <style:style style:name="media" style:family="graphic" style:parent-style-name="Graphics">
            <style:graphic-properties style:run-through="foreground" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit"
               style:wrap-contour="false" style:vertical-pos="top" style:vertical-rel="baseline" style:horizontal-pos="left"
               style:horizontal-rel="paragraph"/>
        </style:style>',
    "medialeft"=>'
        <style:style style:name="medialeft" style:family="graphic" style:parent-style-name="Graphics">
          <style:graphic-properties style:run-through="foreground" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit"
             style:wrap-contour="false" style:horizontal-pos="left" style:horizontal-rel="paragraph"/>
        </style:style>',
    "mediaright"=>'
        <style:style style:name="mediaright" style:family="graphic" style:parent-style-name="Graphics">
          <style:graphic-properties style:run-through="foreground" style:wrap="parallel" style:number-wrapped-paragraphs="no-limit"
             style:wrap-contour="false" style:horizontal-pos="right" style:horizontal-rel="paragraph"/>
        </style:style>',
    "mediacenter"=>'
        <style:style style:name="mediacenter" style:family="graphic" style:parent-style-name="Graphics">
           <style:graphic-properties style:run-through="foreground" style:wrap="none" style:horizontal-pos="center"
              style:horizontal-rel="paragraph"/>
        </style:style>',
    "tablealigncenter"=>'
        <style:style style:name="tablealigncenter" style:family="paragraph" style:parent-style-name="Table_20_Contents">
            <style:paragraph-properties fo:text-align="center"/>
        </style:style>',
    "tablealignright"=>'
        <style:style style:name="tablealignright" style:family="paragraph" style:parent-style-name="Table_20_Contents">
            <style:paragraph-properties fo:text-align="end"/>
        </style:style>',
    "tablealignleft"=>'
        <style:style style:name="tablealignleft" style:family="paragraph" style:parent-style-name="Table_20_Contents">
            <style:paragraph-properties fo:text-align="left"/>
        </style:style>',
    "tableheader"=>'
        <style:style style:name="tableheader" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm" fo:border-left="0.002cm solid #000000" fo:border-right="0.002cm solid #000000" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000"/>
        </style:style>',
    "tablecell"=>'
        <style:style style:name="tablecell" style:family="table-cell">
            <style:table-cell-properties fo:padding="0.05cm" fo:border-left="0.002cm solid #000000" fo:border-right="0.002cm solid #000000" fo:border-top="0.002cm solid #000000" fo:border-bottom="0.002cm solid #000000"/>
        </style:style>',
    "legendcenter"=>'
        <style:style style:name="legendcenter" style:family="paragraph" style:parent-style-name="Illustration">
            <style:paragraph-properties fo:text-align="center"/>
        </style:style>',
);

/*
 * Regular styles. May not be present if in template mode, in which case they will be added to styles.xml
 */
$odt_styles_styles = array(
    "Source_20_Text"=>'
        <style:style style:name="Source_20_Text" style:display-name="Source Text" style:family="text">
            <style:text-properties style:font-name="Bitstream Vera Sans Mono" style:font-name-asian="Bitstream Vera Sans Mono" style:font-name-complex="Bitstream Vera Sans Mono"/>
        </style:style>',
    "Preformatted_20_Text"=>'
        <style:style style:name="Preformatted_20_Text" style:display-name="Preformatted Text" style:family="paragraph" style:parent-style-name="Standard" style:class="html">
            <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0cm"/>
            <style:text-properties style:font-name="Bitstream Vera Sans Mono" style:font-name-asian="Bitstream Vera Sans Mono" style:font-name-complex="Bitstream Vera Sans Mono"/>
        </style:style>',
    "Horizontal_20_Line"=>'
        <style:style style:name="Horizontal_20_Line" style:display-name="Horizontal Line" style:family="paragraph" style:parent-style-name="Standard" style:next-style-name="Text_20_body" style:class="html">
            <style:paragraph-properties fo:margin-top="0cm" fo:margin-bottom="0.5cm" style:border-line-width-bottom="0.002cm 0.035cm 0.002cm" fo:padding="0cm" fo:border-left="none" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.04cm double #808080" text:number-lines="false" text:line-number="0" style:join-border="false"/>
            <style:text-properties fo:font-size="6pt" style:font-size-asian="6pt" style:font-size-complex="6pt"/>
        </style:style>',
    "Footnote"=>'
        <style:style style:name="Footnote" style:family="paragraph" style:parent-style-name="Standard" style:class="extra">
            <style:paragraph-properties fo:margin-left="0.5cm" fo:margin-right="0cm" fo:text-indent="-0.5cm" style:auto-text-indent="false" text:number-lines="false" text:line-number="0"/>
            <style:text-properties fo:font-size="10pt" style:font-size-asian="10pt" style:font-size-complex="10pt"/>
        </style:style>',
    "Emphasis"=>'
        <style:style style:name="Emphasis" style:family="text">
            <style:text-properties fo:font-style="italic" style:font-style-asian="italic" style:font-style-complex="italic"/>
        </style:style>',
    "Strong_20_Emphasis"=>'
        <style:style style:name="Strong_20_Emphasis" style:display-name="Strong Emphasis" style:family="text">
            <style:text-properties fo:font-weight="bold" style:font-weight-asian="bold" style:font-weight-complex="bold"/>
        </style:style>',
);


/*
 * Font definitions. May not be present if in template mode, in which case they will be added to styles.xml
 */
$odt_styles_fonts = array(
    "StarSymbol"=>'<style:font-face style:name="StarSymbol" svg:font-family="StarSymbol"/>', // for bullets
    "Bitstream Vera Sans Mono"=>'<style:font-face style:name="Bitstream Vera Sans Mono" svg:font-family="\'Bitstream Vera Sans Mono\'" style:font-family-generic="modern" style:font-pitch="fixed"/>', // for source code
);


//Setup VIM: ex: et ts=4 enc=utf-8 :
