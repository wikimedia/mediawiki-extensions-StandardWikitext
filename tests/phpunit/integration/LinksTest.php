<?php

/**
 * @group Links
 * @covers StandardWikitext::fixLinks
 */
class LinksTest extends MediaWikiIntegrationTestCase {

	public function testFixLink(): void {
		// No changes
		$this->assertEquals( "[[foo]]", StandardWikitext::fixLinks( "[[foo]]" ) );
		$this->assertEquals( "[[foo]]s", StandardWikitext::fixLinks( "[[foo]]s" ) );
		$this->assertEquals( "[[Foo|bar]]", StandardWikitext::fixLinks( "[[Foo|bar]]" ) );
		$this->assertEquals( "[[Foo|bar_baz]]", StandardWikitext::fixLinks( "[[Foo|bar_baz]]" ) );
		$this->assertEquals( "foo [[bar]] baz", StandardWikitext::fixLinks( "foo [[bar]] baz" ) );
		$this->assertEquals( "[[foo+bar]]", StandardWikitext::fixLinks( "[[foo+bar]]" ) );

		// Fix fake external links
		$this->assertEquals( "[https://foo.com]", StandardWikitext::fixLinks( "[[https://foo.com]]" ) );
		$this->assertEquals( "[https://foo.com Foo]", StandardWikitext::fixLinks( "[[https://foo.com Foo]]" ) );

		// Capitalization
		$this->assertEquals( "[[Foo|bar]]", StandardWikitext::fixLinks( "[[foo|bar]]" ) );

		// Underscores
		$this->assertEquals( "[[foo bar]]", StandardWikitext::fixLinks( "[[foo_bar]]" ) );
		$this->assertEquals( "[[Foo bar|baz]]", StandardWikitext::fixLinks( "[[Foo_bar|baz]]" ) );
		$this->assertEquals( "[[Foo|bar_baz]]", StandardWikitext::fixLinks( "[[foo|bar_baz]]" ) );

		// Spacing
		$this->assertEquals( "[[foo]]", StandardWikitext::fixLinks( "[[ foo ]]" ) );
		$this->assertEquals( "[[Foo|bar]]", StandardWikitext::fixLinks( "[[ foo | bar ]]" ) );

		// Other
		$this->assertEquals( "[[foo]]", StandardWikitext::fixLinks( "[[foo|foo]]" ) );
		$this->assertEquals( "[[foó]]", StandardWikitext::fixLinks( "[[fo%C3%B3]]" ) );
	}

	public function testFixFileLink(): void {
		// No changes
		$this->assertEquals( "[[File:Foo.jpg]]", StandardWikitext::fixLinks( "[[File:Foo.jpg]]" ) );
		$this->assertEquals( "[[File:Foo.jpg|thumb]]", StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb]]" ) );
		$this->assertEquals( "[[File:Foo.jpg|thumb|300px]]", StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb|300px]]" ) );
		$this->assertEquals( "[[File:Foo.jpg|thumb|300px|Caption]]", StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb|300px|Caption]]" ) );
		$this->assertEquals( "[[:File:Foo.jpg]]", StandardWikitext::fixLinks( "[[:File:Foo.jpg]]" ) );

		// Underscores
		$this->assertEquals( "[[File:Foo bar.jpg]]", StandardWikitext::fixLinks( "[[File:Foo_bar.jpg]]" ) );

		// Spacing
		$this->assertEquals( "[[File:Foo.jpg]]", StandardWikitext::fixLinks( "[[ File:Foo.jpg ]]" ) );
		$this->assertEquals( "[[File:Foo.jpg|thumb]]", StandardWikitext::fixLinks( "[[ File:Foo.jpg | thumb ]]" ) );

		// Other
		$this->assertEquals( "[[File:Foo.jpg|thumb]]", StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb|right]]" ) );
		$this->assertEquals( "[[File:Foo.jpg|thumb]]", StandardWikitext::fixLinks( "[[File:Foo.jpg|right|thumb]]" ) );
		$this->assertEquals( "[[File:Foo.jpg|thumb|300px]]", StandardWikitext::fixLinks( "[[File:Foo.jpg|right|thumb|300px]]" ) );
	}
}
