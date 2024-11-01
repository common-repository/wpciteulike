<?php

function wpciteulike_display_options()
{
	
	if(strcmp(get_option('wpciteulike_reset_cache'), '1') == 0)
	{
		update_option('wpciteulike_reset_cache', 0);
		clearCache();
		echo '
		<script type="text/javascript" charset="utf-8">
			document.getElementById("message").innerHTML = "<p><strong>Cache has been reset, reload your publications page to re-generate it (might take a few minutes).</strong></p>";
		</script>
		';
	}
	
	// prepare css
	$css_styles = get_option('wpciteulike_css_style');
	if(strlen(trim($css_styles)) == 0)
	{
		$css_styles = '
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
	
	echo '
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br /></div>
	<h2>CiteULike Settings</h2>

	<form method="post" action="options.php">';
	settings_fields( 'wpciteulike_display_options' );
	echo '
	<table class="form-table">
	<tr valign="top">
	<th scope="row">Bibliography style</th>
	<td>
		<select name="wpciteulike_bibliography_style">
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'IEEE') == 0)? ' selected="selected"' : '').'>IEEE</option>
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'apa') == 0)? ' selected="selected"':'').'>apa</option>
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'britishmedicaljournal') == 0)? ' selected="selected"':'').'>britishmedicaljournal</option>
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'chicago') == 0)? ' selected="selected"':'').'>chicago</option>
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'harvard') == 0)? ' selected="selected"':'').'>harvard</option>
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'mla') == 0)? ' selected="selected"':'').'>mla</option>
			<option'.((strcmp(get_option('wpciteulike_bibliography_style'), 'turabian') == 0)? ' selected="selected"':'').'>turabian</option>
		</select>
		<span class="description">Choose which style the rendered bibliography should have</span>
	</td>
	</tr>

	<tr valign="top">
	<th scope="row">Links in titles</th>
	<td>
		<input type="checkbox" name="wpciteulike_title_link" id="title_link" value="1" ';
		checked('1', get_option('wpciteulike_title_link'));
		echo ' />
		<span class="description">Select for links in titles of bibliography items.</span>
	</td>
	</tr>

	<tr valign="top">
	<th scope="row">Cache HTML</th>
	<td>
		<input type="checkbox" name="wpciteulike_html_cache" id="html_cache" value="1" ';
		checked('1', get_option('wpciteulike_html_cache'));
		echo ' />
		<span class="description">Select for HTML caching and thus faster page loads.</span>
	</td>
	</tr>

	<tr valign="top">
	<th scope="row">Custom CSS style</th>
	<td>
		<textarea rows="10" cols="60" name="wpciteulike_css_style">'.$css_styles.'</textarea>
	</td>
	</tr>
	
	<tr valign="top">
	<th scope="row">
		<p class="submit">
			<input type="submit" class="button-primary" style="float:left" value="Save Changes" />
		</p>
	</th>
	<td>
		<input type="hidden" name="wpciteulike_reset_cache" value="0" />
	</td>
	</tr>
	</table>
	</form>
	
	<br />
	
	<form method="post" action="options.php">';
	settings_fields( 'wpciteulike_display_options' );
	echo '
	<input type="hidden" name="wpciteulike_reset_cache" value="1" />
	<input type="hidden" name="wpciteulike_bibliography_style" value="'.get_option('wpciteulike_bibliography_style').'" />
	<input type="hidden" name="wpciteulike_html_cache" value="'.get_option('wpciteulike_html_cache').'" />
	<input type="hidden" name="wpciteulike_title_link" value="'.get_option('wpciteulike_title_link').'" />
	<input type="hidden" name="wpciteulike_css_style" value="'.get_option('wpciteulike_css_style').'" />
	<table class="form-table">
	<tr valign="top">
	<th scope="row" valign="top">
		<p class="submit">
				<input type="submit" class="button-primary" value="Reset Cache" />
		</p>
	</th>
	<td>
		<span class="description">
			If you would like to clear the HTML cache after changes in bibliography style or CSS, do it below.
			Resetting the cache might be necessary after adding or changing your publication list on citeulike.org
			or if you changed the style your bibliography is rendered above.
		</span>
	</td>
	</tr>
	</table>
	</form>
	</div>
	';

}

?>