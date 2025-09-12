<?php

namespace App\Vito\Plugins\Makhweb\TelegramDeploymentPlugin;

use App\Plugins\AbstractPlugin;
use App\Plugins\RegisterSiteFeature;
use App\Plugins\RegisterSiteFeatureAction;
use App\Vito\Plugins\Makhweb\TelegramDeploymentPlugin\Actions\DisableEnhancedNotifications;
use App\Vito\Plugins\Makhweb\TelegramDeploymentPlugin\Actions\EnableEnhancedNotifications;
use Illuminate\Support\Facades\Event;
use App\DTOs\DynamicForm;
use App\DTOs\DynamicField;

class Plugin extends AbstractPlugin
{
    protected string $name = 'Enhanced Telegram Deployment Notifications';

    protected string $description = 'Provides detailed Telegram notifications for deployments with commit info, duration tracking, and custom templates';

    public function boot(): void
    {
        // Register the enhanced telegram notifications feature for all site types
        $siteTypes = ['laravel', 'php', 'wordpress', 'phpmyadmin', 'nodejs', 'python', 'static'];
        
        foreach ($siteTypes as $siteType) {
            RegisterSiteFeature::make($siteType, 'enhanced-telegram-notifications')
                ->label('Enhanced Telegram Notifications')
                ->description('Send detailed deployment notifications to Telegram with commit info and statistics')
                ->register();
            
            RegisterSiteFeatureAction::make($siteType, 'enhanced-telegram-notifications', 'enable')
                ->label('Enable')
                ->form(DynamicForm::make([
                    DynamicField::make('alert')
                        ->alert()
                        ->label('Configuration Required')
                        ->description('Make sure you have configured Telegram notifications in Settings > Notification Channels'),
                    DynamicField::make('include_commit_info')
                        ->checkbox()
                        ->label('Include Commit Information')
                        ->default(true)
                        ->description('Include commit hash, author, and message in notifications'),
                    DynamicField::make('include_duration')
                        ->checkbox()
                        ->label('Include Deployment Duration')
                        ->default(true)
                        ->description('Track and display how long the deployment took'),
                    DynamicField::make('include_server_info')
                        ->checkbox()
                        ->label('Include Server Information')
                        ->default(false)
                        ->description('Include server name and IP in notifications'),
                    DynamicField::make('include_log_snippet')
                        ->checkbox()
                        ->label('Include Log Snippet')
                        ->default(false)
                        ->description('Include last 10 lines of deployment log on failure'),
                    DynamicField::make('custom_success_emoji')
                        ->text()
                        ->label('Success Emoji')
                        ->default('✅')
                        ->description('Emoji to use for successful deployments'),
                    DynamicField::make('custom_failure_emoji')
                        ->text()
                        ->label('Failure Emoji')
                        ->default('❌')
                        ->description('Emoji to use for failed deployments'),
                ]))
                ->handler(EnableEnhancedNotifications::class)
                ->register();
            
            RegisterSiteFeatureAction::make($siteType, 'enhanced-telegram-notifications', 'disable')
                ->label('Disable')
                ->handler(DisableEnhancedNotifications::class)
                ->register();
        }
        
        // Listen to deployment events to send enhanced notifications
        Event::listen('deployment.completed', function ($deployment): void {
            $this->sendEnhancedNotification($deployment);
        });
    }
    
    private function sendEnhancedNotification($deployment): void
    {
        // This method will be called when a deployment completes
        // The actual notification logic will be in the EnhancedDeploymentCompleted class
    }
}