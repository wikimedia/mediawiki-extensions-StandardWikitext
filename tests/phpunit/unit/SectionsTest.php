<?php

/**
 * @group Sections
 */
class SectionsTest extends MediaWikiUnitTestCase {

    public function testFixSections(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixSections( "== Section ==" ), "== Section ==" );
        $this->assertEquals( StandardWikitext::fixSections( "== Section ==\n\n=== Subsection ===" ), "== Section ==\n\n=== Subsection ===" );
        $this->assertEquals( StandardWikitext::fixSections( "== Section ==\n\nText\n\n=== Subsection ===\n\nText" ), "== Section ==\n\nText\n\n=== Subsection ===\n\nText" );

        // Fix spacing
        $this->assertEquals( StandardWikitext::fixSections( "==Section==" ), "== Section ==" );
        $this->assertEquals( StandardWikitext::fixSections( "== Section ==\n=== Subsection ===" ), "== Section ==\n\n=== Subsection ===" );
        $this->assertEquals( StandardWikitext::fixSections( "== Section ==\nText\n=== Subsection ===\nText" ), "== Section ==\n\nText\n\n=== Subsection ===\n\nText" );

        // Remove bold
        $this->assertEquals( StandardWikitext::fixSections( "== '''Section''' ==" ), "== Section ==" );

        // Remove trailing colon
        $this->assertEquals( StandardWikitext::fixSections( "== Section: ==" ), "== Section ==" );
    }
}