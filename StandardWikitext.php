<?php

class StandardWikitext {

	public static function onPageSaveComplete( WikiPage $wikiPage, MediaWiki\User\UserIdentity $user, string $summary, int $flags, MediaWiki\Revision\RevisionRecord $revisionRecord, MediaWiki\Storage\EditResult $editResult ) {
		global $wgStandardWikitextAccount, $wgStandardWikitextNamespaces;

		// Prevent infinite loops
		if ( $user->getName() === $wgStandardWikitextAccount ) {
			return;
		}

		// If a user tries to revert an edit done by this script, don't insist
		if ( $editResult->isRevert() ) {
			return;
		}

		// Check if the wikitext should be fixed
		$title = $wikiPage->getTitle();
		$contentModel = $title->getContentModel();
		if ( $contentModel !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}
		$namespace = $title->getNamespace();
		if ( !in_array( $namespace, $wgStandardWikitextNamespaces ) ) {
			return;
		}

		// Check if fixing the wikitext changes anything
		$content = $wikiPage->getContent();
		$wikitext = ContentHandler::getContentText( $content );
		$fixed = self::fixWikitext( $wikitext );
		if ( $fixed === $wikitext ) {
			return;
		}

		// Save the fixed wikitext
		self::saveWikitext( $fixed, $wikiPage );
	}

	public static function saveWikitext( $wikitext, $wikiPage ) {
		global $wgStandardWikitextAccount;
		$title = $wikiPage->getTitle();
		$content = ContentHandler::makeContent( $wikitext, $title );
		$user = User::newSystemUser( $wgStandardWikitextAccount );
		$updater = $wikiPage->newPageUpdater( $user );
		$updater->setContent( 'main', $content );
		$summary = wfMessage( 'standardwikitext-summary' )->inContentLanguage()->text();
		$comment = CommentStoreComment::newUnsavedComment( $summary );
		$updater->saveRevision( $comment, EDIT_SUPPRESS_RC | EDIT_FORCE_BOT | EDIT_MINOR | EDIT_INTERNAL );
	}

	public static function fixWikitext( $wikitext ) {
		$wikitext = self::fixLists( $wikitext );
		$wikitext = self::fixTemplates( $wikitext );
		$wikitext = self::fixTables( $wikitext );
		$wikitext = self::fixLinks( $wikitext );
		$wikitext = self::fixReferences( $wikitext );
		$wikitext = self::fixSections( $wikitext );
		$wikitext = self::fixCategories( $wikitext );
		$wikitext = self::fixSpacing( $wikitext );
		return $wikitext;
	}

	public static function fixTemplates( $wikitext ) {
		$templates = self::getElements( '{{', '}}', $wikitext );
		foreach ( $templates as $template ) {

			// Store original wikitext to be able to replace it later
			$original = $template;

			// Remove outer braces
			$template = preg_replace( "/^\{\{/", "", $template );
			$template = preg_replace( "/\}\}$/", "", $template );

			// Replace all nested templates, tables and links to prevent havoc
			$subelements = self::getElements( '{{', '}}', $template );
			$subelements += self::getElements( '{|', '|}', $template );
			$subelements += self::getElements( '[[', ']]', $template );
			foreach ( $subelements as $key => $subelement ) {
				$placeholder = '@@@' . $key . '@@@';
				$template = str_replace( $subelement, $placeholder, $template );
			}

			// Get the template parts
			$params = explode( '|', $template );
			$params = array_map( 'trim', $params );
			$title = array_shift( $params );

			// Inline format
			if ( strpos( $template, "\n|" ) === false ) {

				// Rebuild the template
				$template = $title;
				foreach ( $params as $param ) {
					$parts = explode( '=', $param, 2 );
					if ( count( $parts ) === 2 ) {
						$key = trim( $parts[0] );
						$value = trim( $parts[1] );
						if ( $value ) {
							$template .= "|$key=$value";
						}
					} else {
						$value = trim( $parts[0] );
						$template .= "|$value";
					}
				}

			// Block format
			} else {

				// Force capitalization
				$title = ucfirst( $title );

				// Rebuild the template
				$template = $title;
				foreach ( $params as $param ) {
					$parts = explode( '=', $param, 2 );
					if ( count( $parts ) === 2 ) {
						$key = trim( $parts[0] );
						$value = trim( $parts[1] );
						$value = preg_replace( "/^([*#])/", "\n$1", $value ); // Restore newlines before lists
						if ( $value ) {
							$template .= "\n| $key = $value";
						}
					} else {
						$value = trim( $parts[0] );
						$template .= "\n| $value";
					}
				}
				$template .= "\n";
			}

			// Restore replaced subelements
			foreach ( $subelements as $key => $subelement ) {
				$placeholder = '@@@' . $key . '@@@';
				$template = str_replace( $placeholder, $subelement, $template );
			}

			// Restore outer braces
			$template = '{{' . $template . '}}';

			// Replace original wikitext for fixed one
			$wikitext = str_replace( $original, $template, $wikitext );
		}
		return $wikitext;
	}

