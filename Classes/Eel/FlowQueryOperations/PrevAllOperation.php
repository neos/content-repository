<?php
namespace Neos\ContentRepository\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Exception\NodeException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

/**
 * "prevAll" operation working on ContentRepository nodes. It iterates over all
 * context elements and returns each preceding sibling or only those matching
 * the filter expression specified as optional argument
 */
class PrevAllOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'prevAll';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 0;

    /**
     * {@inheritdoc}
     *
     * @param array $context $context onto which this operation should be applied (array or array-like object)
     * @return boolean true if the operation can be applied onto the $context, false otherwise
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof TraversableNodeInterface));
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $output = [];
        $outputNodeAggregateIdentifiers = [];
        foreach ($flowQuery->getContext() as $contextNode) {
            foreach ($this->getPrevForNode($contextNode) as $prevNode) {
                if ($prevNode !== null && !isset($outputNodeAggregateIdentifiers[(string)$prevNode->getNodeAggregateIdentifier()])) {
                    $outputNodeAggregateIdentifiers[(string)$prevNode->getNodeAggregateIdentifier()] = true;
                    $output[] = $prevNode;
                }
            }
        }
        $flowQuery->setContext($output);

        if (isset($arguments[0]) && !empty($arguments[0])) {
            $flowQuery->pushOperation('filter', $arguments);
        }
    }

    /**
     * @param TraversableNodeInterface $contextNode The node for which the preceding node should be found
     * @return TraversableNodeInterface[] The preceding nodes of $contextNode
     */
    protected function getPrevForNode(TraversableNodeInterface $contextNode)
    {
        try {
            $parentNode = $contextNode->findParentNode();
        } catch (NodeException $e) {
            return [];
        }
        return $parentNode->findChildNodes()->previousAll($contextNode)->toArray();
    }
}
