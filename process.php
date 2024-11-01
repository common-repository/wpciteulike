<?php 

///////////////////////////////////////////////////////////////////////

// starting function, parse wordpress input and generate bibformat html for all linked citeulike user profiles
function wpciteulike($myContent)
{
	// get whether html should be cached after rendering or set YEAH! as default
	$htmlCache = get_option('wpciteulike_html_cache');
	$htmlCache = strlen(trim($htmlCache)) == 0 ? false : true;
	
	// search for all [citeulike user=<username>] tags and extract the user ID and optional filters
	preg_match_all("/\[\s*citeulike\s+user=(.+)(\s+author=(.+))*(\s+(allow|deny|key)=(.+))*]/U", $myContent, $bibItemsSets, PREG_SET_ORDER);
	if ($bibItemsSets)
	{
		$bibItemsSetsCounter = 0;
		foreach ($bibItemsSets as $bibItems)
		{
			// get bibtex for user name
			$user = $bibItems[1];
			$author = isset($bibItems[3]) ? $bibItems[3] : false;
			
			// try to get cached HTML
			if($htmlCache && $cachedHtml = getCachedHtml($user, $bibItemsSetsCounter))
			{
				$myContent = str_replace($bibItems[0], $cachedHtml, $myContent);
			}
			else
			{
				// no cache, try cached bibtex
				list($bib, $msg) = getBibtex($user, $author);

				// process data
				$htmlbib = $bib ? process($bib, $user, $author, isset($bibItems[5]) && isset($bibItems[6]) ? $bibItems[5] : false, isset($bibItems[5]) && isset($bibItems[6]) ? $bibItems[6] : false) : null;
				if($htmlbib != null) {
					if($htmlCache)
					{
						// cache generated HTML
						cacheHtml($user, $htmlbib, $bibItemsSetsCounter);
					}					
				}
				// parsing error
				else {
					// don't cache
					$htmlbib = '<div style="margin: 20px 0; padding: 5px; border: 1px solid #000;">'.
					'This did not really work: <b>'.
					$bibItems[0].
					'</b><br />';
					
					// use backup
					if(restoreBackup($user))
					{
						list($bib, $msg) = getBibtex($user, $author);
						if ($bib)
						{
							$htmlbib .= 'Don\'t worry, there is a backup, see below:';
							
							// process
							$htmlbib2 = process($bib, $user, $author, isset($bibItems[5]) && isset($bibItems[6]) ? $bibItems[5] : false, isset($bibItems[5]) && isset($bibItems[6]) ? $bibItems[6] : false);
							if($htmlbib2 != null) 
							{
								// if good, then maybe cache
								if($htmlCache)
								{
									// cache generated HTML
									cacheHtml($user, $htmlbib2, $bibItemsSetsCounter);
								}
								// but always append to output
								$htmlbib .= $htmlbib2;					
							}
						}
						else
						{
							$htmlbib .= "There seems to be a problem: ".$msg;
						}
					}
					$htmlbib .= '</div>';
				}
				// fill in the content
				$myContent = str_replace($bibItems[0], $htmlbib, $myContent);
			}
			$bibItemsSetsCounter++;
		}     
	}
		
	return $myContent;
}

///////////////////////////////////////////////////////////////////////

