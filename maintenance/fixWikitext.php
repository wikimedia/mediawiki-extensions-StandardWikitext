<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class fixWikitext extends Maintenance {

	public function execute() {
		global $wgStandardWikitextNamespaces;

		// Get the pages to standardise
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnection( DB_REPLICA );
		$results = $dbr->select( 'page', 'page_id', [
			'page_content_model' => CONTENT_MODEL_WIKITEXT,
			'page_namespace' => $wgStandardWikitextNamespaces
		] );
		foreach ( $results as $result ) {

			// Get the working title
			$id = $result->page_id;
			$title = Title::newFromID( $id );
			$text = $title->getFullText();
			$this->output( $text );

			// Get the wikitext
			$wikiPage = WikiPage::factory( $title );
			$content = $wikiPage->getContent();
			$wikitext = ContentHandler::getContentText( $content );

			// Check if fixing the wikitext changes anything
			$fixed = StandardWikitext::fixWikitext( $wikitext );
			if ( $fixed === $wikitext ) {
				$this->output( ' .. ok' . PHP_EOL );
				continue;
			}

			// Save the fixed wikitext
			StandardWikitext::saveWikitext( $fixed, $wikiPage );
			$this->output( ' .. fix' . PHP_EOL );
		}
	}
}

$maintClass = fixWikitext::class;
require_once RUN_MAINTENANCE_IF_MAIN;
