<?php

namespace App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterServerFeature;
use App\Plugins\RegisterServerFeatureAction;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\EnableManagementUI;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\DisableManagementUI;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\CreateVirtualHost;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\DeleteVirtualHost;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\CreateUser;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\DeleteUser;
use App\Vito\Plugins\Makhweb\RabbitMQManagementPlugin\Actions\MonitorQueues;
use App\DTOs\DynamicForm;
use App\DTOs\DynamicField;

class Plugin extends AbstractPlugin
{
    protected string $name = 'RabbitMQ Management Plugin';

    protected string $description = 'Advanced management features for RabbitMQ including Management UI, virtual hosts, user management, and queue monitoring';

    public function boot(): void
    {
        // Register RabbitMQ Management UI feature
        RegisterServerFeature::make('rabbitmq-management-ui')
            ->label('RabbitMQ Management UI')
            ->description('Enable web-based management interface for RabbitMQ on port 15672')
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-management-ui', 'enable')
            ->label('Enable Management UI')
            ->form(DynamicForm::make([
                DynamicField::make('alert')
                    ->alert()
                    ->label('Important')
                    ->description('The Management UI will be accessible on port 15672. Make sure to configure firewall rules accordingly.'),
                DynamicField::make('bind_address')
                    ->text()
                    ->label('Bind Address')
                    ->default('0.0.0.0')
                    ->description('IP address to bind the management interface (0.0.0.0 for all interfaces)'),
                DynamicField::make('create_admin_user')
                    ->checkbox()
                    ->label('Create Admin User')
                    ->default(true)
                    ->description('Create a dedicated admin user for the management interface'),
                DynamicField::make('admin_username')
                    ->text()
                    ->label('Admin Username')
                    ->default('admin')
                    ->description('Username for the management interface admin'),
                DynamicField::make('admin_password')
                    ->text()
                    ->label('Admin Password')
                    ->description('Password for the management interface admin (leave empty to generate)'),
            ]))
            ->handler(EnableManagementUI::class)
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-management-ui', 'disable')
            ->label('Disable Management UI')
            ->handler(DisableManagementUI::class)
            ->register();
        
        // Register Virtual Host Management feature
        RegisterServerFeature::make('rabbitmq-vhost-management')
            ->label('RabbitMQ Virtual Hosts')
            ->description('Manage RabbitMQ virtual hosts for multi-tenant setups')
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-vhost-management', 'create')
            ->label('Create Virtual Host')
            ->form(DynamicForm::make([
                DynamicField::make('vhost_name')
                    ->text()
                    ->label('Virtual Host Name')
                    ->description('Name of the virtual host to create'),
                DynamicField::make('description')
                    ->text()
                    ->label('Description')
                    ->description('Optional description for the virtual host'),
                DynamicField::make('tags')
                    ->text()
                    ->label('Tags')
                    ->description('Comma-separated tags for the virtual host'),
                DynamicField::make('grant_permissions')
                    ->checkbox()
                    ->label('Grant Admin Permissions')
                    ->default(true)
                    ->description('Grant admin user full permissions on this vhost'),
            ]))
            ->handler(CreateVirtualHost::class)
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-vhost-management', 'delete')
            ->label('Delete Virtual Host')
            ->form(DynamicForm::make([
                DynamicField::make('vhost_name')
                    ->text()
                    ->label('Virtual Host Name')
                    ->description('Name of the virtual host to delete'),
                DynamicField::make('confirm')
                    ->checkbox()
                    ->label('Confirm Deletion')
                    ->description('Check to confirm deletion of the virtual host and all its data'),
            ]))
            ->handler(DeleteVirtualHost::class)
            ->register();
        
        // Register User Management feature
        RegisterServerFeature::make('rabbitmq-user-management')
            ->label('RabbitMQ Users')
            ->description('Manage RabbitMQ users and their permissions')
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-user-management', 'create')
            ->label('Create User')
            ->form(DynamicForm::make([
                DynamicField::make('username')
                    ->text()
                    ->label('Username')
                    ->description('Username for the new RabbitMQ user'),
                DynamicField::make('password')
                    ->text()
                    ->label('Password')
                    ->description('Password for the new user'),
                DynamicField::make('tags')
                    ->select()
                    ->label('User Tags')
                    ->options([
                        'management' => 'Management',
                        'administrator' => 'Administrator',
                        'monitoring' => 'Monitoring',
                        'policymaker' => 'Policy Maker',
                    ])
                    ->default('management')
                    ->description('Role tags for the user'),
                DynamicField::make('vhost')
                    ->text()
                    ->label('Virtual Host')
                    ->default('/')
                    ->description('Virtual host to grant permissions on'),
                DynamicField::make('configure_permission')
                    ->text()
                    ->label('Configure Permission')
                    ->default('.*')
                    ->description('Regular expression for configure permission'),
                DynamicField::make('write_permission')
                    ->text()
                    ->label('Write Permission')
                    ->default('.*')
                    ->description('Regular expression for write permission'),
                DynamicField::make('read_permission')
                    ->text()
                    ->label('Read Permission')
                    ->default('.*')
                    ->description('Regular expression for read permission'),
            ]))
            ->handler(CreateUser::class)
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-user-management', 'delete')
            ->label('Delete User')
            ->form(DynamicForm::make([
                DynamicField::make('username')
                    ->text()
                    ->label('Username')
                    ->description('Username of the RabbitMQ user to delete'),
                DynamicField::make('confirm')
                    ->checkbox()
                    ->label('Confirm Deletion')
                    ->description('Check to confirm deletion of the user'),
            ]))
            ->handler(DeleteUser::class)
            ->register();
        
        // Register Queue Monitoring feature
        RegisterServerFeature::make('rabbitmq-queue-monitoring')
            ->label('RabbitMQ Queue Monitor')
            ->description('Monitor and manage RabbitMQ queues')
            ->register();
        
        RegisterServerFeatureAction::make('rabbitmq-queue-monitoring', 'monitor')
            ->label('View Queue Status')
            ->form(DynamicForm::make([
                DynamicField::make('vhost')
                    ->text()
                    ->label('Virtual Host')
                    ->default('/')
                    ->description('Virtual host to monitor'),
                DynamicField::make('include_message_stats')
                    ->checkbox()
                    ->label('Include Message Statistics')
                    ->default(true)
                    ->description('Show detailed message statistics'),
                DynamicField::make('include_consumer_info')
                    ->checkbox()
                    ->label('Include Consumer Information')
                    ->default(true)
                    ->description('Show consumer details for each queue'),
            ]))
            ->handler(MonitorQueues::class)
            ->register();
    }
    
    public function install(): void
    {
        // Actions to perform when the plugin is installed
        // Could include creating configuration files, etc.
    }
    
    public function uninstall(): void
    {
        // Cleanup actions when the plugin is uninstalled
    }
}