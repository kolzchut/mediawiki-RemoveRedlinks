<?php

namespace MediaWiki\Extension\RemoveRedlinks\Tests\Integration;

use MediaWiki\Context\RequestContext;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use ParserOptions;

/**
 * @group Database
 * @covers \MediaWiki\Extension\RemoveRedlinks\Hooks
 */
class RemoveRedlinksTest extends MediaWikiIntegrationTestCase {

	private const MISSING_PAGE = 'RemoveRedlinksTest no such page';

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'RemoveRedLinksExemptGroups', [ 'removeredlinks-test' ] );
	}

	private function getExemptUser(): User {
		return $this->getTestUser( [ 'removeredlinks-test' ] )->getUser();
	}

	private function parse( ParserOptions $options ): string {
		$parser = $this->getServiceContainer()->getParserFactory()->getInstance();
		return $parser->parse(
			'[[' . self::MISSING_PAGE . ']]',
			PageReferenceValue::localReference( NS_MAIN, 'RemoveRedlinksTest' ),
			$options
		)->getRawText();
	}

	public function testAnonymousOptionsRemoveRedLinksEvenWhenAnExemptUserMakesTheRequest() {
		RequestContext::getMain()->setUser( $this->getExemptUser() );

		$html = $this->parse( ParserOptions::newFromAnon() );

		$this->assertStringNotContainsString( 'redlink=1', $html );
		$this->assertStringContainsString( self::MISSING_PAGE, $html );
	}

	public function testExemptOptionsKeepRedLinksEvenWhenTheRequestIsAnonymous() {
		RequestContext::getMain()->setUser( new User() );

		$html = $this->parse( ParserOptions::newFromUser( $this->getExemptUser() ) );

		$this->assertStringContainsString( 'redlink=1', $html );
	}

	public function testExemptAndOtherUsersGetDifferentCacheKeys() {
		$anonHash = ParserOptions::newFromAnon()->optionsHash( [] );
		$exemptHash = ParserOptions::newFromUser( $this->getExemptUser() )->optionsHash( [] );

		$this->assertNotSame( $anonHash, $exemptHash );
		$this->assertStringContainsString( '!showRedLinks', $exemptHash );
	}

	public function testLinksOutsideTheParserGoByTheRequestUser() {
		$linkRenderer = $this->getServiceContainer()->getLinkRenderer();
		$target = Title::newFromText( self::MISSING_PAGE );

		RequestContext::getMain()->setUser( $this->getExemptUser() );
		$this->assertStringContainsString( 'redlink=1', $linkRenderer->makeLink( $target ) );

		RequestContext::getMain()->setUser( new User() );
		$this->assertStringNotContainsString( 'redlink=1', $linkRenderer->makeLink( $target ) );
	}
}
