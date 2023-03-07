<?php

/**
 * @group Templates
 */
class TemplatesTest extends MediaWikiUnitTestCase {

    public function testFixInlineTemplate(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixTemplate( "{{foo}}" ), "{{foo}}" );
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo}}" ), "{{Foo}}" );
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo|a=b}}" ), "{{Foo|a=b}}" );
        $this->assertEquals( StandardWikitext::fixTemplate( "{{foo|a|b=c|d}}" ), "{{foo|a|b=c|d}}" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo | a = b}}" ), "{{Foo|a=b}}" );
        $this->assertEquals( StandardWikitext::fixTemplate( "{{ Foo | a = b }}" ), "{{Foo|a=b}}" );
    }

    public function testFixBlockTemplate(): void {

        // No changes
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo\n| a = b\n}}" ), "{{Foo\n| a = b\n}}" );

        // Capitalization
        $this->assertEquals( StandardWikitext::fixTemplate( "{{foo\n| a = b\n}}" ), "{{Foo\n| a = b\n}}" );

        // Spacing
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo\n|a=b\n}}" ), "{{Foo\n| a = b\n}}" );
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo\n|a=b\n|c\n}}" ), "{{Foo\n| a = b\n| c\n}}" );
        $this->assertEquals( StandardWikitext::fixTemplate( "{{Foo\n|a=b\n|c=d\n}}" ), "{{Foo\n| a = b\n| c = d\n}}" );
    }
}