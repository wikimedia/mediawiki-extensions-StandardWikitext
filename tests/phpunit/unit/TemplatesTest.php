<?php

/**
 * @group Templates
 * @covers StandardWikitext::fixTemplates
 */
class TemplatesTest extends MediaWikiUnitTestCase {

	public function testFixInlineTemplate(): void {
		// No changes
		$this->assertEquals( "{{foo}}", StandardWikitext::fixTemplates( "{{foo}}" ) );
		$this->assertEquals( "{{Foo}}", StandardWikitext::fixTemplates( "{{Foo}}" ) );
		$this->assertEquals( "{{Foo|a=b}}", StandardWikitext::fixTemplates( "{{Foo|a=b}}" ) );
		$this->assertEquals( "{{foo|a|b=c|d}}", StandardWikitext::fixTemplates( "{{foo|a|b=c|d}}" ) );

		// Spacing
		$this->assertEquals( "{{Foo|a=b}}", StandardWikitext::fixTemplates( "{{Foo | a = b}}" ) );
		$this->assertEquals( "{{Foo|a=b}}", StandardWikitext::fixTemplates( "{{ Foo | a = b }}" ) );
	}

	public function testFixBlockTemplate(): void {
		// No changes
		$this->assertEquals( "{{Foo\n| a = b\n}}", StandardWikitext::fixTemplates( "{{Foo\n| a = b\n}}" ) );

		// Capitalization
		$this->assertEquals( "{{Foo\n| a = b\n}}", StandardWikitext::fixTemplates( "{{foo\n| a = b\n}}" ) );

		// Spacing
		$this->assertEquals( "{{Foo\n| a = b\n}}", StandardWikitext::fixTemplates( "{{Foo\n|a=b\n}}" ) );
		$this->assertEquals( "{{Foo\n| a = b\n| c\n}}", StandardWikitext::fixTemplates( "{{Foo\n|a=b\n|c\n}}" ) );
		$this->assertEquals( "{{Foo\n| a = b\n| c = d\n}}", StandardWikitext::fixTemplates( "{{Foo\n|a=b\n|c=d\n}}" ) );
	}
}
