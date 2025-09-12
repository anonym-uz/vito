<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

class DisableManagementUI extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Disable the management plugin
        $this->server->ssh()->exec(
            'sudo rabbitmq-plugins disable rabbitmq_management',
            'disable-rabbitmq-management'
        );
        
        // Remove firewall rule if it exists
        try {
            $this->server->ssh()->exec(
                'sudo ufw delete allow 15672/tcp',
                'close-management-port'
            );
        } catch (\Exception $e) {
            // UFW might not be enabled or rule might not exist
        }
        
        // Restart RabbitMQ to apply changes
        $this->server->ssh()->exec(
            'sudo systemctl restart rabbitmq-server',
            'restart-rabbitmq'
        );
        
        $this->addSuccessLog('RabbitMQ Management UI has been disabled');
    }
}