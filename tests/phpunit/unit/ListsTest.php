<?php

/**
 * @group Lists
 * @covers StandardWikitext::fixLists
 */
class ListsTest extends MediaWikiUnitTestCase {

	public function testFixLists(): void {
		// No changes
		$this->assertEquals( "* a\n* b\n* c", StandardWikitext::fixLists( "* a\n* b\n* c" ) );
		$this->assertEquals( "# a\n# b\n# c", StandardWikitext::fixLists( "# a\n# b\n# c" ) );

		// Remove extra spaces between list items
		$this->assertEquals( "* a\n** b\n*** c", StandardWikitext::fixLists( "*a\n* *b\n* * *c" ) );

		// Remove empty list items
		$this->assertEquals( "* a\n* c", StandardWikitext::fixLists( "* a\n* \n* c" ) );
		$this->assertEquals( "* a\n* b\n", StandardWikitext::fixLists( "* a\n* b\n*" ) );

		// Add initial space to list items
		$this->assertEquals( "* a\n* b\n* c", StandardWikitext::fixLists( "*a\n*b\n*c" ) );
		$this->assertEquals( "# a\n# b\n# c", StandardWikitext::fixLists( "#a\n#b\n#c" ) );

		// Remove newlines between lists
		$this->assertEquals( "* a\n* b\n* c", StandardWikitext::fixLists( "* a\n\n* b\n\n* c" ) );

		// Give lists some room
		$this->assertEquals( "foo\n\n* a\n* b\n* c\n\nbar", StandardWikitext::fixLists( "foo\n*a\n*b\n*c\nbar" ) );
	}
}
