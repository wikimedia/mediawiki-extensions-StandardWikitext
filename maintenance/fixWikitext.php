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
		$services = MediaWikiServices::getInstance();
		$dbr = $services->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$pageIds = $dbr->selectFieldValues( 'page', 'page_id', [
			'page_content_model' => CONTENT_MODEL_WIKITEXT,
			'page_namespace' => $wgStandardWikitextNamespaces
		] );
		$wikiPageFactory = $services->getWikiPageFactory();

		foreach ( $pageIds as $pageId ) {
			// Get the working title
			$title = Title::newFromID( $pageId );
			$this->output( $title->getFullText() );

			// Get the wikitext
			$wikiPage = $wikiPageFactory->newFromTitle( $title );
			$content = $wikiPage->getContent();
			$wikitext = $content instanceof TextContent ? $content->getText() : '';

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