// get user bibliography, process it and generate bibformat html
function process($data, $user, $author, $filterType, $filter)
{
	// get bibliography style or set default 'ieee'
	$bibliographyStyle = get_option('wpciteulike_bibliography_style');
	if(strlen(trim($bibliographyStyle)) == 0)
	{
		$bibliographyStyle = 'ieee';
	}
	
	// the user name this plugin shall use to access CiteULike
	$citeulikeUsername = $user;
	$OSBiBPath = dirname(__FILE__) . '/OSBiB/';
	include($OSBiBPath.'format/bibtexParse/PARSEENTRIES.php');
	include($OSBiBPath.'format/BIBFORMAT.php');

	// parse the content of bib string and generate associative array with valid entries
	$parse = new PARSEENTRIES();
	$parse->expandMacro = true;
	$parse->fieldExtract = true;
	$parse->removeDelimit = true;
	$parse->loadBibtexString($data);
	$parse->extractEntries();
	list($preamble, $strings, $entries) = $parse->returnArrays();
	
	// true implies that the input data is in bibtex format
    $bibformat = new BIBFORMAT($OSBiBPath, true);

	// convert BibTeX (and LaTeX) special characters to UTF-8
    $bibformat->cleanEntry=true;
    list($info, $citation, $footnote, $styleCommon, $styleTypes) = $bibformat->loadStyle($OSBiBPath."styles/bibliography/", $bibliographyStyle);
    $bibformat->getStyle($styleCommon, $styleTypes, $footnote);

	// check if parsing was correct
	if(!is_array($entries)) {
		return null;
	}

    // currently sorting descending on year by default
    usort($entries, "wpciteulike_sortbyyear");
	
	$output = '';
	$year = '';
	foreach ($entries as $entry) {
		// Get the resource type ('book', 'article', 'inbook' etc.)
		$resourceType = $entry['bibtexEntryType'];
		
		// adds all the resource elements automatically to the BIBFORMAT::item array
		$bibformat->preProcess($resourceType, $entry);
		$resourceType = $bibformat->type;
		
		// XXXXXXXadditional
		$bibformat->formatTitle($entry['title'], "{", "}");
		if(array_key_exists('edition', $entry) && array_search('edition', $bibformat->styleMap->$type))
			$bibformat->formatEdition($entry['edition']);
		// Pages
		if(array_key_exists('pageStart', $entry) && array_search('pages', $bibformat->styleMap->$type))
		{
			$end = array_key_exists('pageEnd', $entry) ? $entry['pageEnd'] : FALSE;
			$bibformat->formatPages($entry['pageStart'], $end);
		}
		// All other database resource fields that do not require special formatting/conversion.
		$bibformat->addAllOtherItems($entry);

		if($filterType && $filter)
		{
			// apply filters
			$resourceType = str_replace(array('journal_article', 'proceedings_article'), array('article', 'inproceedings'), $resourceType);
			$pos = strpos($filter, $resourceType);
			$bibkey = $entry['bibtexCitation'];

			if ( ( (strcmp($filterType, "allow") === 0) && ($pos === false) ) or
				( (strcmp($filterType, "deny")  === 0) && ($pos !== false) ) or
				( (strcmp($filterType, "key")   === 0) && (strcmp($filter, $bibkey) != 0) ) ) continue;
		}
		
		// check and print year separator
		if(strcmp($entry['year'], $year) != 0)
		{
			$output .= '<h2 class="wpciteulike_year">'.$entry['year'].'</h2>';
			$year = $entry['year'];
		}
		$output .= printEntry(str_replace(array('{', '}'), '', $bibformat->map()), $entry, $user);
    }        

    return $output;
}

///////////////////////////////////////////////////////////////////////

// sort function for years descending
function wpciteulike_sortbyyear($a, $b)
{
	$f1 = isset($a['year']) ? $a['year'] : 1900;
	$f2 = isset($b['year']) ? $b['year'] : 1900; 

	return ($f1 == $f2) ? 0 : ($f1 < $f2) ? 1 : -1;
}

///////////////////////////////////////////////////////////////////////

// print (header with) css formatting
function wpciteulike_header()
{
	// get custom CSS or set default
	$css = get_option('wpciteulike_css_style');
	if(strlen(trim($css)) == 0)
	{
		$css = '
		/* formatting for year separator */
		h2.wpciteulike_year {
			display: block;
		}
		/* formatting of full entry */
		div.wpciteulike_entry {
			margin-bottom: 15px;
			padding-bottom: 15px;
		}
		/* formatting of citation text only */
		div.wpciteulike_bibformat {
			text-indent: -10px;
			margin-left: 10px;
			margin-bottom: 0.5em;
		}
		/* formatting of title in bibformat */
		wpciteulike_title, wpciteulike_title a {
		}
		/* formatting of links below citation text */
		div.wpciteulike_links {
			margin-left: 10px;
		}
		';
	}
	
	echo "<style type=\"text/css\">$css</style>";
}

///////////////////////////////////////////////////////////////////////

