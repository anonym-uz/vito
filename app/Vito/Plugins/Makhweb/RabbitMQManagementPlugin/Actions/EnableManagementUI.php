<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions;

use App\Models\Server;
use App\ServerFeatures\Action;
use Illuminate\Support\Str;

class EnableManagementUI extends Action
{
    public function __construct(public Server $server) {}

    public function run(array $input): void
    {
        $bindAddress = $input['bind_address'] ?? '0.0.0.0';
        $createAdmin = $input['create_admin_user'] ?? true;
        $adminUsername = $input['admin_username'] ?? 'admin';
        $adminPassword = $input['admin_password'] ?: Str::random(16);
        
        // Check if RabbitMQ is installed
        $rabbitmq = $this->server->messageQueue();
        if (!$rabbitmq) {
            $this->addErrorLog('RabbitMQ is not installed on this server');
            throw new \Exception('RabbitMQ is not installed on this server');
        }
        
        // Enable the management plugin
        $this->server->ssh()->exec(
            'sudo rabbitmq-plugins enable rabbitmq_management',
            'enable-rabbitmq-management'
        );
        
        // Configure the management interface bind address
        $config = <<<EOT
listeners.tcp.default = 5672
management.tcp.port = 15672
management.tcp.ip = {$bindAddress}
management.load_definitions = /etc/rabbitmq/definitions.json
EOT;
        
        $this->server->ssh()->exec(
            "echo '{$config}' | sudo tee /etc/rabbitmq/rabbitmq.conf",
            'configure-rabbitmq-management'
        );
        
        // Create admin user if requested
        if ($createAdmin) {
            $this->server->ssh()->exec(
                "sudo rabbitmqctl add_user {$adminUsername} {$adminPassword}",
                'create-admin-user'
            );
            
            $this->server->ssh()->exec(
                "sudo rabbitmqctl set_user_tags {$adminUsername} administrator",
                'set-admin-tags'
            );
            
            $this->server->ssh()->exec(
                "sudo rabbitmqctl set_permissions -p / {$adminUsername} \".*\" \".*\" \".*\"",
                'set-admin-permissions'
            );
            
            $this->addSuccessLog("Admin user '{$adminUsername}' created with password: {$adminPassword}");
        }
        
        // Restart RabbitMQ to apply changes
        $this->server->ssh()->exec(
            'sudo systemctl restart rabbitmq-server',
            'restart-rabbitmq'
        );
        
        // Open firewall port if UFW is enabled
        try {
            $this->server->ssh()->exec(
                'sudo ufw allow 15672/tcp',
                'open-management-port'
            );
        } catch (\Exception $e) {
            // UFW might not be enabled, that's okay
        }
        
        $this->addSuccessLog("RabbitMQ Management UI enabled on port 15672");
        $this->addSuccessLog("Access the UI at: http://{$this->server->ip}:15672");
    }
}