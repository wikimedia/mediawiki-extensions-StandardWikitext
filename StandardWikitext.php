<?php

use MediaWiki\MediaWikiServices;

class StandardWikitext {

	public static function onParserPreSaveTransformComplete( Parser $parser, string &$text ) {
		global $wgStandardWikitextNamespaces, $wgStandardWikitextRules;

		$title = $parser->getTitle();
		$namespace = $title->getNamespace();
		if ( !in_array( $namespace, $wgStandardWikitextNamespaces ) ) {
			return;
		}

		if ( $wgStandardWikitextRules['templates'] ) {
			$text = self::fixTemplates( $text );
		}

		if ( $wgStandardWikitextRules['tables'] ) {
			$text = self::fixTables( $text );
		}

		if ( $wgStandardWikitextRules['links'] ) {
			$text = self::fixLinks( $text );
		}

		if ( $wgStandardWikitextRules['references'] ) {
			$text = self::fixReferences( $text );
		}

		if ( $wgStandardWikitextRules['lists'] ) {
			$text = self::fixLists( $text );
		}

		if ( $wgStandardWikitextRules['sections'] ) {
			$text = self::fixSections( $text );
		}

		if ( $wgStandardWikitextRules['spacing'] ) {
			$text = self::fixSpacing( $text );
		}

		if ( $wgStandardWikitextRules['punctuation'] ) {
			$text = self::fixPunctuation( $text );
		}
	}

	public static function fixTemplates( $text ) {
		$templates = self::getElements( '{{', '}}', $text );
		foreach ( $templates as $template ) {
			$fixed = self::fixTemplate( $template );

			// Give standalone templates some room
			$position = strpos( $text, $template );
			$previous = $position === 0 ? $position : $position - 1;
			if ( substr( $text, $previous, 1 ) === "\n" ) {
				$fixed = "\n\n" . $fixed . "\n\n";
			}

			$text = str_replace( $template, $fixed, $text );
		}
		return $text;
	}

	public static function fixTables( $text ) {
		$tables = self::getElements( '{|', '|}', $text );
		foreach ( $tables as $table ) {
			$fixed = self::fixTable( $table );

			// Give standalone tables some room
			$position = strpos( $text, $table );
			$previous = $position === 0 ? $position : $position - 1;
			if ( substr( $text, $previous, 1 ) === "\n" ) {
				$fixed = "\n\n" . $fixed . "\n\n";
			}

			$text = str_replace( $table, $fixed, $text );
		}
		return $text;
	}

	public static function fixLinks( $text ) {
		$links = self::getElements( '[[', ']]', $text );
		foreach ( $links as $link ) {
			$fixed = self::fixLink( $link );

			// Give standalone links some room
			$position = strpos( $text, $link );
			$previous = $position === 0 ? $position : $position - 1;
			if ( substr( $text, $previous, 1 ) === "\n" ) {
				$fixed = "\n\n" . $fixed . "\n\n";
			}

			$text = str_replace( $link, $fixed, $text );
		}
		return $text;
	}

	public static function fixTemplate( $template ) {

		// Remove outer braces
		$template = preg_replace( "/^\{\{/", "", $template );
		$template = preg_replace( "/\}\}$/", "", $template );

		$pipe = strpos( $template, '|' );
		$title = substr( $template, 0, $pipe );
		$params = substr( $template, $pipe );

		// Restore leading newline to params if there was one
		if ( $params && substr( $title, -1 ) === "\n" ) {
			$params = "\n$params";
		}

		// {{ Foo }} → {{Foo}}
		$title = trim( $title );

		// Block format
		if ( preg_match( "/\n *\|/", $template ) ) {

			// Force capitalization
			$title = ucfirst( $title );

			// Fix spacing
			$params = preg_replace( "/ *\| */", "| ", $params ); // Anonymous parameters
			$params = preg_replace( "/^ ?\|([^=]+)=/m", "| $1 = ", $params ); // Named parameters (careful with = signs in URLs)

		// Inline format
		} else {
			$params = trim( $params );
			$params = preg_replace( "/ *\| */", "|", $params ); // Anonymous parameters
			$params = preg_replace( "/ *= */", "=", $params ); // Named parameters
			$params = preg_replace( "/\|[^=]+=($|\|)/", "$1", $params ); // Remove empty parameters
		}

		// Remove extra spaces
		$params = preg_replace( "/  +/", " ", $params );

		// Restore outer braces
		$template = "{{" . $title . $params . "}}";

		return $template;
	}