// generate html for single entry
function printEntry($bibformat, $entry, $user)
{
	// get if title should be linked or set default: true
	$linkInTitle = get_option('wpciteulike_title_link');
	$linkInTitle = strlen(trim($linkInTitle)) == 0 ? false : true;
	
	// links will lead to the following file types if their URL is given
	$linkTitleTo = array('html', 'pdf' , 'rtf', 'ps', 'doc', 'docx');

	$result = '';

	// output semantics (in HTML source only)
	if(isset($entry['semantics']))
	{
		$result .= '<span class="Z3988" title="'.$entry['semantics'].'"></span>';
	}
	
	// link in title
	if($linkInTitle && isset($entry['citeulike-linkout-0']))
	{
		$bibformat = str_ireplace($entry['title'], '<a href="'.$entry['citeulike-linkout-0'].'" class="wpciteulike_title">'.$entry['title'].'</a>', $bibformat);
	}
	else
	{
		$bibformat = str_replace($entry['title'], '<span class="wpciteulike_title">'.$entry['title'].'</span>', $bibformat);
	}

	// output entry header and bibformat
	$result .= '<div class="wpciteulike_entry"><a name="'.$entry['bibtexCitation'].'"></a>';
	$result .= '<div class="wpciteulike_bibformat">'.formatBibtex($bibformat).'</div>';
	$result .= '<div class="wpciteulike_links">';

	// output all fulltext sources
	$availableTargets = array();
	for($i = 0; $i < 10; $i++) 
	{
		$linkout_index = 'citeulike-linkout-'.$i;
		if(isset($entry[$linkout_index]))
		{
			$linkout = $entry[$linkout_index];
			$linkouttype = getFileExtension($linkout);
			if(strlen($linkouttype) <= 4 && !isDOI($linkout))
			{
				$availableTargets[] = '<a href="'.$linkout.'">'.strtoupper($linkouttype).'</a>';
			}
		}
	}
	if(count($availableTargets) > 0)
	{
		$result .= 'FULL TEXT: '.str_replace('\_', '_', implode(' ', $availableTargets));
		if(isset($entry['doi']))
		{
				$result .= ' | ';
		}
		else
		{
			$result .= '<br>';
		}
	}

	// output doi
	if(isset($entry['doi']))
	{
		$result .= 'DOI: <a href="http://dx.doi.org/'.formatDOI($entry['doi']).'">'.formatDOI($entry['doi']).'</a><br>';
	}
	
	// output references and citeulike link
	$result .= 'REFERENCE: ';
	$result .= '<a href="http://www.citeulike.org/bibtex/user/'.$user.'/article/'.$entry['citeulike-article-id'].'">BibTeX</a>,';
	$result .= ' <a href="http://www.citeulike.org/endnote/user/'.$user.'/article/'.$entry['citeulike-article-id'].'">EndNote (RIS)</a>';
	$result .= ' | CiteULike: <a href="http://www.citeulike.org/article/'.$entry['citeulike-article-id'].'">'.$entry['citeulike-article-id'].'</a>';
	$result .= '</div></div>';
	
	return $result;
}

///////////////////////////////////////////////////////////////////////

// fix small bibtex errors before display
function formatBibtex($entry)
{
	$new_entry = str_replace('\&', '&', $entry);

	return $new_entry;
}

///////////////////////////////////////////////////////////////////////

// clear the cache
function clearCache() 
{
	// first all bib text files
	$bibfiles = glob(dirname(__FILE__).'/data/*.cached.bib');
	if($bibfiles && count($bibfiles) > 0)
	{
		foreach($bibfiles as $bibfile) {
			$backupfile = str_replace('cached', 'backup', $bibfile);
			// remove backup
			if(file_exists($backupfile)) {
				unlink($backupfile);
			}
			// backup current bib
			rename($bibfile, $backupfile);
		}
			
	}
	// second all html cache files
	$datfiles = glob(dirname(__FILE__).'/data/*.dat');
	if($datfiles && count($datfiles) > 0)
	{
		foreach($datfiles as $datfile)
			unlink($datfile);
	}
}

///////////////////////////////////////////////////////////////////////

// restores a backup'd bibtex file 
function restoreBackup($userName)
{
	$backupfile = dirname(__FILE__).'/data/'.strtolower($userName).'.backup.bib';
	if(file_exists($backupfile))
	{
		rename($backupfile, dirname(__FILE__).'/data/'.strtolower($userName).'.cached.bib');
		return TRUE;
	}
	
	return FALSE;
}

///////////////////////////////////////////////////////////////////////

