<?php

use MediaWiki\MediaWikiServices;

class StandardWikitext {

	public static function onParserPreSaveTransformComplete( Parser $parser, string &$wikitext ) {
		global $wgStandardWikitextNamespaces, $wgStandardWikitextModules;

		$title = $parser->getTitle();
		$namespace = $title->getNamespace();
		if ( !in_array( $namespace, $wgStandardWikitextNamespaces ) ) {
			return;
		}

		if ( in_array( 'templates', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixTemplates( $wikitext );
		}

		if ( in_array( 'tables', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixTables( $wikitext );
		}

		if ( in_array( 'links', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixLinks( $wikitext );
		}

		if ( in_array( 'references', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixReferences( $wikitext );
		}

		if ( in_array( 'lists', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixLists( $wikitext );
		}

		if ( in_array( 'sections', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixSections( $wikitext );
		}

		if ( in_array( 'spacing', $wgStandardWikitextModules ) ) {
			$wikitext = self::fixSpacing( $wikitext );
		}
	}

	public static function fixTemplates( $wikitext ) {
		$templates = self::getElements( '{{', '}}', $wikitext );
		foreach ( $templates as $template ) {

			// Store original wikitext to replace it later
			$original = $template;

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

				// Fix spacing of anonymous parameters
				$params = preg_replace( "/ *\| */", "| ", $params );

				// Fix spacing of named parameters (careful with = signs in URLs)
				$params = preg_replace( "/^ ?\|([^=]+)=/m", "| $1 = ", $params );

			// Inline format
			} else {

				// Fix spacing around parameters
				$params = trim( $params );

				// Fix spacing of anonymous parameters
				$params = preg_replace( "/ *\| */", "|", $params );

				// Fix spacing of named parameters
				$params = preg_replace( "/ *= */", "=", $params );

				// Remove empty parameters
				$params = preg_replace( "/\|[^=]+=($|\|)/", "$1", $params );
			}

			// Remove extra spaces
			$params = preg_replace( "/  +/", " ", $params );

			// Restore outer braces
			$template = "{{" . $title . $params . "}}";

			// Give standalone templates some room
			$position = strpos( $wikitext, $original );
			$previous = $position === 0 ? $position : $position - 1;
			if ( substr( $wikitext, $previous, 1 ) === "\n" ) {
				$template = "\n\n" . $template . "\n\n";
			}

			// Replace original wikitext for fixed one
			$wikitext = str_replace( $original, $template, $wikitext );
		}
		return $wikitext;
	}

	public static function fixTables( $wikitext ) {
		$tables = self::getElements( '{|', '|}', $wikitext );
		foreach ( $tables as $table ) {

			// Store original wikitext to replace it later
			$original = $table;

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

			// Give standalone tables some room
			$position = strpos( $wikitext, $original );
			$previous = $position === 0 ? $position : $position - 1;
			if ( substr( $wikitext, $previous, 1 ) === "\n" ) {
				$table = "\n\n" . $table . "\n\n";
			}

			// Replace original wikitext for fixed one
			$wikitext = str_replace( $original, $table, $wikitext );
		}
		return $wikitext;
	}

	public static function fixLinks( $wikitext ) {
		$links = self::getElements( '[[', ']]', $wikitext );
		foreach ( $links as $link ) {

			// Store original wikitext to replace it later
			$original = $link;

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
						return self::fixLinks( $link );
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

			// Give standalone file links some room
			if ( $namespace === 6 ) {
				$position = strpos( $wikitext, $original );
				$previous = $position === 0 ? $position : $position - 1;
				if ( substr( $wikitext, $previous, 1 ) === "\n" ) {
					$link = "\n\n" . $link . "\n\n";
				}
			}

			// Replace original wikitext for fixed one
			$wikitext = str_replace( $original, $link, $wikitext );
		}
		return $wikitext;
	}

	public static function fixReferences( $wikitext ) {

		// Fix spacing
		$wikitext = preg_replace( "/<ref +name += +/", "<ref name=", $wikitext );
		$wikitext = preg_replace( "/<ref([^>]+[^ ]+)\/>/", "<ref$1 />", $wikitext );

		// Fix quotes
		$wikitext = preg_replace( "/<ref name=' *([^']+) *'/", "<ref name=\"$1\"", $wikitext );
		$wikitext = preg_replace( "/<ref name=([^\" \/>]+)/", "<ref name=\"$1\"", $wikitext );

		// Remove spaces or newlines after opening ref tag
		$wikitext = preg_replace( "/<ref([^>\/]*)>[ \n]+/", "<ref$1>", $wikitext );

		// Fix empty references with name
		$wikitext = preg_replace( "/<ref name=\"([^\"]+)\"><\/ref>/", "<ref name=\"$1\" />", $wikitext );

		// Remove empty references
		$wikitext = preg_replace( "/<ref><\/ref>/", "", $wikitext );

		// Remove spaces or newlines before references
		$wikitext = preg_replace( "/[ \n]+<\/?ref/", "<ref", $wikitext );

		// Move references after punctuation
		$wikitext = preg_replace( "/<ref([^<]+)<\/ref>([.,;:])/", "$2<ref$1</ref>", $wikitext );
		$wikitext = preg_replace( "/<ref([^>]+)\/>([.,;:])/", "$2<ref$1/>", $wikitext );

		return $wikitext;
	}

	public static function fixLists( $wikitext ) {

		// Fix unordered lists with wrong items
		$wikitext = preg_replace( "/^-/m", "*", $wikitext );

		// Fix ordered lists with wrong items
		$wikitext = preg_replace( "/^\d\./m", "#", $wikitext );

		// Remove extra spaces between list items
		$wikitext = preg_replace( "/^([*#]) ?([*#])? ?([*#])?/m", "$1$2$3", $wikitext );

		// Remove empty list items
		$wikitext = preg_replace( "/^([*#]+)$/m", "", $wikitext );

		// Add initial space to list items
		$wikitext = preg_replace( "/^([*#]+)([^ ]?)/m", "$1 $2", $wikitext );

		// Remove newlines between lists
		$wikitext = preg_replace( "/^\n+([*#]+)/m", "$1", $wikitext );

		// Give lists some room
		$wikitext = preg_replace( "/^([^*#][^\n]+)\n([*#])/m", "$1\n\n$2", $wikitext );
		$wikitext = preg_replace( "/^([*#][^\n]+)\n([^*#])/m", "$1\n\n$2", $wikitext );

		return $wikitext;
	}

	public static function fixSections( $wikitext ) {

		// Fix spacing
		$wikitext = preg_replace( "/^(=+) *(.+?) *(=+) *$/m", "\n\n$1 $2 $3\n\n", $wikitext );
		$wikitext = preg_replace( "/\n\n\n+/m", "\n\n", $wikitext );
		$wikitext = trim( $wikitext );

		// Remove bold
		$wikitext = preg_replace( "/^(=+) '''(.+?)''' (=+)$/m", "$1 $2 $3", $wikitext );

		// Remove trailing colon
		$wikitext = preg_replace( "/^(=+) (.+?): (=+)$/m", "$1 $2 $3", $wikitext );

		return $wikitext;
	}

	public static function fixSpacing( $wikitext ) {

		// Fix tabs in code blocks
		$wikitext = preg_replace( "/^  {8}/m", " \t\t\t\t", $wikitext );
		$wikitext = preg_replace( "/^  {6}/m", " \t\t\t", $wikitext );
		$wikitext = preg_replace( "/^  {4}/m", " \t\t", $wikitext );
		$wikitext = preg_replace( "/^  {2}/m", " \t", $wikitext );

		// Fix remaining tabs (for example in <pre> blocks)
		// @todo Make more robust
		$wikitext = preg_replace( "/ {4}/", "\t", $wikitext );

		// Remove excessive spaces
		$wikitext = preg_replace( "/  +/", " ", $wikitext );

		// Remove trailing spaces
		$wikitext = preg_replace( "/^ $/m", "@@@", $wikitext ); // Exception for code blocks
		$wikitext = preg_replace( "/ +$/m", "", $wikitext );
		$wikitext = preg_replace( "/^@@@$/m", " ", $wikitext );

		// Fix line breaks
		$wikitext = preg_replace( "/ *<br ?\/?> */", "<br>", $wikitext );

		// Remove excessive newlines
		$wikitext = preg_replace( "/^\n\n+/m", "\n", $wikitext );

		// Remove leading newlines
		$wikitext = preg_replace( "/^\n+/", "", $wikitext );

		// Remove trailing newlines
		$wikitext = preg_replace( "/\n+$/", "", $wikitext );

		return $wikitext;
	}

	/**
	 * Helper method to get elements that may have similar elements nested inside
	 */
	public static function getElements( $prefix, $suffix, $wikitext ) {
		$elements = [];
		$start = strpos( $wikitext, $prefix );
		while ( $start !== false ) {
			$depth = 0;
			for ( $position = $start; $position < strlen( $wikitext ); $position++ ) {
				if ( substr( $wikitext, $position, strlen( $prefix ) ) === $prefix ) {
					$position++;
					$depth++;
				}
				if ( substr( $wikitext, $position, strlen( $suffix ) ) === $suffix ) {
					$position++;
					$depth--;
				}
				if ( !$depth ) {
					break;
				}
			}
			$end = $position - $start + 1;
			$element = substr( $wikitext, $start, $end );
			$elements[] = $element;
			$start = strpos( $wikitext, $prefix, $position );
		}
		return $elements;
	}
}