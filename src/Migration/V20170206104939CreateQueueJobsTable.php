<?php

namespace Miaoxing\Queue\Migration;

use Miaoxing\Services\Migration\BaseMigration;

class V20170206104939CreateQueueJobsTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('queue_jobs')
            ->bigInt('id')->autoIncrement()
            ->string('queue', 32)
            ->longText('payload')
            ->tinyInt('attempts')
            ->timestamp('reserved_at')
            ->timestamp('available_at')
            ->timestamp('created_at')
            ->primary('id')
            ->index(['queue', 'reserved_at'])
            ->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->dropIfExists('queue_jobs');
    }
}
