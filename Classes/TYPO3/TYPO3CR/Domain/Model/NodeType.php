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
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\TYPO3CR\Exception\InvalidNodeTypePostprocessorException;
use TYPO3\TYPO3CR\NodeTypePostprocessor\NodeTypePostprocessorInterface;

/**
 * A Node Type
 *
 * Although methods contained in this class belong to the public API, you should
 * not need to deal with creating or managing node types manually. New node types
 * should be defined in a NodeTypes.yaml file.
 *
 * @api
 */
class NodeType {

	/**
	 * Name of this node type. Example: "TYPO3CR:Folder"
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Configuration for this node type, can be an arbitrarily nested array.
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * Is this node type marked abstract
	 *
	 * @var boolean
	 */
	protected $abstract = FALSE;

	/**
	 * Is this node type marked final
	 *
	 * @var boolean
	 */
	protected $final = FALSE;

	/**
	 * node types this node type directly inherits from
	 *
	 * @var array<\TYPO3\TYPO3CR\Domain\Model\NodeType>
	 */
	protected $declaredSuperTypes;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * Whether or not this node type has been initialized (e.g. if it has been postprocessed)
	 *
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * Constructs this node type
	 *
	 * @param string $name Name of the node type
	 * @param array $declaredSuperTypes Parent types of this node type
	 * @param array $configuration the configuration for this node type which is defined in the schema
	 * @throws \InvalidArgumentException
	 */
	public function __construct($name, array $declaredSuperTypes, array $configuration) {
		$this->name = $name;

		foreach ($declaredSuperTypes as $type) {
			if (!$type instanceof NodeType) {
				throw new \InvalidArgumentException('$declaredSuperTypes must be an array of NodeType objects', 1291300950);
			}
		}
		$this->declaredSuperTypes = $declaredSuperTypes;

		if (isset($configuration['abstract']) && $configuration['abstract'] === TRUE) {
			$this->abstract = TRUE;
			unset($configuration['abstract']);
		}

		if (isset($configuration['final']) && $configuration['final'] === TRUE) {
			$this->final = TRUE;
			unset($configuration['final']);
		}

		$this->configuration = $configuration;
	}

	/**
	 * Initializes this node type
	 *
	 * @return void
	 */
	protected function initialize() {
		if ($this->initialized === TRUE) {
			return;
		}
		$this->initialized = TRUE;
		$this->applyPostprocessing();
	}

	/**
	 * Iterates through configured postprocessors and invokes them
	 *
	 * @return void
	 * @throws \TYPO3\TYPO3CR\Exception\InvalidNodeTypePostprocessorException
	 */
	protected function applyPostprocessing() {
		if (!isset($this->configuration['postprocessors'])) {
			return;
		}
		foreach ($this->configuration['postprocessors'] as $postprocessorConfiguration) {
			$postprocessor = new $postprocessorConfiguration['postprocessor']();
			if (!$postprocessor instanceof NodeTypePostprocessorInterface) {
				throw new InvalidNodeTypePostprocessorException(sprintf('Expected NodeTypePostprocessorInterface but got "%s"', get_class($postprocessor)), 1364759955);
			}
			$postprocessorOptions = array();
			if (isset($postprocessorConfiguration['postprocessorOptions'])) {
				$postprocessorOptions = $postprocessorConfiguration['postprocessorOptions'];
			}
			$postprocessor->process($this, $this->configuration, $postprocessorOptions);
		}
	}

	/**
	 * Returns the name of this node type
	 *
	 * @return string
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Return boolean TRUE if marked abstract
	 *
	 * @return boolean
	 */
	public function isAbstract() {
		return $this->abstract;
	}

	/**
	 * Return boolean TRUE if marked final
	 *
	 * @return boolean
	 */
	public function isFinal() {
		return $this->final;
	}

	/**
	 * Returns the direct, explicitly declared super types
	 * of this node type.
	 *
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeType>
	 * @api
	 */
	public function getDeclaredSuperTypes() {
		return $this->declaredSuperTypes;
	}

