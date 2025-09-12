<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

class MonitorQueues extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        $vhost = $input['vhost'] ?? '/';
        $includeMessageStats = $input['include_message_stats'] ?? true;
        $includeConsumerInfo = $input['include_consumer_info'] ?? true;
        
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Get list of queues
        $queuesOutput = $this->server->ssh()->exec(
            "sudo rabbitmqctl list_queues -p {$vhost} name messages consumers memory state",
            'list-queues'
        );
        
        $this->addInfoLog("Queue Status for vhost: {$vhost}");
        $this->addInfoLog("=====================================");
        
        // Parse and display queue information
        $lines = explode("\n", trim($queuesOutput));
        $header = array_shift($lines); // Remove header line
        
        if (empty($lines)) {
            $this->addInfoLog("No queues found in vhost '{$vhost}'");
            return;
        }
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                $queueName = $parts[0];
                $messages = $parts[1];
                $consumers = $parts[2];
                $memory = $this->formatBytes((int)$parts[3]);
                $state = $parts[4];
                
                $this->addInfoLog("Queue: {$queueName}");
                $this->addInfoLog("  Messages: {$messages}");
                $this->addInfoLog("  Consumers: {$consumers}");
                $this->addInfoLog("  Memory: {$memory}");
                $this->addInfoLog("  State: {$state}");
                
                // Get detailed message statistics if requested
                if ($includeMessageStats && $messages > 0) {
                    $statsOutput = $this->server->ssh()->exec(
                        "sudo rabbitmqctl list_queues -p {$vhost} name messages_ready messages_unacknowledged | grep '^{$queueName}'",
                        'get-message-stats'
                    );
                    
                    if (!empty($statsOutput)) {
                        $statsParts = preg_split('/\s+/', trim($statsOutput));
                        if (count($statsParts) >= 3) {
                            $this->addInfoLog("  Ready: {$statsParts[1]}");
                            $this->addInfoLog("  Unacknowledged: {$statsParts[2]}");
                        }
                    }
                }
                
                // Get consumer information if requested
                if ($includeConsumerInfo && $consumers > 0) {
                    $consumerOutput = $this->server->ssh()->exec(
                        "sudo rabbitmqctl list_consumers -p {$vhost} | grep '^{$queueName}'",
                        'get-consumer-info'
                    );
                    
                    if (!empty($consumerOutput)) {
                        $consumerLines = explode("\n", trim($consumerOutput));
                        $this->addInfoLog("  Consumer Details:");
                        foreach ($consumerLines as $consumerLine) {
                            if (!empty(trim($consumerLine))) {
                                $this->addInfoLog("    - {$consumerLine}");
                            }
                        }
                    }
                }
                
                $this->addInfoLog(""); // Empty line for readability
            }
        }
        
        // Get overview statistics
        $overviewOutput = $this->server->ssh()->exec(
            "sudo rabbitmqctl list_vhosts name messages",
            'get-vhost-overview'
        );
        
        $overviewLines = explode("\n", trim($overviewOutput));
        foreach ($overviewLines as $overviewLine) {
            if (strpos($overviewLine, $vhost) !== false) {
                $parts = preg_split('/\s+/', trim($overviewLine));
                if (count($parts) >= 2) {
                    $totalMessages = $parts[1] ?? '0';
                    $this->addSuccessLog("Total messages in vhost '{$vhost}': {$totalMessages}");
                }
                break;
            }
        }
        
        // Get node statistics
        $nodeStats = $this->server->ssh()->exec(
            "sudo rabbitmqctl node_health_check",
            'node-health-check'
        );
        
        if (strpos($nodeStats, 'Health check passed') !== false) {
            $this->addSuccessLog('RabbitMQ node health check: PASSED');
        } else {
            $this->addWarningLog('RabbitMQ node health check: Check the logs for details');
        }
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}