<?php
namespace TYPO3\TYPO3CR\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A container of properties which can be used as a template for generating new nodes.
 *
 * @api
 */
class NodeTemplate extends AbstractNodeData {

	/**
	 * The node name which acts as a path segment for its node path
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Set the name to $newName
	 *
	 * @param string $newName
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setName($newName) {
		if (!is_string($newName) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $newName) !== 1) {
			throw new \InvalidArgumentException('Invalid node name "' . $newName . '" (a node name must only contain characters, numbers and the "-" sign).', 1364290839);
		}
		$this->name = $newName;
	}

	/**
	 * Get the name of this node template.
	 *
	 * If a name has been set using setName(), it is returned. If not, but the
	 * template has a (non-empty) title property, this property is used to
	 * generate a valid name. As a last resort a random name is returned (in
	 * the form "nameXXXXX").
	 *
	 * @return string
	 * @api
	 */
	public function getName() {
		if ($this->name !== NULL) {
			return $this->name;
		}

		if ($this->hasProperty('title') && strlen($this->getProperty('title')) > 0) {
			return \TYPO3\TYPO3CR\Utility::renderValidNodeName($this->getProperty('title'));
		}

		return uniqid('node');
	}

	/**
	 * A NodeTemplate is not stored in any workspace, thus this method returns NULL.
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	public function getWorkspace() {
		return;
	}

}