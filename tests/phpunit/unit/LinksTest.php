<?php

/**
 * @group Links
 */
class LinksTest extends MediaWikiUnitTestCase {

    public function testFixLink(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixLink( "[[foo]]" ), "[[foo]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[Foo|bar]]" ), "[[Foo|bar]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[Foo|bar_baz]]" ), "[[Foo|bar_baz]]" );

        // Capitalization
        $this->assertEquals( StandardWikitext::fixLink( "[[foo|bar]]" ), "[[Foo|bar]]" );

        // Underscores
        $this->assertEquals( StandardWikitext::fixLink( "[[foo_bar]]" ), "[[foo bar]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[Foo_bar|baz]]" ), "[[Foo bar|baz]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[foo|bar_baz]]" ), "[[Foo|bar_baz]]" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixLink( "[[ foo ]]" ), "[[foo]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[ foo | bar ]]" ), "[[Foo|bar]]" );

        // Other
        $this->assertEquals( StandardWikitext::fixLink( "[[foo|foo]]" ), "[[foo]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[fo%C3%B3]]" ), "[[foó]]" );
    }

    public function testFixFileLink(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg]]" ), "[[File:Foo.jpg]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg|thumb]]" ), "[[File:Foo.jpg|thumb]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg|thumb|300px]]" ), "[[File:Foo.jpg|thumb|300px]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg|thumb|300px|Caption]]" ), "[[File:Foo.jpg|thumb|300px|Caption]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[:File:Foo.jpg]]" ), "[[:File:Foo.jpg]]" );

        // Underscores
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo_bar.jpg]]" ), "[[File:Foo bar.jpg]]" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixLink( "[[ File:Foo.jpg ]]" ), "[[File:Foo.jpg]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[ File:Foo.jpg | thumb ]]" ), "[[File:Foo.jpg|thumb]]" );

        // Other
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg|thumb|right]]" ), "[[File:Foo.jpg|thumb]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg|right|thumb]]" ), "[[File:Foo.jpg|thumb]]" );
        $this->assertEquals( StandardWikitext::fixLink( "[[File:Foo.jpg|right|thumb|300px]]" ), "[[File:Foo.jpg|thumb|300px]]" );
    }

    public function testFixLinks(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixLinks( "[[foo]]s" ), "[[foo]]s" );

        //$this->assertEquals( StandardWikitext::fixLinks( "foo\n[[bar]]\nbaz" ), "foo\n\n[[bar]]\n\nbaz" );
        //$this->assertEquals( StandardWikitext::fixLinks( "foo\n[[File:Bar.jpg]]\nbaz" ), "foo\n\n[[File:Bar.jpg]]\n\nbaz" );
    }
}