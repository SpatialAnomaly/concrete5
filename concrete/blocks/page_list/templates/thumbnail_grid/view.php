<?php
defined('C5_EXECUTE') or die("Access Denied.");
$th = Loader::helper('text');
$c = Page::getCurrentPage();
$dh = Core::make('helper/date'); /* @var $dh \Concrete\Core\Localization\Service\Date */
?>

<?php if ($c->isEditMode() && $controller->isBlockEmpty()) { ?>
<div class="ccm-edit-mode-disabled-item"><?php echo t('Empty Page List Thumbnail Grid Block.')?></div>
<?php } else { ?>

<div class="ccm-block-page-list-thumbnail-grid-wrapper">

	<?php if (isset($pageListTitle) && $pageListTitle) { ?>
	<div class="ccm-block-page-list-header">
		<h5><?php echo h($pageListTitle)?></h5>
	</div>
	<?php } ?>
	
	<?php if (isset($rssUrl) && $rssUrl) { ?>
	<div class="ccm-block-page-list-rss">
		<a href="<?php echo $rssUrl ?>" target="_blank" class="ccm-block-page-list-rss-feed">
			<i class="fa fa-rss"></i>
		</a>
	</div>
	<?php } ?>
	
	<div class="ccm-block-page-list-grid-pages row">
				
    <?php 
		foreach ($pages as $page) {	
			$columnClass = 'col-sm-4';
			$buttonClasses = 'btn btn-primary btn-sm';
			$title = $th->entities($page->getCollectionName());
			$url = $nh->getLinkToCollection($page);
			$target = ($page->getCollectionPointerExternalLink() != '' && $page->openCollectionPointerExternalLinkInNewWindow()) ? '_blank' : $page->getAttribute('nav_target');
			$target = empty($target) ? '_self' : $target;
			$thumbnail = false;
			if ($displayThumbnail) {
				$thumbnail = $page->getAttribute('thumbnail');
			}
			$hoverLinkText = $title;
			$description = $page->getCollectionDescription();
			$description = $controller->truncateSummaries ? $th->wordSafeShortText($description, $controller->truncateChars) : $description;
			$description = $th->entities($description);
			if ($useButtonForLink) {
					$hoverLinkText = $buttonLinkText;
			}
			$date = $dh->formatDateTime($page->getCollectionDatePublic(), true);
		?>

		<div class="ccm-block-page-list-page-entry-grid-item <?php echo $columnClass; ?>">

			<?php if (is_object($thumbnail)) { ?>
			<div class="ccm-block-page-list-page-entry-grid-thumbnail">
				<a href="<?php echo $url ?>" target="<?php echo $target ?>">
					<?php
					$img = Core::make('html/image', array($thumbnail));
					$tag = $img->getTag();
					$tag->addClass('img-responsive');
					echo $tag;
					?>
					<div class="ccm-block-page-list-page-entry-grid-thumbnail-hover">
						<div class="ccm-block-page-list-page-entry-grid-thumbnail-title-wrapper">
							<div class="ccm-block-page-list-page-entry-grid-thumbnail-title">
							<i class="ccm-block-page-list-page-entry-grid-thumbnail-icon"></i>
							<?php echo $hoverLinkText; ?>
							</div>
						</div>
					</div>
				</a>
			</div>
			<?php } ?>

			<div class="ccm-block-page-list-title">
				<strong>
					<?php if (isset($useButtonForLink) && $useButtonForLink) { ?>
					<?php echo $title; ?>
					<?php	} else { ?>
						<a href="<?php echo $url ?>" target="<?php echo $target ?>">
							<?php echo $title ?>
						</a>
					<?php } ?>
				</strong>
			</div>

			<?php if ($includeDate) { ?>
			<div class="ccm-block-page-list-date">
			<?php echo $date; ?>
			</div>
			<?php } ?>

			<?php if ($includeDescription) { ?>
			<div class="ccm-block-page-list-description">
			<?php echo $description; ?>
			</div>
			<?php } ?>
			
			<?php if (isset($useButtonForLink) && $useButtonForLink) { ?>
			<div class="ccm-block-page-list-page-entry-read-more">
				<a href="<?php echo $url?>" target="<?php echo $target; ?>" class="<?php echo $buttonClasses; ?>">
					<?php echo $buttonLinkText?>
				</a>
			</div>
			<?php } ?>

		</div>

		<?php } // ends: foreach ?>
		
		</div> <?php // ends: .ccm-block-page-list-pages ?>

		<?php if (count($pages) == 0) { ?>
		<div class="ccm-block-page-list-no-pages">
		<?php echo h($noResultsMessage)?>
		</div>
		<?php } ?>

</div> <?php // ends: .ccm-block-page-list-thumbnail-grid-wrapper ?>

<?php if ($showPagination) { echo $pagination; } ?>

<?php if ($c->isEditMode() && $controller->isBlockEmpty()) { ?>
<div class="ccm-edit-mode-disabled-item">
<?php echo t('Empty Page List Block.')?>
</div>
<?php } ?>

<?php } // ends: ifEditMode ?>
