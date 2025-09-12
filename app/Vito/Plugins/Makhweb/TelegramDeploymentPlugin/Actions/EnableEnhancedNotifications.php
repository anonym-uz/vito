<?php

namespace App\Vito\Plugins\Makhweb\TelegramDeploymentPlugin\Actions;

use App\Models\Site;
use App\SiteFeatures\Action;
use App\Facades\Notifier;
use App\Vito\Plugins\Makhweb\TelegramDeploymentPlugin\Notifications\EnhancedDeploymentCompleted;
use Illuminate\Support\Facades\Event;

class EnableEnhancedNotifications extends Action
{
    public function __construct(public Site $site) {}

    public function run(array $input): void
    {
        // Store the configuration in the site's type_data
        $typeData = $this->site->type_data ?? [];
        $typeData['enhanced_telegram_notifications'] = [
            'enabled' => true,
            'include_commit_info' => $input['include_commit_info'] ?? true,
            'include_duration' => $input['include_duration'] ?? true,
            'include_server_info' => $input['include_server_info'] ?? false,
            'include_log_snippet' => $input['include_log_snippet'] ?? false,
            'custom_success_emoji' => $input['custom_success_emoji'] ?? '✅',
            'custom_failure_emoji' => $input['custom_failure_emoji'] ?? '❌',
        ];
        
        $this->site->type_data = $typeData;
        $this->site->save();
        
        // Register event listener for this site's deployments
        $this->registerDeploymentListener();
        
        $this->addSuccessLog('Enhanced Telegram notifications enabled for site');
    }
    
    private function registerDeploymentListener(): void
    {
        // This would typically be done in the plugin's boot method
        // but we'll store a flag that the plugin checks
        Event::listen('App\Events\DeploymentCompleted', function ($event) {
            if ($event->deployment->site_id === $this->site->id) {
                $config = $this->site->type_data['enhanced_telegram_notifications'] ?? [];
                if ($config['enabled'] ?? false) {
                    // Send enhanced notification
                    Notifier::send(
                        $this->site,
                        new EnhancedDeploymentCompleted(
                            $event->deployment,
                            $this->site,
                            $config
                        )
                    );
                }
            }
        });
    }
}