	public static function fixTables( $wikitext ) {
		$tables = self::getElements( "{|", "\n|}", $wikitext );
		foreach ( $tables as $table ) {

			// Store original wikitext to replace it later
			$original = $table;

			// Flatten the table
			$table = preg_replace( "/\!\!/", "\n!", $table );
			$table = preg_replace( "/\|\|/", "\n|", $table );

			// Add leading spaces
			$table = preg_replace( "/^!([^ \n])/m", "! $1", $table ); // Headers
			$table = preg_replace( "/^\|\+([^ \n])/m", "|+ $1", $table ); // Captions
			$table = preg_replace( "/^\|-([^ \n])/m", "|- $1", $table ); // Newrows
			$table = preg_replace( "/^\|([^ \n}+-])/m", "| $1", $table ); // Cells

			// Remove empty captions
			$table = preg_replace( "/^\|\+ *\n/m", "", $table );

			// Remove newrow after caption
			$table = preg_replace( "/^(\|\+[^\n]+)\n\|\-/m", "$1", $table );

			// Remove bold from captions
			$table = preg_replace( "/^\|\+ *'''(.*)'''/m", "|+ $1", $table );

			// Remove bold from headers
			$table = preg_replace( "/^! *'''(.*)'''/m", "! $1", $table );

			// Fix pseudo-headers
			$table = preg_replace( "/^\| *'''(.*)'''/m", "! $1", $table );

			// Remove leading newrow
			$table = preg_replace( "/^(\{\|[^\n]*\n)\|\-\n/", "$1", $table );

			// Remove trailing newrow
			$table = preg_replace( "/\n\|\-\n\|\}$/", "\n|}", $table );

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

			// Fix fake external link
			// @todo Make more robust
			if ( preg_match( '#^https?://#', $title ) ) {
				$link = "[$link]";
				$wikitext = str_replace( $original, $link, $wikitext );
				continue;
			}

			$params = array_slice( $parts, 1 );

			// [[ foo ]] → [[foo]]
			$title = trim( $title );

			// [[Fo%C3%B3]] → [[Foó]]
			$title = rawurldecode( $title );

			// [[test_link]] → [[test link]]
			$title = str_replace( '_', ' ', $title );

			$Title = Title::newFromText( $title );
			if ( !$Title ) {
				continue;
			}
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
			} elseif ( $params ) {

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

		// Remove spaces or newlines after opening ref tags
		$wikitext = preg_replace( "/<ref([^>\/]*)>[ \n]+/", "<ref$1>", $wikitext );

		// Fix empty references with name
		$wikitext = preg_replace( "/<ref name=\"([^\"]+)\"><\/ref>/", "<ref name=\"$1\" />", $wikitext );

		// Remove empty references
		$wikitext = preg_replace( "/<ref><\/ref>/", "", $wikitext );

		// Remove spaces or newlines around opening ref tags
		$wikitext = preg_replace( "/[ \n]*<ref([^>\/]*)>[ \n]*/", "<ref$1>", $wikitext );

		// Remove spaces or newlines before closing ref tags
		$wikitext = preg_replace( "/[ \n]+<\/ref>/", "</ref>", $wikitext );

		// Move references after punctuation
		$wikitext = preg_replace( "/<ref([^<]+)<\/ref>([.,;:])/", "$2<ref$1</ref>", $wikitext );
		$wikitext = preg_replace( "/<ref([^>]+)\/>([.,;:])/", "$2<ref$1/>", $wikitext );

		return $wikitext;
	}

	public static function fixLists( $wikitext ) {
		// Don't confuse a redirect with a numbered list
		$wikitext = preg_replace( "/^#(.+ ?\[\[.+\]\])/", "@@@$1", $wikitext );

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

		// Restore redirect
		$wikitext = preg_replace( "/^@@@/", "#", $wikitext );

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

	/**
	 * Move categories to the bottom
	 * and remove duplicate categories
	 * @todo Only works in English
	 */
	public static function fixCategories( $wikitext ) {
		// Don't replace category links inside templates and parser functions
		$templates = self::getElements( '{{', '}}', $wikitext );
		foreach ( $templates as $i => $template ) {
			$wikitext = str_replace( $template, "@@@$i@@@", $wikitext );
		}

		$count = preg_match_all( "/\n*\[\[ ?[Cc]ategory ?: ?([^]]+) ?\]\]/", $wikitext, $matches );
		if ( $count ) {
			foreach ( $matches[0] as $match ) {
				$wikitext = str_replace( $match, '', $wikitext );
			}
			$categories = $matches[1];
			$categories = array_unique( $categories );
			$wikitext .= "\n";
			foreach ( $categories as $category ) {
				$wikitext .= "\n[[Category:$category]]";
			}
		}

		// Restore templates
		foreach ( $templates as $i => $template ) {
			$wikitext = str_replace( "@@@$i@@@", $template, $wikitext );
		}
		return $wikitext;
	}

	public static function fixSpacing( $wikitext ) {
		// Give block templates some room
		$templates = self::getElements( "\n{{", "}}\n", $wikitext );
		foreach ( $templates as $template ) {
			$wikitext = str_replace( $template, "\n$template\n", $wikitext );
		}

		// Give tables some room
		$tables = self::getElements( "\n{|", "\n|}", $wikitext );
		foreach ( $tables as $table ) {
			$wikitext = str_replace( $table, "\n$table\n", $wikitext );
		}

		// Give standalone file links some room
		// @todo i18n
		$links = self::getElements( "\n[[File:", "]]\n", $wikitext );
		foreach ( $links as $link ) {
			$wikitext = str_replace( $link, "\n$link\n", $wikitext );
		}

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
		$wikitext = preg_replace( "/^@@@$/m", " ", $wikitext ); // Restore code block

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
					$position += strlen( $prefix ) - 1;
					$depth++;
				}
				if ( substr( $wikitext, $position, strlen( $suffix ) ) === $suffix ) {
					$position += strlen( $suffix ) - 1;
					$depth--;
				}
				if ( !$depth ) {
					break;
				}
			}
			$end = $position - $start + 1;
			$element = substr( $wikitext, $start, $end );
			$elements[] = $element;
			$start = strpos( $wikitext, $prefix, $start + 1 );
		}
		return $elements;
	}
}
