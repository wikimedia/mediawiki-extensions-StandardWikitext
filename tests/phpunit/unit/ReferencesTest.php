<?php

/**
 * @group References
 * @covers StandardWikitext::fixReferences
 */
class ReferencesTest extends MediaWikiUnitTestCase {

	public function testFixReferences(): void {
		// No changes
		$this->assertEquals( "<ref name=\"foo\">bar</ref>", StandardWikitext::fixReferences( "<ref name=\"foo\">bar</ref>" ) );
		$this->assertEquals( "<ref name=\"foo\" />", StandardWikitext::fixReferences( "<ref name=\"foo\" />" ) );

		// Fix spacing
		$this->assertEquals( "<ref name=\"foo\">bar</ref>", StandardWikitext::fixReferences( "<ref name = \"foo\">bar</ref>" ) );
		$this->assertEquals( "<ref name=\"foo\" />", StandardWikitext::fixReferences( "<ref name = \"foo\" />" ) );

		// Fix quotes
		$this->assertEquals( "<ref name=\"foo\">bar</ref>", StandardWikitext::fixReferences( "<ref name=foo>bar</ref>" ) );
		$this->assertEquals( "<ref name=\"foo\" />", StandardWikitext::fixReferences( "<ref name=foo />" ) );
		$this->assertEquals( "<ref name=\"foo\">bar</ref>", StandardWikitext::fixReferences( "<ref name='foo'>bar</ref>" ) );
		$this->assertEquals( "<ref name=\"foo\" />", StandardWikitext::fixReferences( "<ref name='foo' />" ) );

		// Remove spaces or newlines after opening ref tag
		$this->assertEquals( "<ref name=\"foo\">bar</ref>", StandardWikitext::fixReferences( "<ref name=\"foo\"> bar</ref>" ) );
		$this->assertEquals( "<ref name=\"foo\">bar</ref>", StandardWikitext::fixReferences( "<ref name=\"foo\">\nbar</ref>" ) );

		// Fix empty references with name
		$this->assertEquals( "<ref name=\"foo\" />", StandardWikitext::fixReferences( "<ref name=\"foo\"></ref>" ) );

		// Remove empty references
		$this->assertSame( "", StandardWikitext::fixReferences( "<ref></ref>" ) );

		// Remove spaces or newlines around opening ref tags
		$this->assertEquals( "foo.<ref>baz</ref>", StandardWikitext::fixReferences( "foo. <ref>baz</ref>" ) );
		$this->assertEquals( "foo.<ref name=\"bar\">baz</ref>", StandardWikitext::fixReferences( "foo.\n<ref name=\"bar\">baz</ref>" ) );

		// Remove spaces or newlines before closing ref tags
		$this->assertEquals( "foo.<ref>bar</ref>", StandardWikitext::fixReferences( "foo.<ref>bar </ref>" ) );
		$this->assertEquals( "foo.<ref name=\"bar\">baz</ref>", StandardWikitext::fixReferences( "foo.<ref name=\"bar\">baz\n</ref>" ) );

		// Move references after punctuation
		$this->assertEquals( "foo.<ref name=\"bar\">baz</ref>", StandardWikitext::fixReferences( "foo<ref name=\"bar\">baz</ref>." ) );
		$this->assertEquals( "foo;<ref name=\"bar\" />", StandardWikitext::fixReferences( "foo<ref name=\"bar\" />;" ) );
	}
}
