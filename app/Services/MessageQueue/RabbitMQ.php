<?php

namespace App\Services\MessageQueue;

use App\Exceptions\ServiceInstallationFailed;
use App\Exceptions\SSHError;
use App\Services\AbstractService;
use Closure;

class RabbitMQ extends AbstractService
{
    public static function id(): string
    {
        return 'rabbitmq';
    }

    public static function type(): string
    {
        return 'message_queue';
    }

    public function unit(): string
    {
        return 'rabbitmq-server';
    }

    public function creationRules(array $input): array
    {
        return [
            'type' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $rabbitmqExists = $this->service->server->messageQueue();
                    if ($rabbitmqExists) {
                        $fail('You already have a RabbitMQ service on the server.');
                    }
                },
            ],
            'username' => 'required|string|min:3|max:50',
            'password' => 'required|string|min:8',
        ];
    }

    public function creationData(array $input): array
    {
        return [
            'username' => $input['username'],
            'password' => $input['password'],
        ];
    }

    /**
     * @throws ServiceInstallationFailed
     * @throws SSHError
     */
    public function install(): void
    {
        // Use the same installation script for all versions
        // RabbitMQ will be installed from the official repository which provides the latest stable version
        $this->service->server->ssh()->exec(
            view('ssh.services.message-queue.rabbitmq.install', [
                'username' => $this->service->type_data['username'],
                'password' => $this->service->type_data['password'],
            ]),
            'install-rabbitmq'
        );
        $status = $this->service->server->systemd()->status($this->unit());
        $this->service->validateInstall($status);
        
        // Enable management plugin
        $this->service->server->ssh()->exec(
            'sudo rabbitmq-plugins enable rabbitmq_management',
            'enable-rabbitmq-management'
        );
        
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.message-queue.rabbitmq.uninstall'),
            'uninstall-rabbitmq'
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function version(): string
    {
        return $this->service->server->ssh()->exec('sudo rabbitmqctl version | head -n 1');
    }
}