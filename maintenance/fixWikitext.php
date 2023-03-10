<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class fixWikitext extends Maintenance {

	public function execute() {
		global $wgStandardWikitextNamespaces;

		// Get pages to standardise
		$dbw = wfGetDB( DB_PRIMARY );
		$results = $dbw->select( 'page', 'page_id', [
			'page_content_model' => CONTENT_MODEL_WIKITEXT,
			'page_namespace' => $wgStandardWikitextNamespaces
		] );
		foreach ( $results as $result ) {

			// Get working title
			$id = $result->page_id;
			$Title = Title::newFromID( $id );
			$title = $Title->getFullText();
			$this->output( $title );

			// Get wikitext
			$Page = WikiPage::factory( $Title );
			$Content = $Page->getContent();
			$wikitext = ContentHandler::getContentText( $Content );

            // Standardize wikitext
            $fixed = StandardWikitext::fixWikitext( $wikitext );
			if ( $fixed === $wikitext ) {
				$this->output( ' .. ok' . PHP_EOL );
				continue;
			}

			// Save wikitext
			$Content = ContentHandler::makeContent( $wikitext, $Title );
			$User = User::newSystemUser( 'Wikitext standardization bot' );
			$Updater = $Page->newPageUpdater( $User );
			$Updater->setContent( 'main', $Content );
			$Updater->saveRevision( CommentStoreComment::newUnsavedComment( 'Standardize wikitext' ), EDIT_SUPPRESS_RC );
			$this->output( ' .. fixed' . PHP_EOL );
		}
	}
}

$maintClass = fixWikitext::class;
require_once RUN_MAINTENANCE_IF_MAIN;