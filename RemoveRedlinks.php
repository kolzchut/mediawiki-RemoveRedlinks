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
 * @author Dror S [FFS]
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
 
$wgExtensionCredits['other'][] = array(
	'path'			=> __FILE__,
	'name'			=> 'RemoveRedlinks',
	'description'	=> 'Removes all redlinks from page output for certain groups',
	'url'			=> 'https://github.com/kolzchut/mediawiki-RemoveRedlinks',
	'version'		=> '2.0.2',
	'author'		=> array(
		'[mailto:innocentkiller@gmail.com Chad Horohoe]',
		'Dror S. [FFS] ([http://www.kolzchut.org.il Kol-Zchut])'
	),
	'license-name'	=> 'GPL-2.0+',
	'descriptionmsg' => 'ext-removeredlinks-desc',

);

// Groups that should still see red links
$wgRemoveRedLinksExemptGroups = array();

// i18n
$GLOBALS['wgMessagesDirs']['RemoveRedlinks'] = __DIR__ . '/i18n';

// Hooks
$wgHooks['LinkEnd'][] = 'RemoveRedLinks::onLinkEnd'; // Cancel red links for some users
$wgHooks['PageRenderingHash'][] = 'RemoveRedLinks::onPageRenderingHash';


class RemoveRedLinks {

	protected static function isExempt( User $user ) {
		global $wgRemoveRedLinksExemptGroups;
		if ( empty( $wgRemoveRedLinksExemptGroups ) ) {
			return false;
		}

		$userGroups = $user->getEffectiveGroups();
		$match = array_intersect( $userGroups, $wgRemoveRedLinksExemptGroups );
		if ( !empty( $match ) ) {
			return true;
		}

		return false;
	}

	public static function onPageRenderingHash( &$confstr, User $user, $optionsUsed ) {
		if ( self::isExempt( $user ) ) {
			$confstr .= "!showRedLinks";
		}

		return true;
	}

	public static function onLinkEnd(
		$dummy, Title $target, array $options, &$text, array &$attribs, &$ret
	) {
		// return if link is known to be good or the user's group is exempt from this
		$user = RequestContext::getMain()->getUser();
		if ( in_array( 'known', $options, true ) || self::isExempt( $user ) ) {
			return true;
		}

		if ( in_array( 'broken', $options, true ) ) {
			$ret = $text;
			return false;
		}

		// If we got to here, it's all good
		return true;
	}

}
