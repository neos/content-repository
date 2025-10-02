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

require_once(__DIR__ . '/../../../../../../Application/Neos.Behat/Tests/Behat/FlowContextTrait.php');
require_once(__DIR__ . '/NodeOperationsTrait.php');
require_once(__DIR__ . '/NodeAuthorizationTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/IsolatedBehatStepsTrait.php');
require_once(__DIR__ . '/../../../../../../Framework/Neos.Flow/Tests/Behavior/Features/Bootstrap/SecurityOperationsTrait.php');

use Behat\Behat\Context\Context;
use Neos\Behat\Tests\Behat\FlowContextTrait;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Service\AuthorizationService;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\NodeAuthorizationTrait;
use Neos\ContentRepository\Tests\Behavior\Features\Bootstrap\NodeOperationsTrait;
use Neos\ContentRepository\Tests\Functional\Command\BehatTestHelper;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\IsolatedBehatStepsTrait;
use Neos\Flow\Tests\Behavior\Features\Bootstrap\SecurityOperationsTrait;
use Neos\Flow\Utility\Environment;

/**
 * Features context
 */
class FeatureContext implements Context
{
    use FlowContextTrait;
    use NodeOperationsTrait;
    use NodeAuthorizationTrait;
    use SecurityOperationsTrait;
    use IsolatedBehatStepsTrait;

    private function getObject(string $className): object
    {
        return $this->objectManager->get($className);
    }

    /**
     * @var string
     */
    protected $behatTestHelperObjectName = BehatTestHelper::class;

    /**
     * @var Environment
     */
    protected $environment;

    public function __construct()
    {
        if (self::$bootstrap === null) {
            self::$bootstrap = $this->initializeFlow();
        }
        $this->objectManager = self::$bootstrap->getObjectManager();
        $this->environment = $this->objectManager->get(Environment::class);
        $this->nodeAuthorizationService = $this->objectManager->get(AuthorizationService::class);
        $this->nodeTypeManager = $this->objectManager->get(NodeTypeManager::class);
        $this->setupSecurity();
    }

    /**
     * @return Environment
     */
    protected function getEnvironment()
    {
        return $this->objectManager->get(Environment::class);
    }
}
