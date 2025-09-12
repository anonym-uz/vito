<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

class CreateUser extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        $username = $input['username'];
        $password = $input['password'];
        $tags = $input['tags'] ?? 'management';
        $vhost = $input['vhost'] ?? '/';
        $configurePermission = $input['configure_permission'] ?? '.*';
        $writePermission = $input['write_permission'] ?? '.*';
        $readPermission = $input['read_permission'] ?? '.*';
        
        // Validate input
        if (empty($username) || empty($password)) {
            $this->addErrorLog('Username and password are required');
            throw new \Exception('Username and password are required');
        }
        
        if (strlen($password) < 8) {
            $this->addErrorLog('Password must be at least 8 characters long');
            throw new \Exception('Password must be at least 8 characters long');
        }
        
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Create the user
        $this->server->ssh()->exec(
            "sudo rabbitmqctl add_user {$username} {$password}",
            'create-user'
        );
        
        // Set user tags
        $this->server->ssh()->exec(
            "sudo rabbitmqctl set_user_tags {$username} {$tags}",
            'set-user-tags'
        );
        
        // Set permissions on the specified vhost
        $this->server->ssh()->exec(
            "sudo rabbitmqctl set_permissions -p {$vhost} {$username} \"{$configurePermission}\" \"{$writePermission}\" \"{$readPermission}\"",
            'set-user-permissions'
        );
        
        $this->addSuccessLog("User '{$username}' created successfully");
        $this->addSuccessLog("Tags: {$tags}");
        $this->addSuccessLog("Permissions granted on vhost: {$vhost}");
        $this->addInfoLog("Configure: {$configurePermission}, Write: {$writePermission}, Read: {$readPermission}");
    }
}