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
 * @author Malte Ahrhodldt <nordicnames.de>
 * based on previous work Chad Horohoe <innocentkiller@gmail.com>
 * @author Dror S [FFS]
 * @author Chad Horohoe <innocentkiller@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

namespace MediaWiki\Extension\RemoveRedlinks;


use HtmlArmor;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use RequestContext;
use Title;
use User;

class Hooks {

	protected static function isExempt( User $user ) {
		global $wgRemoveRedLinksExemptGroups;
		if ( empty( $wgRemoveRedLinksExemptGroups ) ) {
			return false;
		}

		$userGroups = MediaWikiServices::getInstance()
		                               ->getUserGroupManager()
		                               ->getUserEffectiveGroups( $user );
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
	}

	/**
	 * HtmlPageLinkRendererEnd hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/HtmlPageLinkRendererEnd
	 *
	 * Used to change interwiki links
	 *
	 * @param LinkRenderer $linkRenderer
	 * @param LinkTarget $target
	 * @param $isKnown
	 * @param &$text
	 * @param &$attribs []
	 * @param &$ret
	 *
	 * @return bool
	 */
	public static function onHtmlPageLinkRendererEnd(
		LinkRenderer $linkRenderer, LinkTarget $target, $isKnown, &$text, &$attribs, &$ret
	) {
		// If it's not a broken (red) link, exit.
		if ( $isKnown ) {
			return true;
		}

		// If the user is exempt from this, exit
		$user = RequestContext::getMain()->getUser();
		if ( $user->isSafeToLoad() ) {
			if ( self::isExempt( $user ) || !$target instanceof Title ) {
				return true;
			}
		}

		// The link is broken and the user isn't exempt, so make it plain text
		// and prevent further processing
		$ret = HtmlArmor::getHtml( $text );
		return false;
	}

}