	public static function fixTable( $table ) {

		$table = trim( $table );

		// Remove multiple newlines
		$table = preg_replace( "/\n\n+/", "\n", $table );

		// Flatten the table
		$table = preg_replace( "/\!\!/", "\n!", $table );
		$table = preg_replace( "/\|\|/", "\n|", $table );

		// Remove trailing spaces
		$table = preg_replace( "/ +\n/", "\n", $table );

		// Add missing spaces
		$table = preg_replace( "/\n([|!][-+}]?)([^ +}\n-])/", "\n$1 $2", $table );

		// Remove empty captions
		$table = preg_replace( "/\n\|\+\n/", "\n", $table );

        // Remove newrow after caption
		$table = preg_replace( "/(\n\|\+[^\n]+)\n\|\-/", "$1", $table );

		// Fix pseudo-headers
		$table = preg_replace( "/\n[|!]\+? *'''([^\n]+)'''/", "\n! $1", $table );

		// Remove leading newrow
		$table = preg_replace( "/^(\{\|[^\n]*\n)\|\-\n/", "$1", $table );

		// Remove trailing newrow
		$table = preg_replace( "/\|\-\n\|\}$/", "|}", $table );

		return $table;
	}

	public static function fixLink( $link ) {

		// Remove the outer braces
		$link = preg_replace( "/^\[\[/", '', $link );
		$link = preg_replace( "/\]\]$/", '', $link );

		$parts = explode( '|', $link );

		$title = $parts[0];

		$params = array_slice( $parts, 1 );

		// [[ foo ]] → [[foo]]
		$title = trim( $title );

		// [[test_link]] → [[test link]]
		$title = str_replace( '_', ' ', $title );

		// [[Fo%C3%B3]] → [[Foó]]
		$title = urldecode( $title );

		$Title = Title::newFromText( $title );

		$namespace = $Title->getNamespace();

		// File link: [[File:Foo.jpg|thumb|Caption with [[sub_link]].]]
		if ( $namespace === 6 ) {

			$link = $title;
			foreach ( $params as $param ) {

				// [[File:Foo.jpg| thumb ]] → [[File:Foo.jpg|thumb]]
				$param = trim( $param );

				// [[File:Foo.jpg|thumb|Caption with [[sub_link]].]] → [[File:Foo.jpg|thumb|Caption with [[sub link]].]]
				$param = preg_replace_callback( "/\[\[[^\]]+\]\]/", function ( $matches ) {
					$link = $matches[0];
					return self::fixLink( $link );
				}, $param );

				$link .= '|' . $param;
			}

			// Remove redundant parameters
			$link = str_replace( 'thumb|right', 'thumb', $link );
			$link = str_replace( 'right|thumb', 'thumb', $link );
			$link = str_replace( '|alt=|', '|', $link );

		// Link with alternative text: [[Title|text]]
		} else if ( $params ) {

			$text = $params[0];

			// [[Foo| bar ]] → [[Foo|bar]]
			$text = trim( $text );

			// [[foo|bar]] → [[Foo|bar]]
			$title = ucfirst( $title );

			// [[Foo|foo]] → [[foo]]
			if ( lcfirst( $title ) === $text ) {
				$link = $text;

			// Else just build the link
			} else {
				$link = "$title|$text";
			}

		// Plain link: [[link]]
		} else {
			$link = $title;
		}

		// Restore outer braces
		$link = "[[$link]]";

		return $link;
	}

	public static function fixReferences( $text ) {

		// Fix spacing
		$text = preg_replace( "/<ref +name += +/", "<ref name=", $text );
		$text = preg_replace( "/<ref([^>]+[^ ]+)\/>/", "<ref$1 />", $text );

		// Fix quotes
		$text = preg_replace( "/<ref name=' *([^']+) *'/", "<ref name=\"$1\"", $text );
		$text = preg_replace( "/<ref name=([^\" \/>]+)/", "<ref name=\"$1\"", $text );

		// Remove spaces or newlines after opening ref tag
		$text = preg_replace( "/<ref([^>\/]*)>[ \n]+/", "<ref$1>", $text );

		// Fix empty references with name
		$text = preg_replace( "/<ref name=\"([^\"]+)\"><\/ref>/", "<ref name=\"$1\" />", $text );

		// Remove empty references
		$text = preg_replace( "/<ref><\/ref>/", "", $text );

		// Remove spaces or newlines before references
		$text = preg_replace( "/[ \n]+<\/?ref/", "<ref", $text );

		// Move references after punctuation
		$text = preg_replace( "/<ref([^<]+)<\/ref>([.,;:])/", "$2<ref$1</ref>", $text );
		$text = preg_replace( "/<ref([^>]+)\/>([.,;:])/", "$2<ref$1/>", $text );

		return $text;
	}

