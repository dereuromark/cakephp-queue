<?php
declare(strict_types=1);

namespace Queue\Controller\Admin;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Templating\View\Helper\IconHelper;
use Templating\View\Helper\IconSnippetHelper;
use Templating\View\Helper\TemplatingHelper;

trait LoadHelperTrait {

	/**
	 * @return void
	 */
	protected function loadHelpers(): void {
		$helpers = [];

		// Time helper: prefer Tools, fallback to core
		if (Plugin::isLoaded('Tools')) {
			$helpers[] = 'Tools.Time';
			$helpers[] = 'Tools.Text';
			$helpers[] = 'Tools.Format';
		} else {
			$helpers[] = 'Time';
			$helpers[] = 'Text';
		}

		// Configure helper: prefer Shim, fallback to Queue's own
		if (Plugin::isLoaded('Shim')) {
			$helpers[] = 'Shim.Configure';
		} else {
			$helpers[] = 'Queue.Configure';
		}

		if (Configure::read('Icon.sets')) {
			$helpers[] = class_exists(IconHelper::class) ? 'Templating.Icon' : 'Tools.Icon';
		}
		if (class_exists(IconSnippetHelper::class)) {
			$helpers[] = 'Templating.IconSnippet';
		}
		if (class_exists(TemplatingHelper::class)) {
			$helpers[] = 'Templating.Templating';
		}

		$this->viewBuilder()->addHelpers($helpers);
	}

}
