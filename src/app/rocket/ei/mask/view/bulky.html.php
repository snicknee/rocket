<?php
	/*
	 * Copyright (c) 2012-2016, Hofmänner New Media.
	 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
	 *
	 * This file is part of the n2n module ROCKET.
	 *
	 * ROCKET is free software: you can redistribute it and/or modify it under the terms of the
	 * GNU Lesser General Public License as published by the Free Software Foundation, either
	 * version 2.1 of the License, or (at your option) any later version.
	 *
	 * ROCKET is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
	 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
	 *
	 * The following people participated in this project:
	 *
	 * Andreas von Burg...........:	Architect, Lead Developer, Concept
	 * Bert Hofmänner.............: Idea, Frontend UI, Design, Marketing, Concept
	 * Thomas Günther.............: Developer, Frontend UI, Rocket Capability for Hangar
	 */

	use n2n\impl\web\ui\view\html\HtmlView;
	use rocket\ei\manage\gui\ui\DisplayStructure;
	use rocket\ei\util\model\Eiu;
	use rocket\ei\manage\EiHtmlBuilder;

	$view = HtmlView::view($this);
	$html = HtmlView::html($this);
	$formHtml = HtmlView::formHtml($this);

	$displayStructure = $view->getParam('displayStructure');
	$view->assert($displayStructure instanceof DisplayStructure);
	
	$eiu = $view->getParam('eiu');
	$view->assert($eiu instanceof Eiu);

	$eiHtml = new EiHtmlBuilder($view);
	
	$entryOpen = $eiHtml->meta()->isEntryOpen($eiu->entryGui());
	
	$renderForkMags = $view->getParam('renderForkMags', false, null);
	$renderInnerForks = false;
	$controlsAllowed = $view->getParam('controlsAllowed');
	if ($renderForkMags === null) {
		$renderInnerForks = 1 == count($displayStructure->getDisplayItems()) 
				&& $displayStructure->getDisplayItems()[0]->isGroup();
		$renderForkMags = !$renderInnerForks;
	}
	
	$controls = array();
	if ($renderInnerForks) {
		$renderForkMags = false;
	} else if ($renderForkMags) {
		if ($controlsAllowed) {
			$controls = $eiHtml->meta()->createEntryControls($eiu->entryGui(), 6);
		}
		$renderForkMags = $eiu->entryGui()->hasForkMags() || !empty($controls);
	}
?>

<?php if (!$entryOpen): ?>
	<?php $eiHtml->entryOpen('div', $eiu->entryGui()) ?>
<?php endif ?>

<?php if ($renderForkMags): ?>
	<div class="rocket-group-toolbar">
		<?php $eiHtml->entryForkControls() ?>
		<?php $eiHtml->commands($controls, true) ?>
	</div>
<?php endif ?>

<?php foreach ($displayStructure->getDisplayItems() as $displayItem): ?>
	<?php if ($displayItem->hasDisplayStructure()): ?>
		<?php $eiHtml->displayItemOpen('div', $displayItem) ?>
			<?php if (null !== ($label = $displayItem->getLabel())): ?>
				<label><?php $html->out($displayItem->getLabel()) ?></label>
			<?php endif ?>
	
			<?php if ($renderInnerForks): ?>
				<div class="rocket-group-toolbar">
					<?php $eiHtml->entryForkControls() ?>
					<?php $eiHtml->commands($controls, true) ?>
				</div>
			<?php endif ?>		
			
			<div class="rocket-control">
				<?php $view->import('bulky.html', $view->mergeParams(array(
						'displayStructure' => $displayItem->getDisplayStructure(), 
						'eiu' => $eiu, 'renderForkMags' => false))) ?>
			</div>
		<?php $eiHtml->displayItemClose() ?>
	<?php else: ?>
		<?php $eiHtml->fieldOpen('div', $displayItem) ?>
			<?php $eiHtml->fieldLabel() ?>
			
			<?php if ($renderInnerForks): ?>
				<div class="rocket-group-toolbar">
					<?php $eiHtml->entryForkControls() ?>
					<?php $eiHtml->commands($controls, true) ?>
				</div>
			<?php endif ?>	
			
			<div class="rocket-control">
				<?php $eiHtml->fieldContent() ?>
				<?php $eiHtml->fieldMessage() ?>
			</div>
		<?php $eiHtml->fieldClose() ?>
	<?php endif ?>
<?php endforeach; ?>

<?php if (!$entryOpen): ?>
	<?php $eiHtml->entryClose()?>
<?php endif ?>