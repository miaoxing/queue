<?php

namespace Miaoxing\Queue\Migration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wei\Migration\BaseMigration;

class V20170210112526CreateQueueFailedJobsTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        Schema::create('queue_failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Schema::dropIfExists('queue_failed_jobs');
    }
}
