<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

class DeleteVirtualHost extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        $vhostName = $input['vhost_name'];
        $confirm = $input['confirm'] ?? false;
        
        // Validate input
        if (empty($vhostName)) {
            $this->addErrorLog('Virtual host name is required');
            throw new \Exception('Virtual host name is required');
        }
        
        if (!$confirm) {
            $this->addErrorLog('Please confirm the deletion');
            throw new \Exception('Deletion not confirmed');
        }
        
        // Prevent deletion of default vhost
        if ($vhostName === '/') {
            $this->addErrorLog('Cannot delete the default virtual host');
            throw new \Exception('Cannot delete the default virtual host');
        }
        
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Delete the virtual host
        $this->server->ssh()->exec(
            "sudo rabbitmqctl delete_vhost {$vhostName}",
            'delete-vhost'
        );
        
        $this->addSuccessLog("Virtual host '{$vhostName}' deleted successfully");
        $this->addWarningLog('All queues, exchanges, and bindings in this vhost have been removed');
    }
}