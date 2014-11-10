<?php

/**
 * Extension:RemoveRedlinks - Hide all redlinks and turn them into
 * their static text.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Chad Horohoe <innocentkiller@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
 
$wgExtensionCredits['other'][] = array(
	'path'			=> __FILE__,
	'name'			=> 'RemoveRedlinks',
	'description'	=> 'Removes all redlinks from page output for certain groups',
	'url'			=> 'https://mediawiki.org/wiki/Extension:RemoveRedlinks',
	'version'		=> '2.0',
	'author'		=> array( '[mailto:innocentkiller@gmail.com Chad Horohoe]', 'Dror S. ([http://www.kolzchut.org.il Kol-Zchut])' ),
	'license-name'	=> 'GPL-2.0+'
);

// Groups that should still see red links
$wgRemoveRedLinksExemptGroups = array();

// Hook Registering
$wgHooks['LinkEnd'][] = 'RemoveRedLinks::onLinkEnd'; // Cancel red links for some users
$wgHooks['PageRenderingHash'][] = 'RemoveRedLinks::onPageRenderingHash';

class RemoveRedLinks {

	function onPageRenderingHash( &$confstr, $user, $optionsUsed ) {
		global $wgRemoveRedLinksExemptGroups;

		if( empty( $wgRemoveRedLinksExemptGroups ) ) {
			return true;
		}

		$userGroups = $user->getEffectiveGroups(true);
		$match = array_intersect( $userGroups, $wgRemoveRedLinksExemptGroups );
		if( !empty( $match ) ) {
			$confstr .= "!showRedLinks";
		}

		return true;
	}

	function onLinkEnd( $dummy, Title $target, array $options, &$text, array &$attribs, &$ret ) {
		global $wgRemoveRedLinksExemptGroups, $wgUser;

		// return possibly if our group is exempt from this
		$userGroups = $wgUser->getEffectiveGroups(true);
		$match = array_intersect( $userGroups, $wgRemoveRedLinksExemptGroups );
		if( !empty( $match ) ) {
			return true;
		}

		if ( in_array( 'broken', $options ) ) {
			$ret = $text;
			return false;
		} else {
			// we know it's good
			return true;
		}
	}

}
