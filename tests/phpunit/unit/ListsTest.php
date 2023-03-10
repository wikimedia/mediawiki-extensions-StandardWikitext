<?php

/**
 * @group Lists
 */
class ListsTest extends MediaWikiUnitTestCase {

    public function testFixLists(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixLists( "* a\n* b\n* c" ), "* a\n* b\n* c" );
        $this->assertEquals( StandardWikitext::fixLists( "# a\n# b\n# c" ), "# a\n# b\n# c" );
        $this->assertEquals( StandardWikitext::fixLists( "#REDIRECT [[Foo]]" ), "#REDIRECT [[Foo]]" );

		// Fix unordered lists with wrong items
        $this->assertEquals( StandardWikitext::fixLists( "- a\n- b\n- c" ), "* a\n* b\n* c" );

		// Fix ordered lists with wrong items
        $this->assertEquals( StandardWikitext::fixLists( "1. a\n2. b\n3. c" ), "# a\n# b\n# c" );

		// Remove extra spaces between list items
        $this->assertEquals( StandardWikitext::fixLists( "*a\n* *b\n* * *c" ), "* a\n** b\n*** c" );

		// Remove empty list items
        $this->assertEquals( StandardWikitext::fixLists( "* a\n* \n* c" ), "* a\n* c" );
        $this->assertEquals( StandardWikitext::fixLists( "* a\n* b\n*" ), "* a\n* b\n" );

		// Add initial space to list items
        $this->assertEquals( StandardWikitext::fixLists( "*a\n*b\n*c" ), "* a\n* b\n* c" );
        $this->assertEquals( StandardWikitext::fixLists( "#a\n#b\n#c" ), "# a\n# b\n# c" );

		// Remove newlines between lists
        $this->assertEquals( StandardWikitext::fixLists( "* a\n\n* b\n\n* c" ), "* a\n* b\n* c" );

		// Give lists some room
        $this->assertEquals( StandardWikitext::fixLists( "foo\n*a\n*b\n*c\nbar" ), "foo\n\n* a\n* b\n* c\n\nbar" );
    }
}