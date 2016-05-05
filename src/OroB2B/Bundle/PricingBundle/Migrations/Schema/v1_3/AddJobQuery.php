<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use JMS\JobQueueBundle\Entity\Job;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;

class AddJobQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $commandName;

    /**
     * @var array
     */
    protected $args;

    /**
     * @param $commandName
     * @param array $args
     */
    public function __construct($commandName, array $args = [])
    {
        $this->commandName = $commandName;
        $this->args = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'INSERT INTO jms_jobs (command, args, createdAt, queue, state) ';
        $query .= 'VALUES (:command, :args, :now, :queue, :state)';
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $params = [
            'command' => $this->commandName,
            'args' => $this->args,
            'now' => $now,
            'queue' => Job::DEFAULT_QUEUE,
            'state' => Job::STATE_NEW,
        ];
        $types = [
            'command' => Type::STRING,
            'args' => Type::JSON_ARRAY,
            'now' => Type::DATETIME,
            'queue' => Type::STRING,
            'state' => Type::STRING,
        ];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeQuery($query, $params, $types);
        }
    }
}
