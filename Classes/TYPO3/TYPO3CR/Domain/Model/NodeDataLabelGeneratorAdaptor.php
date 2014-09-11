<?php


namespace TYPO3\TYPO3CR\Domain\Model;

/**
 *
 * @deprecated since 1.2 This is merely a helper until the NodeDataLabelGeneratorInterface is finally removed.
 */
class NodeDataLabelGeneratorAdaptor implements NodeLabelGeneratorInterface {

	/**
	 * @var NodeDataLabelGeneratorInterface
	 */
	protected $nodeDataLabelGenerator;

	/**
	 * @return NodeDataLabelGeneratorInterface
	 */
	public function getNodeDataLabelGenerator() {
		return $this->nodeDataLabelGenerator;
	}

	/**
	 * @param NodeDataLabelGeneratorInterface $nodeDataLabelGenerator
	 */
	public function setNodeDataLabelGenerator($nodeDataLabelGenerator) {
		$this->nodeDataLabelGenerator = $nodeDataLabelGenerator;
	}

	/**
	 * Render a node label
	 *
	 * @param NodeInterface $node
	 * @param boolean $crop
	 * @return string
	 */
	public function getLabel(NodeInterface $node, $crop = TRUE) {
		return $this->nodeDataLabelGenerator->getLabel($node->getNodeData(), $crop);
	}

}