	public static function fixLists( $text ) {

		// List items with wrong characters
		$text = preg_replace( "/^-/m", "*", $text );

		// Number items with wrong characters
		$text = preg_replace( "/^\d\./m", "#", $text );

		// Empty list items
		$text = preg_replace( "/^([*#]+) *\n/m", "", $text );

		// List items with no initial space
		$text = preg_replace( "/^([*#]+)([^ ])/m", "$1 $2", $text );

		// List items with extra newlines
		$text = preg_replace( "/^\n+([*#]+)/m", "$1", $text );

		// Lists with no initial extra newline
		$text = preg_replace( "/^([^*#][^\n]+)\n([*#])/m", "$1\n\n$2", $text );

		// Lists with no trailing extra newline
		$text = preg_replace( "/^([*#][^\n]+)\n([^*#])/m", "$1\n\n$2", $text );

		return $text;
	}

	public static function fixSections( $text ) {

		// Fix spacing
		$text = preg_replace( "/^(=+) *(.+?) *(=+) *$/m", "\n\n$1 $2 $3\n\n", $text );
		$text = preg_replace( "/\n\n\n+/m", "\n\n", $text );
		$text = trim( $text );

		// Remove bold
		$text = preg_replace( "/^(=+) '''(.+?)''' (=+)$/m", "$1 $2 $3", $text );

		// Remove trailing colon
		$text = preg_replace( "/^(=+) (.+?): (=+)$/m", "$1 $2 $3", $text );

		return $text;
	}

	public static function fixSpacing( $text ) {

		// Fix tabs in code blocks
		$text = preg_replace( "/^  {8}/m", " \t\t\t\t", $text );
		$text = preg_replace( "/^  {6}/m", " \t\t\t", $text );
		$text = preg_replace( "/^  {4}/m", " \t\t", $text );
		$text = preg_replace( "/^  {2}/m", " \t", $text );

		// Fix remaining tabs (for example in <pre> blocks)
		// @todo Make more robust
		$text = preg_replace( "/ {4}/", "\t", $text );

		// Remove excessive spaces
		$text = preg_replace( "/  +/", " ", $text );

		// Remove trailing spaces
		$text = preg_replace( "/^ $/m", "@@@", $text ); // Exception for code blocks
		$text = preg_replace( "/ +$/m", "", $text );
		$text = preg_replace( "/^@@@$/m", " ", $text );

		// Fix line breaks
		$text = preg_replace( "/ *<br ?\/?> */", "<br>", $text );

		// Remove excessive newlines
		$text = preg_replace( "/^\n\n+/m", "\n", $text );

		// Remove leading newlines
		$text = preg_replace( "/^\n+/", "", $text );

		// Remove trailing newlines
		$text = preg_replace( "/\n+$/", "", $text );

		return $text;
	}

	public static function fixPunctuation( $text ) {

		// Punctuation marks
		$text = preg_replace( "/ \./", ".", $text );
		$text = preg_replace( "/ ,/", ",", $text );
		$text = preg_replace( "/ :/", ":", $text );

		// Parenthesis
		$text = preg_replace( "/ \)/", ")", $text );
		$text = preg_replace( "/\( /", "(", $text );

		// Quotes
		$text = preg_replace( "/« /", "«", $text );
		$text = preg_replace( "/ »/", "»", $text );
		//$text = preg_replace( "/[’‘]/", "'", $text );
		//$text = preg_replace( "/[“”]/", '"', $text );

		return $text;
	}

	/**
	 * Helper method to get elements that may have other similar elements inside
	 */
	public static function getElements( $prefix, $suffix, $text ) {
		$elements = [];
		$start = strpos( $text, $prefix );
		while ( $start !== false ) {
			$depth = 0;
			for ( $position = $start; $position < strlen( $text ); $position++ ) {
				if ( substr( $text, $position, strlen( $prefix ) ) === $prefix ) {
					$position++;
					$depth++;
				}
				if ( substr( $text, $position, strlen( $suffix ) ) === $suffix ) {
					$position++;
					$depth--;
				}
				if ( !$depth ) {
					break;
				}
			}
			$end = $position - $start + 1;
			$element = substr( $text, $start, $end );
			$elements[] = $element;
			$start = strpos( $text, $prefix, $position );
		}
		return $elements;
	}
}