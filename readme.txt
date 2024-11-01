=== Plugin Name ===
Contributors: mattfunk
Donate link: 
Tags: citeulike, formatting, bibtex
Requires at least: 2.8
Tested up to: 3.0
Stable tag: 0.7.2

wpciteulike enables to add a bibliography maintained with CiteULike formatted as HTML to wordpress pages and posts. The input data is the bibtex meta data from CiteULike and the output is HTML. 

== Description ==

If you would like to embed your bibliography from CiteULike into your Wordpress blog, then wpciteulike is the easiest solution for you.

wpciteulike enables to add bibtex entries formatted as HTML in Wordpress pages and posts. The input data comes directly from CiteULike.org, so you don't have to maintain your bibliography at multiple locations and sites. The output is HTML and all the entries are formatted by default using the IEEE style (changeable). Several links such as the Bibtex source file, a RIS version, and links to the PDF, HTML, or RTF versions are also available from the HTML. 

Features:

* embed as many bibliographies as you want from citeulike.org, just provide the user name and the rest is taken care of
* automatic HTML generation and caching for faster page loads
* easy inclusion in wordpress pages/posts by means of a dedicated tag
* access the single bibtex entry source code via citeulike.org
* expose URL and DOI of each document (if provided)
* automatic linking of files that have been added on citeulike.org
* settings page for easy configuration
* filter citeulike bib entries by author
* link to single citations on the page by adding "#citationID" to the URL


The wpciteulike plugin has been developed and tested under Wordpress 2.9 and 3.0 and is being used with Wordpress 3.0.

== Installation ==

1. download the zip file and extract the content of the zip file into a local folder
2. upload the folder wpciteulike into your wp-content/plugins/ directory
3. log in the wordpress administration page and access the Plugins menu
4. activate wpciteulike

== Frequently Asked Questions ==

= How are the entries sorted? =

The entries are sorted by year starting from the most recent.

= How can I personalize the HTML rendering? =

On the settings page, you will find an option to change the CSS styling of the rendered HTML. Just explore, change and don't forget to reset the cache (you can do that also on the settings page).

== A brief example ==

When writing a page/post, you can use the tag [citeulike] as follows:

This is my whole list of publications: [citeulike user=<username>]

If you want to filter the type of items, you can use one of the attributes allow, deny and key as follows:

This is my list of journal articles:
[citeulike user=<username> allow=article]

This is my list of journal articles by author name (e.g. 'Doe:J', 'Smith:W'):
[citeulike user=<username> author=<author name> allow=article]

This is my list of conference articles and technical reports:
[citeulike user=<username> allow=inproceedings,techreport]

This is the rest of my publications:
[citeulike user=<username> deny=article,inproceedings,techreport]
