<?php


namespace App\Services\Kafka;

use App\Constants\TraceCode;
use \RdKafka\Conf;
use Trace;

trait KafkaTrait
{
    protected function getConfig(): Conf
    {
        $conf = new Conf();

        $conf->set('client.id', 'auth-kafka');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', env('QUEUE_KAFKA_CONSUMER_BROKERS'));

        $tlsEnabled = env('QUEUE_KAFKA_CONSUMER_TLS_ENABLED', 'false');

        $sslCertificationVerification = ($tlsEnabled === true) ? 'true' : 'false';

        $conf->set('enable.ssl.certificate.verification', $sslCertificationVerification);

        //Set Security Protocol to ssl, needs ca-cert for ssl handle-shake
        $conf->set('security.protocol', 'ssl');

        $conf->set('enable.auto.commit', 'false');

        $kafkaUserCertString = $this->sanitizeCertValue(env('KAFKA_USER_CERT', ''));

        $kafkaUserKeyString = $this->sanitizeCertValue(env('KAFKA_USER_KEY', ''));

        $kafkaCaCertString = $this->sanitizeCertValue(env('KAFKA_CA_CERT', ''));

        // export pem format cert to kafka_ca_cert.cer, pass the file path to ssl.ca.location
        // ca-cert is used verify the broker key.
        if ((empty($kafkaCaCertString) === false) and
            (empty($kafkaUserCertString) === false) and (empty($kafkaUserKeyString) === false))
        {
            $kafkaCaCertFilePath = $this->exportCertToFile($kafkaCaCertString, 'kafka_ca_cert.pem');

            $conf->set('ssl.ca.location', $kafkaCaCertFilePath);

            $kafkaUserCertFilePath = $this->exportCertToFile($kafkaUserCertString, 'kafka_user_cert.crt');

            $conf->set('ssl.certificate.location', $kafkaUserCertFilePath);

            $kafkaUserKeyFilePath = $this->exportPrivateKeyToFile($kafkaUserKeyString, 'kafka_user_key.key');

            $conf->set('ssl.key.location', $kafkaUserKeyFilePath);
        }

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $conf->set('auto.offset.reset', 'smallest');

        $isDebugModeEnable = env('QUEUE_KAFKA_ENABLE_DEBUG_MODE', 'false');

        if ($isDebugModeEnable === true)
        {
            $conf->set('debug', 'consumer,broker');
        }

        return $conf;
    }

    private function exportCertToFile(string $kafkaCertString, string $fileName)
    {
        $kafkaCertFilePath = env('QUEUE_KAFKA_CONSUMER_CERTS_PATH') . '/' . $fileName;

        if (file_exists($kafkaCertFilePath) === false)
        {
            $isCertExportSuccess = openssl_x509_export_to_file($kafkaCertString, $kafkaCertFilePath);

            if ($isCertExportSuccess === false)
            {
                Trace::critical(TraceCode::KAFKA_CERT_ERROR, [
                    'message' => 'failed to export cert into file path',
                    'fileName' => $fileName
                ]);
            }
        }

        return $kafkaCertFilePath;
    }

    private function exportPrivateKeyToFile(string $kafkaUserKeyString, string $fileName)
    {
        $kafkaUserKeyFilePath = env('QUEUE_KAFKA_CONSUMER_CERTS_PATH') . '/' . $fileName;

        if (file_exists($kafkaUserKeyFilePath) === false)
        {
            $isUserCertExportSuccess = openssl_pkey_export_to_file($kafkaUserKeyString, $kafkaUserKeyFilePath);

            if ($isUserCertExportSuccess === false)
            {
                Trace::critical(TraceCode::KAFKA_CERT_ERROR, [
                    'message' => 'failed to export user key into file path'
                ]);
            }
        }

        return $kafkaUserKeyFilePath;
    }

    private function sanitizeCertValue(string $certString)
    {
        return trim(str_replace('\n', "\n", $certString));
    }
}
