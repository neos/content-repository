<?php
namespace TYPO3\TYPO3CR\Service;

/*
 * This file is part of the TYPO3.TYPO3CR package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * TYPO3CR Publishing Service Interface
 *
 * @api
 */
interface PublishingServiceInterface
{
    /**
     * Returns a list of nodes contained in the given workspace which are not yet published
     *
     * @param Workspace $workspace
     * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
     */
    public function getUnpublishedNodes(Workspace $workspace);

    /**
     * Returns the number of unpublished nodes contained in the given workspace
     *
     * @param Workspace $workspace
     * @return integer
     * @api
     */
    public function getUnpublishedNodesCount(Workspace $workspace);

    /**
     * Publishes the given node to the specified target workspace. If no workspace is specified, "live" is assumed.
     *
     * @param NodeInterface $node
     * @param Workspace $targetWorkspace If not set the "live" workspace is assumed to be the publishing target
     * @return void
     * @api
     */
    public function publishNode(NodeInterface $node, Workspace $targetWorkspace = null);

    /**
     * Publishes the given nodes to the specified target workspace. If no workspace is specified, "live" is assumed.
     *
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes The nodes to publish
     * @param Workspace $targetWorkspace If not set the "live" workspace is assumed to be the publishing target
     * @return void
     * @api
     */
    public function publishNodes(array $nodes, Workspace $targetWorkspace = null);
}
