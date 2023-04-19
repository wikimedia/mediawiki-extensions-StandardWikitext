<?php

/**
 * @group Tables
 * @covers StandardWikitext::fixTables
 */
class TablesTest extends MediaWikiUnitTestCase {

	public function testFixTables(): void {
		// No changes
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n! Header\n| Cell\n|}" ) );
		$this->assertEquals( "{| class=\"wikitable\"\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{| class=\"wikitable\"\n! Header\n| Cell\n|}" ) );

		// Add leading spaces
		$this->assertEquals( "{|\n! Header\n! Header\n| Cell\n| Cell\n|}", StandardWikitext::fixTables( "{|\n!Header!!Header\n|Cell||Cell\n|}" ) );
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n!Header\n|Cell\n|}" ) );

		// Remove empty caption
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n|+\n! Header\n| Cell\n|}" ) );

		// Remove newrow after caption
		$this->assertEquals( "{|\n|+ Caption\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n|+ Caption\n|-\n! Header\n| Cell\n|}" ) );

		// Remove leading newrow
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n|-\n! Header\n| Cell\n|}" ) );

		// Remove trailing newrow
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n! Header\n| Cell\n|-\n|}" ) );

		// Pseudo-headers
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n! '''Header'''\n| Cell\n|}" ) );
		$this->assertEquals( "{|\n! Header\n| Cell\n|}", StandardWikitext::fixTables( "{|\n| '''Header'''\n| Cell\n|}" ) );
	}
}
