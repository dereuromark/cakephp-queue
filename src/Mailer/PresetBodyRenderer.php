<?php
declare(strict_types=1);

/**
 * @author Mark Scherer
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Queue\Mailer;

use Cake\Mailer\Renderer;

/**
 * Returns bodies that were already rendered before queueing, so Mailer::deliver()
 * does not replace them with empty content.
 */
class PresetBodyRenderer extends Renderer {

	/**
	 * @param array<string, string> $bodies Keyed by body type (`html`, `text`).
	 */
	public function __construct(protected array $bodies) {
		parent::__construct();
	}

	/**
	 * @param string $content
	 * @param array<string> $types
	 *
	 * @return array<string, string>
	 */
	public function render(string $content, array $types = []): array {
		$rendered = [];
		foreach ($types as $type) {
			$rendered[$type] = $this->bodies[$type] ?? $content;
		}

		return $rendered;
	}

}
