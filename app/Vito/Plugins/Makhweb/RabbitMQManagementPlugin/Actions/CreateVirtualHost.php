<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

class CreateVirtualHost extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        $vhostName = $input['vhost_name'];
        $description = $input['description'] ?? '';
        $tags = $input['tags'] ?? '';
        $grantPermissions = $input['grant_permissions'] ?? true;
        
        // Validate input
        if (empty($vhostName)) {
            $this->addErrorLog('Virtual host name is required');
            throw new \Exception('Virtual host name is required');
        }
        
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Create the virtual host
        $this->server->ssh()->exec(
            "sudo rabbitmqctl add_vhost {$vhostName}",
            'create-vhost'
        );
        
        // Set description if provided
        if (!empty($description)) {
            $this->server->ssh()->exec(
                "sudo rabbitmqctl set_vhost_metadata {$vhostName} description \"{$description}\"",
                'set-vhost-description'
            );
        }
        
        // Set tags if provided
        if (!empty($tags)) {
            $this->server->ssh()->exec(
                "sudo rabbitmqctl set_vhost_metadata {$vhostName} tags \"{$tags}\"",
                'set-vhost-tags'
            );
        }
        
        // Grant permissions to admin user if requested
        if ($grantPermissions) {
            // Get the admin username from RabbitMQ service data
            $adminUsername = $rabbitmq->type_data['username'] ?? 'guest';
            
            $this->server->ssh()->exec(
                "sudo rabbitmqctl set_permissions -p {$vhostName} {$adminUsername} \".*\" \".*\" \".*\"",
                'grant-admin-permissions'
            );
            
            $this->addSuccessLog("Granted full permissions to user '{$adminUsername}' on vhost '{$vhostName}'");
        }
        
        $this->addSuccessLog("Virtual host '{$vhostName}' created successfully");
    }
}