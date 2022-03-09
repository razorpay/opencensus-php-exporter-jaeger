<?php


namespace App\Services\Kafka\Producer;

use RdKafka\Producer;
use App\Constants\TraceCode;
use App\Services\Kafka\KafkaTrait;
use Trace;

class KafkaProducer
{
    use KafkaTrait;

    protected $kafkaTopic;

    protected $producer;

    protected $message;

    protected $key;

    protected $producerPollTimeOutMS = 0;

    protected $producerFlushTimeOutMS = 10000;

    public function __construct($topicName, $message, $key = null)
    {
        $conf = $this->getConfig();

        $this->producer = new Producer($conf);

        $this->kafkaTopic = $this->producer->newTopic($topicName);

        $this->message = $message;

        $this->key = $key;

        $this->producerPollTimeOutMS = env('PRODUCER_POLL_TIMEOUT_MS', $this->producerPollTimeOutMS);

        $this->producerFlushTimeOutMS = env('PRODUCER_FLUSH_TIMEOUT_MS', $this->producerFlushTimeOutMS);
    }

    public function Produce()
    {
        $this->kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $this->message, $this->key);

        $this->producer->poll($this->producerPollTimeOutMS);

        $result = $this->producer->flush($this->producerFlushTimeOutMS);

        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result)
        {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }

        Trace::info(TraceCode::KAFKA_PRODUCER_FLUSH_SUCCESS, ['message' => $this->message]);
    }
}