	/**
	 * If this node type or any of the direct or indirect super types
	 * has the given name.
	 *
	 * @param string $nodeType
	 * @return boolean TRUE if this node type is of the given kind, otherwise FALSE
	 * @api
	 */
	public function isOfType($nodeType) {
		if ($nodeType === $this->name) {
			return TRUE;
		}
		foreach ($this->declaredSuperTypes as $superType) {
			if ($superType->isOfType($nodeType) === TRUE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Get the full configuration of the node type. Should only be used internally.
	 *
	 * Instead, use the hasConfiguration()/getConfiguration() methods to check/retrieve single configuration values.
	 *
	 * @return array
	 */
	public function getFullConfiguration() {
		$this->initialize();
		return $this->configuration;
	}

	/**
	 * Checks if the configuration of this node type contains a setting for the given $configurationPath
	 *
	 * @param string $configurationPath The name of the configuration option to verify
	 * @return boolean
	 * @api
	 */
	public function hasConfiguration($configurationPath) {
		return $this->getConfiguration($configurationPath) !== NULL;
	}

	/**
	 * Returns the configuration option with the specified $configurationPath or NULL if it does not exist
	 *
	 * @param string $configurationPath The name of the configuration option to retrieve
	 * @return mixed
	 * @api
	 */
	public function getConfiguration($configurationPath) {
		$this->initialize();
		return ObjectAccess::getPropertyPath($this->configuration, $configurationPath);
	}

	/**
	 * Get the human-readable label of this node type
	 *
	 * @return string
	 * @api
	 */
	public function getLabel() {
		$this->initialize();
		return isset($this->configuration['ui']) && isset($this->configuration['ui']['label']) ? $this->configuration['ui']['label'] : '';
	}

	/**
	 * Get additional options (if specified)
	 *
	 * @return array
	 * @api
	 */
	public function getOptions() {
		$this->initialize();
		return (isset($this->configuration['options']) ? $this->configuration['options'] : array());
	}

	/**
	 * Return the node label generator class for the given node
	 *
	 * @return NodeLabelGeneratorInterface
	 */
	public function getNodeLabelGenerator() {
		$this->initialize();
		if (isset($this->configuration['nodeLabelGenerator'])) {
			$nodeLabelGeneratorClassName = $this->configuration['nodeLabelGenerator'];
		} else {
			$nodeLabelGeneratorClassName = 'TYPO3\TYPO3CR\Domain\Model\DefaultNodeLabelGenerator';
		}
		return $this->objectManager->get($nodeLabelGeneratorClassName);
	}

	/**
	 * Return the array with the defined properties. The key is the property name,
	 * the value the property configuration. There are no guarantees on how the
	 * property configuration looks like.
	 *
	 * @return array
	 * @api
	 */
	public function getProperties() {
		$this->initialize();
		return (isset($this->configuration['properties']) ? $this->configuration['properties'] : array());
	}

	/**
	 * Returns the configured type of the specified property
	 *
	 * @param string $propertyName Name of the property
	 * @return string
	 */
	public function getPropertyType($propertyName) {
		if (!isset($this->configuration['properties']) || !isset($this->configuration['properties'][$propertyName]) || !isset($this->configuration['properties'][$propertyName]['type'])) {
			return 'string';
		}
		return $this->configuration['properties'][$propertyName]['type'];
	}

	/**
	 * Return an array with the defined default values for each property, if any.
	 *
	 * The default value is configured for each property under the "default" key.
	 *
	 * @return array
	 * @api
	 */
	public function getDefaultValuesForProperties() {
		$this->initialize();
		if (!isset($this->configuration['properties'])) {
			return array();
		}

		$defaultValues = array();
		foreach ($this->configuration['properties'] as $propertyName => $propertyConfiguration) {
			if (isset($propertyConfiguration['defaultValue'])) {
				$type = isset($propertyConfiguration['type']) ? $propertyConfiguration['type'] : '';
				switch ($type) {
					case 'date':
						$defaultValues[$propertyName] = new \DateTime($propertyConfiguration['defaultValue']);
					break;
					default:
						$defaultValues[$propertyName] = $propertyConfiguration['defaultValue'];
				}
			}
		}

		return $defaultValues;
	}

	/**
	 * Return an array with child nodes which should be automatically created
	 *
	 * @return array the key of this array is the name of the child, and the value its NodeType.
	 * @api
	 */
	public function getAutoCreatedChildNodes() {
		$this->initialize();
		if (!isset($this->configuration['childNodes'])) {
			return array();
		}

		$autoCreatedChildNodes = array();
		foreach ($this->configuration['childNodes'] as $childNodeName => $childNodeConfiguration) {
			if (isset($childNodeConfiguration['type'])) {
				$autoCreatedChildNodes[$childNodeName] = $this->nodeTypeManager->getNodeType($childNodeConfiguration['type']);
			}
		}

		return $autoCreatedChildNodes;
	}

	/**
	 * Checks if the given NodeType is acceptable as sub-node with the configured constraints,
	 * not taking constraints of auto-created nodes into account. Thus, this method only returns
	 * the correct result if called on NON-AUTO-CREATED nodes!
	 *
	 * Otherwise, allowsGrandchildNodeType() needs to be called on the *parent node type*.
	 *
	 * @param NodeType $nodeType
	 * @return boolean TRUE if the $nodeType is allowed as child node, FALSE otherwise.
	 */
	public function allowsChildNodeType(NodeType $nodeType) {
		$constraints = $this->getConfiguration('constraints.nodeTypes') ?: array();
		return $this->isNodeTypeAllowedByConstraints($nodeType, $constraints);
	}

	/**
	 * Checks if the given $nodeType is allowed as a childNode of the given $childNodeName
	 * (which must be auto-created in $this NodeType).
	 *
	 * Only allowed to be called if $childNodeName is auto-created.
	 *
	 * @param string $childNodeName The name of a configured childNode of this NodeType
	 * @param NodeType $nodeType The NodeType to check constraints for.
	 * @return boolean TRUE if the $nodeType is allowed as grandchild node, FALSE otherwise.
	 * @throws \InvalidArgumentException If the given $childNodeName is not configured to be auto-created in $this.
	 */
	public function allowsGrandchildNodeType($childNodeName, NodeType $nodeType) {
		$autoCreatedChildNodes = $this->getAutoCreatedChildNodes();
		if (!isset($autoCreatedChildNodes[$childNodeName])) {
			throw new \InvalidArgumentException('The method "allowsGrandchildNodeType" can only be used on auto-created childNodes, given $childNodeName "' . $childNodeName . '" is not auto-created.', 1403858395);
		}
		$constraints = $autoCreatedChildNodes[$childNodeName]->getConfiguration('constraints.nodeTypes') ?: array();

		$childNodeConstraintConfiguration = $this->getConfiguration('childNodes.' . $childNodeName . '.constraints.nodeTypes') ?: array();
		$constraints = Arrays::arrayMergeRecursiveOverrule($constraints, $childNodeConstraintConfiguration);

		return $this->isNodeTypeAllowedByConstraints($nodeType, $constraints);
	}

	/**
	 * Internal method to check whether the passed-in $nodeType is allowed by the $constraints array.
	 *
	 * $constraints is an associative array where the key is the Node Type Name. If the value is "TRUE",
	 * the node type is explicitly allowed. If the value is "FALSE", the node type is explicitly denied.
	 * If nothing is specified, the fallback "*" is used. If that one is also not specified, we DENY by
	 * default.
	 *
	 * Super types of the given node types are also checked, so if a super type is constrained
	 * it will also take affect on the inherited node types. The closest constrained super type match is used.
	 *
	 * @param NodeType $nodeType
	 * @param array $constraints
	 * @return boolean
	 */
	protected function isNodeTypeAllowedByConstraints(NodeType $nodeType, array $constraints) {
		$directConstraintsResult = $this->isNodeTypeAllowedByDirectConstraints($nodeType, $constraints);
		if ($directConstraintsResult !== NULL) {
			return $directConstraintsResult;
		}

		$inheritanceConstraintsResult = $this->isNodeTypeAllowedByInheritanceConstraints($nodeType, $constraints);
		if ($inheritanceConstraintsResult !== NULL) {
			return $inheritanceConstraintsResult;
		}

		if (isset($constraints['*'])) {
			return (boolean)$constraints['*'];
		}

		return FALSE;
	}

	/**
	 * @param NodeType $nodeType
	 * @param array $constraints
	 * @return boolean TRUE if the passed $nodeType is allowed by the $constraints
	 */
	protected function isNodeTypeAllowedByDirectConstraints(NodeType $nodeType, array $constraints) {
		if ($constraints === array()) {
			return TRUE;
		}

		if (array_key_exists($nodeType->getName(), $constraints) && $constraints[$nodeType->getName()] === TRUE) {
			return TRUE;
		}

		if (array_key_exists($nodeType->getName(), $constraints) && $constraints[$nodeType->getName()] === FALSE) {
			return FALSE;
		}

		return NULL;
	}

	/**
	 * This method loops over the constraints and finds node types that the given node type inherits from. For all
	 * matched super types, their super types are traversed to find the closest super node with a constraint which
	 * is used to evaluated if the node type is allowed. It finds the closest results for true and false, and uses
	 * the distance to choose which one wins (lowest). If no result is found the node type is allowed.
	 *
	 * @param NodeType $nodeType
	 * @param array $constraints
	 * @return boolean|NULL if no constraint matched
	 */
	protected function isNodeTypeAllowedByInheritanceConstraints(NodeType $nodeType, array $constraints) {
		$constraintDistanceForTrue = NULL;
		$constraintDistanceForFalse = NULL;
		foreach ($constraints as $superType => $constraint) {
			if ($nodeType->isOfType($superType)) {
				$distance = $this->traverseSuperTypes($nodeType, $superType, 0);

				if ($constraint === TRUE && ($constraintDistanceForTrue === NULL || $constraintDistanceForTrue > $distance)) {
					$constraintDistanceForTrue = $distance;
				}
				if ($constraint === FALSE && ($constraintDistanceForFalse === NULL || $constraintDistanceForFalse > $distance)) {
					$constraintDistanceForFalse = $distance;
				}
			}
		}

		if ($constraintDistanceForTrue !== NULL && $constraintDistanceForFalse !== NULL) {
			return $constraintDistanceForTrue < $constraintDistanceForFalse ? TRUE : FALSE;
		}

		if ($constraintDistanceForFalse !== NULL) {
			return FALSE;
		}

		if ($constraintDistanceForTrue !== NULL) {
			return TRUE;
		}

		return NULL;
	}

	/**
	 * This method traverses the given node type to find the first super type that matches the constraint node type.
	 * In case the hierarchy has more than one way of finding a path to the node type it's not taken into account,
	 * since the first matched is returned. This is accepted on purpose for performance reasons and due to the fact
	 * that such hierarchies should be avoided.
	 *
	 * @param NodeType $currentNodeType
	 * @param string $constraintNodeTypeName
	 * @param integer $distance
	 * @return integer or NULL if no NodeType matched
	 */
	protected function traverseSuperTypes(NodeType $currentNodeType, $constraintNodeTypeName, $distance) {
		if ($currentNodeType->getName() === $constraintNodeTypeName) {
			return $distance;
		}

		$distance++;
		foreach ($currentNodeType->getDeclaredSuperTypes() as $superType) {
			$result = $this->traverseSuperTypes($superType, $constraintNodeTypeName, $distance);
			if ($result !== NULL) {
				return $result;
			}
		}

		return NULL;
	}

	/**
	 * Alias for getName().
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return $this->getName();
	}

	/**
	 * Magic get* and has* method for all properties inside $configuration.
	 *
	 * @param string $methodName
	 * @param array $arguments
	 * @return mixed
	 * @deprecated Use hasConfiguration() or getConfiguration() instead
	 */
	public function __call($methodName, array $arguments) {
		if (substr($methodName, 0, 3) === 'get') {
			$configurationKey = lcfirst(substr($methodName, 3));
			return $this->getConfiguration($configurationKey);
		} elseif (substr($methodName, 0, 3) === 'has') {
			$configurationKey = lcfirst(substr($methodName, 3));
			return $this->hasConfiguration($configurationKey);
		}

		trigger_error('Call to undefined method ' . get_class($this) . '::' . $methodName, E_USER_ERROR);
	}
}