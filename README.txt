====== Dokuwiki ODT plugin ======

This plugin lets you export wiki pages to ODT, the OpenDocument Text format as
used by many applications, included OpenOffice.org

Further documentation on the OpenDocument format is available here :
http://en.wikipedia.org/wiki/OpenDocument


===== Official documentation =====

For installation and usage documentation, please refer to 
http://wiki.splitbrain.org/plugin:odt

This version of the plugin supports Dokuwiki's latest version : 20070626 (you
do not need the development version)


==== Templates support ====
You may now use templates to export your document. A template is a regular
ODT file, as produced by OpenOffice (for example, not tested with other ODT-
supporting applications).

In your wiki page, add the following code:
<code><nowiki>
  {{odt>template:your template file name.odt}}
</nowiki></code>
and upload your template to the wiki using the media manager. By defaut, you
must put it in a ":odt" namespace (Meaning and "odt" directory right below the
root in your wiki). The folder name can be configured using the admin page.

The exported page will be added after the content of your template. If you
include the string ''DOKUWIKI-INSERT-ODT'' in the template, the wiki page will
be inserted there (replacing the string).

==== User-defined fields support ====
Together with the [[plugin:fields|fields plugin]], you can store data in
user-defined fields in your page, and recall this data from anywhere in your
document. See the [[plugin:fields|fields plugin documentation page]] for more
information.

In OpenOffice, user-defined fields are accessible using the Insert menu -> 
Field -> Other, "Variables" tab, and "User fields" section on the left.
