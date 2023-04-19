<?php

/**
 * @group Sections
 * @covers StandardWikitext::fixSections
 */
class SectionsTest extends MediaWikiUnitTestCase {

	public function testFixSections(): void {
		// No changes
		$this->assertEquals( "== Section ==", StandardWikitext::fixSections( "== Section ==" ) );
		$this->assertEquals( "== Section ==\n\n=== Subsection ===", StandardWikitext::fixSections( "== Section ==\n\n=== Subsection ===" ) );
		$this->assertEquals( "== Section ==\n\nText\n\n=== Subsection ===\n\nText", StandardWikitext::fixSections( "== Section ==\n\nText\n\n=== Subsection ===\n\nText" ) );

		// Fix spacing
		$this->assertEquals( "== Section ==", StandardWikitext::fixSections( "==Section==" ) );
		$this->assertEquals( "== Section ==\n\n=== Subsection ===", StandardWikitext::fixSections( "== Section ==\n=== Subsection ===" ) );
		$this->assertEquals( "== Section ==\n\nText\n\n=== Subsection ===\n\nText", StandardWikitext::fixSections( "== Section ==\nText\n=== Subsection ===\nText" ) );

		// Remove bold
		$this->assertEquals( "== Section ==", StandardWikitext::fixSections( "== '''Section''' ==" ) );

		// Remove trailing colon
		$this->assertEquals( "== Section ==", StandardWikitext::fixSections( "== Section: ==" ) );
	}
}
