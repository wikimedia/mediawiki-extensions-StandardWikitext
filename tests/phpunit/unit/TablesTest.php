<?php

/**
 * @group Tables
 */
class TablesTest extends MediaWikiUnitTestCase {

    public function testFixTables(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixTable( "{|\n! Header\n| Cell\n|}" ), "{|\n! Header\n| Cell\n|}" );
        $this->assertEquals( StandardWikitext::fixTable( "{| class=\"wikitable\"\n! Header\n| Cell\n|}" ), "{| class=\"wikitable\"\n! Header\n| Cell\n|}" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixTable( "{|\n!Header!!Header\n|Cell||Cell\n|}" ), "{|\n! Header\n! Header\n| Cell\n| Cell\n|}" );
        $this->assertEquals( StandardWikitext::fixTable( "{|\n!Header\n|Cell\n|}" ), "{|\n! Header\n| Cell\n|}" );
        $this->assertEquals( StandardWikitext::fixTable( "{|\n! Header \n| Cell \n|}" ), "{|\n! Header\n| Cell\n|}" );

        // Remove empty caption
        $this->assertEquals( StandardWikitext::fixTable( "{|\n|+\n! Header\n| Cell\n|}" ), "{|\n! Header\n| Cell\n|}" );

        // Remove newrow after caption
        $this->assertEquals( StandardWikitext::fixTable( "{|\n|+ Caption\n|-\n! Header\n| Cell\n|}" ), "{|\n|+ Caption\n! Header\n| Cell\n|}" );

        // Remove leading newrow
        $this->assertEquals( StandardWikitext::fixTable( "{|\n|-\n! Header\n| Cell\n|}" ), "{|\n! Header\n| Cell\n|}" );

        // Remove trailing newrow
        $this->assertEquals( StandardWikitext::fixTable( "{|\n! Header\n| Cell\n|-\n|}" ), "{|\n! Header\n| Cell\n|}" );

        // Pseudo-headers
        $this->assertEquals( StandardWikitext::fixTable( "{|\n! '''Header'''\n| Cell\n|}" ), "{|\n! Header\n| Cell\n|}" );
        $this->assertEquals( StandardWikitext::fixTable( "{|\n| '''Header'''\n| Cell\n|}" ), "{|\n! Header\n| Cell\n|}" );
    }
}