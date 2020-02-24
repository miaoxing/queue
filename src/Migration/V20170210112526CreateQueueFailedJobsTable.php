<?php

namespace Miaoxing\Queue\Migration;

use Miaoxing\Services\Migration\BaseMigration;

class V20170210112526CreateQueueFailedJobsTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->schema->table('queue_failed_jobs')
            ->id()
            ->string('queue', 32)
            ->longText('payload')
            ->timestamp('created_at')
            ->exec();
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->schema->dropIfExists('queue_failed_jobs');
    }
}
