<?php

/**
 * @group References
 */
class ReferencesTest extends MediaWikiUnitTestCase {

    public function testFixReferences(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=\"foo\">bar</ref>" ), "<ref name=\"foo\">bar</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=\"foo\" />" ), "<ref name=\"foo\" />" );

        // Fix spacing
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name = \"foo\">bar</ref>" ), "<ref name=\"foo\">bar</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name = \"foo\" />" ), "<ref name=\"foo\" />" );

        // Fix quotes
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=foo>bar</ref>" ), "<ref name=\"foo\">bar</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=foo />" ), "<ref name=\"foo\" />" );
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name='foo'>bar</ref>" ), "<ref name=\"foo\">bar</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name='foo' />" ), "<ref name=\"foo\" />" );

		// Remove spaces or newlines after opening ref tag
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=\"foo\"> bar</ref>" ), "<ref name=\"foo\">bar</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=\"foo\">\nbar</ref>" ), "<ref name=\"foo\">bar</ref>" );

        // Fix empty references with name
        $this->assertEquals( StandardWikitext::fixReferences( "<ref name=\"foo\"></ref>" ), "<ref name=\"foo\" />" );

        // Remove empty references
        $this->assertEquals( StandardWikitext::fixReferences( "<ref></ref>" ), "" );

        // Remove spaces or newlines around opening ref tags
        $this->assertEquals( StandardWikitext::fixReferences( "foo. <ref>baz</ref>" ), "foo.<ref>baz</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "foo.\n<ref name=\"bar\">baz</ref>" ), "foo.<ref name=\"bar\">baz</ref>" );

        // Remove spaces or newlines before closing ref tags
        $this->assertEquals( StandardWikitext::fixReferences( "foo.<ref>bar </ref>" ), "foo.<ref>bar</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "foo.<ref name=\"bar\">baz\n</ref>" ), "foo.<ref name=\"bar\">baz</ref>" );

        // Move references after punctuation
        $this->assertEquals( StandardWikitext::fixReferences( "foo<ref name=\"bar\">baz</ref>." ), "foo.<ref name=\"bar\">baz</ref>" );
        $this->assertEquals( StandardWikitext::fixReferences( "foo<ref name=\"bar\" />;" ), "foo;<ref name=\"bar\" />" );

    }
}