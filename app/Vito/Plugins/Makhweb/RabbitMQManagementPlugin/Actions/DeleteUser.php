<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;

class DeleteUser extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        $username = $input['username'];
        $confirm = $input['confirm'] ?? false;
        
        // Validate input
        if (empty($username)) {
            $this->addErrorLog('Username is required');
            throw new \Exception('Username is required');
        }
        
        if (!$confirm) {
            $this->addErrorLog('Please confirm the deletion');
            throw new \Exception('Deletion not confirmed');
        }
        
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Prevent deletion of the main admin user
        $adminUsername = $rabbitmq->type_data['username'] ?? '';
        if ($username === $adminUsername) {
            $this->addErrorLog('Cannot delete the main admin user');
            throw new \Exception('Cannot delete the main admin user');
        }
        
        // Delete the user
        $this->server->ssh()->exec(
            "sudo rabbitmqctl delete_user {$username}",
            'delete-user'
        );
        
        $this->addSuccessLog("User '{$username}' deleted successfully");
        $this->addInfoLog('All permissions and connections for this user have been removed');
    }
}