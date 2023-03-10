<?php

/**
 * @group Links
 */
class LinksTest extends MediaWikiUnitTestCase {

    public function testFixLink(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo]]" ), "[[foo]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo]]s" ), "[[foo]]s" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[Foo|bar]]" ), "[[Foo|bar]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[Foo|bar_baz]]" ), "[[Foo|bar_baz]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "foo\n[[bar]]\nbaz" ), "foo\n[[bar]]\nbaz" );

        // Capitalization
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo|bar]]" ), "[[Foo|bar]]" );

        // Underscores
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo_bar]]" ), "[[foo bar]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[Foo_bar|baz]]" ), "[[Foo bar|baz]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo|bar_baz]]" ), "[[Foo|bar_baz]]" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixLinks( "[[ foo ]]" ), "[[foo]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[ foo | bar ]]" ), "[[Foo|bar]]" );

        // Other
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo|foo]]" ), "[[foo]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[fo%C3%B3]]" ), "[[foó]]" );
    }

    public function testFixFileLink(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg]]" ), "[[File:Foo.jpg]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb]]" ), "[[File:Foo.jpg|thumb]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb|300px]]" ), "[[File:Foo.jpg|thumb|300px]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb|300px|Caption]]" ), "[[File:Foo.jpg|thumb|300px|Caption]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[:File:Foo.jpg]]" ), "[[:File:Foo.jpg]]" );

        // Underscores
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo_bar.jpg]]" ), "[[File:Foo bar.jpg]]" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixLinks( "[[ File:Foo.jpg ]]" ), "[[File:Foo.jpg]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[ File:Foo.jpg | thumb ]]" ), "[[File:Foo.jpg|thumb]]" );

        // Other
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg|thumb|right]]" ), "[[File:Foo.jpg|thumb]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg|right|thumb]]" ), "[[File:Foo.jpg|thumb]]" );
        $this->assertEquals( StandardWikitext::fixLinks( "[[File:Foo.jpg|right|thumb|300px]]" ), "[[File:Foo.jpg|thumb|300px]]" );
    }
}