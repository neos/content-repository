<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Projection\Content\PropertyCollectionInterface;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Strip all tags on a given property
 */
class StripTagsOnProperty implements NodeBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    protected string $propertyName = '';

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    /**
     * Sets the name of the property to work on.
     */
    public function setProperty(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    public function execute(
        NodeInterface $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        if ($node->hasProperty($this->propertyName)) {
            /** @var PropertyCollectionInterface $properties */
            $properties = $node->getProperties();
            /** @var SerializedPropertyValue $serializedPropertyValue safe since NodeInterface::hasProperty */
            $serializedPropertyValue = $properties->serialized()->getProperty($this->propertyName);
            $propertyValue = $serializedPropertyValue->getValue();
            if (!is_string($propertyValue)) {
                throw new \Exception(
                    'StripTagsOnProperty can only be applied to properties of type string.',
                    1645391885
                );
            }
            $newValue = strip_tags($propertyValue);
            return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(
                new SetSerializedNodeProperties(
                    $contentStreamForWriting,
                    $node->getNodeAggregateIdentifier(),
                    $node->getOriginDimensionSpacePoint(),
                    SerializedPropertyValues::fromArray([
                        $this->propertyName => new SerializedPropertyValue(
                            $newValue,
                            $serializedPropertyValue->getType()
                        )
                    ]),
                    UserIdentifier::forSystemUser()
                )
            );
        }

        return CommandResult::createEmpty();
    }
}