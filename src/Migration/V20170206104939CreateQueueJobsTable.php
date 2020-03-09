<?php

namespace Miaoxing\Queue\Migration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Miaoxing\Services\Migration\BaseMigration;

class V20170206104939CreateQueueJobsTable extends BaseMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        Schema::create('queue_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        Schema::dropIfExists('queue_jobs');
    }
}
