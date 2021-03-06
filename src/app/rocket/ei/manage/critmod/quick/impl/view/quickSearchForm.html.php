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
	use rocket\ei\manage\critmod\quick\impl\form\QuickSearchForm;
	use n2n\web\ui\Raw;
	
	$view = HtmlView::view($this);
	$html = HtmlView::html($this);
	$formHtml = HtmlView::formHtml($this);
	
	$quickSearchForm = $view->getParam('quickSearchForm');
	$this->assert($quickSearchForm instanceof QuickSearchForm);
?>


<?php $formHtml->open($quickSearchForm, null, null, array('class' => 'rocket-impl-quicksearch form-inline' 
				. ($quickSearchForm->isActive() ? ' rocket-active' : ''), 
		'data-rocket-impl-post-url' => $view->getParam('postUrl'))) ?>
	<?php $formHtml->label('searchStr', $html->getL10nText('common_search_label')) ?>
	<div class="input-group">
		<?php $formHtml->input('searchStr', array('class' => 'form-control'), 'search') ?>
		<span class="input-group-append">
			<?php $formHtml->buttonSubmit('search', new Raw('<i class="fa fa-search"></i>'),
					array('class' => 'btn btn-secondary',
							'title' => $view->getL10nText('ei_impl_list_quicksearch_tooltip'))) ?>
			<?php $formHtml->buttonSubmit('clear', new Raw('<i class="fa fa-eraser"></i>'),
					array('class' => 'btn btn-secondary',
							'title' => $view->getL10nText('ei_impl_list_quicksearch_erase_tooltip'))) ?>
		</span>
	</div>
<?php $formHtml->close() ?>
