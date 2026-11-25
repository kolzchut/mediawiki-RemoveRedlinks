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
use MediaWiki\Parser\Parser;
use MediaWiki\User\UserIdentity;
use RequestContext;
use Title;
use User;
use WeakMap;
use WeakReference;

class Hooks {

	/**
	 * Each parser's own LinkRenderer, mapped to that parser, so a link can be judged by the
	 * user of the parser options it is rendered for.
	 *
	 * @var WeakMap<LinkRenderer,WeakReference<Parser>>|null
	 */
	private static ?WeakMap $parsersByLinkRenderer = null;

	protected static function isExempt( UserIdentity $user ) {
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

	/**
	 * ParserFirstCallInit hook handler, called once for each new parser.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {
		self::$parsersByLinkRenderer ??= new WeakMap();
		self::$parsersByLinkRenderer[ $parser->getLinkRenderer() ] = WeakReference::create( $parser );
	}

	/**
	 * The user a link is rendered for. For a link the parser renders, that is the user of
	 * the parser options, which the parser cache key is built from (see onPageRenderingHash).
	 * It is not the user making the web request: core renders other users' copies inside
	 * a request, e.g. the anonymous copy when an editor saves a page. Links rendered
	 * outside the parser go by the request user.
	 *
	 * @param LinkRenderer $linkRenderer
	 * @return UserIdentity|null Null when the request user is not safe to load yet
	 */
	private static function getUserForLinks( LinkRenderer $linkRenderer ): ?UserIdentity {
		$parserRef = self::$parsersByLinkRenderer[ $linkRenderer ] ?? null;
		$parser = $parserRef ? $parserRef->get() : null;
		$options = $parser ? $parser->getOptions() : null;
		if ( $options ) {
			return $options->getUserIdentity();
		}

		$user = RequestContext::getMain()->getUser();
		return $user->isSafeToLoad() ? $user : null;
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
		$user = self::getUserForLinks( $linkRenderer );
		if ( $user ) {
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
