<?php

namespace App\Vito\Plugins\Makhweb\TelegramDeploymentPlugin\Notifications;

use App\Models\Deployment;
use App\Models\Site;
use App\Notifications\AbstractNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class EnhancedDeploymentCompleted extends AbstractNotification
{
    public function __construct(
        protected Deployment $deployment, 
        protected Site $site,
        protected array $config = []
    ) {}

    public function rawText(): string
    {
        return $this->buildDetailedMessage();
    }

    public function toEmail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Deployment Completed - :site', ['site' => $this->site->domain]))
            ->line($this->buildDetailedMessage());
    }

    public function toSlack(object $notifiable): string
    {
        return $this->buildDetailedMessage();
    }

    public function toDiscord(object $notifiable): string
    {
        return $this->buildDetailedMessage();
    }

    public function toTelegram(object $notifiable): string
    {
        $emoji = $this->deployment->status === 'finished' 
            ? ($this->config['custom_success_emoji'] ?? '✅')
            : ($this->config['custom_failure_emoji'] ?? '❌');
        
        $status = strtoupper($this->deployment->status);
        
        $message = "{$emoji} *Deployment {$status}*\n\n";
        $message .= "🌐 *Site:* `{$this->site->domain}`\n";
        $message .= "🌿 *Branch:* `{$this->site->branch}`\n";
        
        // Include server information if enabled
        if ($this->config['include_server_info'] ?? false) {
            $message .= "🖥️ *Server:* {$this->site->server->name} ({$this->site->server->ip})\n";
        }
        
        // Include commit information if enabled
        if (($this->config['include_commit_info'] ?? true) && $this->deployment->commit_id) {
            $commitShort = substr($this->deployment->commit_id, 0, 7);
            $message .= "\n📝 *Commit Details:*\n";
            $message .= "• *Hash:* `{$commitShort}`\n";
            
            if ($this->deployment->commit_data) {
                $commitData = $this->deployment->commit_data;
                if (isset($commitData['author'])) {
                    $message .= "• *Author:* {$commitData['author']}\n";
                }
                if (isset($commitData['message'])) {
                    $commitMessage = $this->truncateString($commitData['message'], 200);
                    $message .= "• *Message:* _{$commitMessage}_\n";
                }
            }
        }
        
        // Include deployment duration if enabled
        if ($this->config['include_duration'] ?? true) {
            $duration = $this->calculateDuration();
            if ($duration) {
                $message .= "\n⏱️ *Duration:* {$duration}\n";
            }
        }
        
        // Include deployment type (modern vs classic)
        if ($this->deployment->release) {
            $message .= "📦 *Release:* `{$this->deployment->release}`\n";
            $message .= "🚀 *Type:* Modern Deployment\n";
        } else {
            $message .= "🚀 *Type:* Classic Deployment\n";
        }
        
        // Include log snippet on failure if enabled
        if ($this->deployment->status === 'failed' && ($this->config['include_log_snippet'] ?? false)) {
            $logSnippet = $this->getLogSnippet();
            if ($logSnippet) {
                $message .= "\n📋 *Last Log Lines:*\n```\n{$logSnippet}\n```\n";
            }
        }
        
        // Add timestamp
        $message .= "\n🕐 *Time:* " . Carbon::now()->format('Y-m-d H:i:s T');
        
        // Add deployment ID for reference
        $message .= "\n🔗 *Deployment ID:* #{$this->deployment->id}";
        
        return $message;
    }
    
    private function buildDetailedMessage(): string
    {
        $status = $this->deployment->status === 'finished' ? 'completed successfully' : 'failed';
        $message = "Deployment for site [{$this->site->domain}] has {$status}";
        
        if ($this->deployment->commit_id) {
            $commitShort = substr($this->deployment->commit_id, 0, 7);
            $message .= " (commit: {$commitShort})";
        }
        
        if ($duration = $this->calculateDuration()) {
            $message .= " - Duration: {$duration}";
        }
        
        return $message;
    }
    
    private function calculateDuration(): ?string
    {
        if (!$this->deployment->created_at || !$this->deployment->updated_at) {
            return null;
        }
        
        $start = Carbon::parse($this->deployment->created_at);
        $end = Carbon::parse($this->deployment->updated_at);
        $diff = $end->diff($start);
        
        if ($diff->h > 0) {
            return sprintf('%dh %dm %ds', $diff->h, $diff->i, $diff->s);
        } elseif ($diff->i > 0) {
            return sprintf('%dm %ds', $diff->i, $diff->s);
        } else {
            return sprintf('%ds', $diff->s);
        }
    }
    
    private function getLogSnippet(): ?string
    {
        if (!$this->deployment->log) {
            return null;
        }
        
        try {
            $logContent = $this->deployment->log->content ?? '';
            $lines = explode("\n", $logContent);
            $lastLines = array_slice($lines, -10);
            return implode("\n", $lastLines);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function truncateString(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }
}