// get cached bibtex data of given citeulike user or retrieve fresh data from citeulike.org
function getBibtex($user, $author)
{
	$url = ($author)
		? 'http://www.citeulike.com/bibtex/user/'.$user.'/author/'.$author
		: 'http://www.citeulike.com/bibtex/user/'.$user.'/bibtex';
	$file = dirname(__FILE__).'/data/'.strtolower($user).'.cached.bib';
    $errorMsg = '';
	// check if cache file exists
	if (!file_exists($file))
	{
		// file is open for write
		$bib = file_get_contents($url);
		if($bib && !empty($bib) && strspn('<!DOCTYPE html PUBLIC', $bib, 0) == 0)
		{
			// if download was successful, cache it
	        $fileInput = fopen($file, "wb");
	        if ($fileInput)
			{
				if(strlen($bib) != fwrite($fileInput, $bib))
				{
					$errorMsg .= 'Failed to write cache filer properly (wrong number of bytes saved. Please check directory permissions according to your Web server privileges. Should be 777.';
					fclose($fileInput);							
				}
				else
				{
					fclose($fileInput);
					return array($bib, null);
				}
	        }
			else
			{
				$errorMsg .= 'Failed to write cache file. Please check directory permissions according to your Web server privileges. Should be 777.';
			}
		}
		else
		{
			$errorMsg .= 'Failed to open URL, please check if your web server has "allow_url_fopen = On" set (e.g. in the php.ini file). ';
		}
	}
	else
	{
		// if a cached version exists, use it
		$bib = file_get_contents($file);
		if ($bib && !empty($bib))
		{
			return array($bib, null);
		}
		else
		{
			$errorMsg .= 'The cache file is empty. There is maybe a problem with your server configuration. Please reload this page, thanks! ';
		}          
	}
   
    return array(false, $errorMsg);
}

///////////////////////////////////////////////////////////////////////

// store the html rendering in cache
function cacheHtml($user, $html, $counter)
{
	$file = dirname(__FILE__)."/data/".strtolower($user).'_'.$counter.'.dat';

	// open file and write contents
	$fileOutput = fopen($file, "wb");
	if ($fileOutput)
	{
		fwrite($fileOutput, $html);
		fclose($fileOutput);
	}
	
}

///////////////////////////////////////////////////////////////////////

// get cached html rendering if available
function getCachedHtml($user, $counter)
{
	$file = dirname(__FILE__).'/data/'.strtolower($user).'_'.$counter.'.dat';
    
	if (file_exists($file))
	{
		$html = file_get_contents($file);
		if ($html && !empty($html))
		{
			return $html;
		}
	}

	return false;
}

///////////////////////////////////////////////////////////////////////

// store bibtex in cache
function cacheBibTex($user, $bibtexEntries)
{
	$file = dirname(__FILE__)."/data/".strtolower($user).'.cached.bib';

	// prepare bibtex output
	$bibTexOutput = '';
	$linkTargets = array('pdf', 'html', 'doc', 'docx', 'rtf', 'ps');
	foreach($bibtexEntries as $entry)
	{
		$bibTexOutput .= substr($entry['bibtexEntry'], 0, strlen($entry['bibtexEntry']) - 1);
		foreach($linkTargets as $target)
		{
			if(isset($entry[$target]))
			{
				$bibTexOutput .= ', pdf = {'.$entry[$target].'}';
			}
		}		
		$bibTexOutput .= ', fulltextscan = {true} }
';
	}

	// open file and write contents
	$fileOutput = fopen($file, "wb");
	if ($fileOutput)
	{
		fwrite($fileOutput, $bibTexOutput);
		fclose($fileOutput);
	}
	else
	{
		echo "Failed to write file" . $file . " - check directory permission according to your Web server privileges. Permissions should be set to e.g. like 777.";
	}
}

///////////////////////////////////////////////////////////////////////

// gets file extension
function getFileExtension($filename) {
    $pos = strrpos($filename, '.');
    if($pos===false) {
        return false;
    } else {
        return substr($filename, $pos+1);
    }
}

///////////////////////////////////////////////////////////////////////

// returns whether the link is a DOI link 
function isDOI($link) {
	return strpos($link, "http://dx.doi.org/") !== false || strpos($link, "http://doi.acm.org/") !== false;
}

///////////////////////////////////////////////////////////////////////

// returns formatted DOI
function formatDOI($doi) {
	return str_replace('\_', '_', $doi);
}

///////////////////////////////////////////////////////////////////////

// returns contents of url
function curl_file_get_contents($URL) {
	$c = curl_init();
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_URL, $URL);
	$contents = curl_exec($c);
	curl_close($c);
	return $contents ? $contents : FALSE;
}

?>