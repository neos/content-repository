<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\NodeVariation;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\Exception\DimensionSpacePointNotFound;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Feature\NodeVariation\Command\ResetNodeVariant;
use Neos\ContentRepository\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Feature\Common\Exception\DimensionSpacePointIsNotYetOccupied;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\ContentRepository\Feature\Common\Exception\NodeAggregateCurrentlyExists;
use Neos\ContentRepository\Feature\Common\ConstraintChecks;
use Neos\ContentRepository\Feature\Common\NodeVariationInternals;
use Neos\ContentRepository\Feature\Common\NodeAggregateEventPublisher;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DomainEvents;

trait NodeVariation
{
    use NodeVariationInternals;
    use ConstraintChecks;

    abstract protected function getReadSideMemoryCacheManager(): ReadSideMemoryCacheManager;

    abstract protected function getNodeAggregateEventPublisher(): NodeAggregateEventPublisher;

    abstract protected function getRuntimeBlocker(): RuntimeBlocker;

    /**
     * @throws ContentStreamDoesNotExistYet
     * @throws NodeAggregateCurrentlyExists
     * @throws DimensionSpacePointNotFound
     * @throws NodeAggregatesTypeIsAmbiguous
     * @throws DimensionSpacePointIsNotYetOccupied
     * @throws DimensionSpacePointIsAlreadyOccupied
     * @throws NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint
     */
    public function handleCreateNodeVariant(CreateNodeVariant $command): CommandResult
    {
        $this->getReadSideMemoryCacheManager()->disableCache();

        $this->requireContentStreamToExist($command->contentStreamIdentifier);
        $nodeAggregate = $this->requireProjectedNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier
        );
        $this->requireDimensionSpacePointToExist($command->sourceOrigin->toDimensionSpacePoint());
        $this->requireDimensionSpacePointToExist($command->targetOrigin->toDimensionSpacePoint());
        $this->requireNodeAggregateToNotBeRoot($nodeAggregate);
        $this->requireNodeAggregateToBeUntethered($nodeAggregate);
        $this->requireNodeAggregateToOccupyDimensionSpacePoint($nodeAggregate, $command->sourceOrigin);
        $this->requireNodeAggregateToNotOccupyDimensionSpacePoint($nodeAggregate, $command->targetOrigin);
        $parentNodeAggregate = $this->requireProjectedParentNodeAggregate(
            $command->contentStreamIdentifier,
            $command->nodeAggregateIdentifier,
            $command->sourceOrigin
        );
        $this->requireNodeAggregateToCoverDimensionSpacePoint(
            $parentNodeAggregate,
            $command->targetOrigin->toDimensionSpacePoint()
        );

        $events = $this->createEventsForVariations(
            $command->contentStreamIdentifier,
            $command->sourceOrigin,
            $command->targetOrigin,
            $nodeAggregate,
            $command->initiatingUserIdentifier
        );

        $this->getNodeAggregateEventPublisher()->withCommand($command, function () use ($command, $events) {
            $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
                $command->contentStreamIdentifier
            );

            $this->getNodeAggregateEventPublisher()->publishMany($streamName->getEventStreamName(), $events);
        });

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }

    public function handleResetNodeVariant(ResetNodeVariant $command): CommandResult
    {
        $events = DomainEvents::createEmpty();

        return CommandResult::fromPublishedEvents($events, $this->getRuntimeBlocker());
    }
}
