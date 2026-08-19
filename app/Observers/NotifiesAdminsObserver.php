<?php

namespace App\Observers;

use App\Services\NotificationCenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

/**
 * A single, config-driven observer that raises admin notifications for domain events
 * across the whole application. Attached to every model listed in config/notifications.php
 * so new coverage is a one-line config change rather than a new observer class.
 */
class NotifiesAdminsObserver
{
    /**
     * Attributes tried, in order, to build a human label for the affected record.
     *
     * @var list<string>
     */
    private const NAME_ATTRIBUTES = ['title', 'name', 'subject', 'reference', 'reference_no', 'invoice_number', 'code'];

    public function __construct(private readonly NotificationCenter $notifications) {}

    public function created(Model $model): void
    {
        $this->handle($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->handle($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->handle($model, 'deleted');
    }

    private function handle(Model $model, string $event): void
    {
        $config = config('notifications.models.'.get_class($model));

        if (! is_array($config) || ! in_array($event, $config['events'] ?? [], true)) {
            return;
        }

        $label = $config['label'] ?? Str::headline(class_basename($model));

        $this->notifications->toAdmins(
            type: $config['type'] ?? 'system',
            title: $this->title($event, $label),
            body: $this->describe($model),
            link: $this->link($config['route'] ?? null, $model),
            subject: $model,
            permission: $config['permission'] ?? null,
        );
    }

    private function title(string $event, string $label): string
    {
        return match ($event) {
            'created' => Str::ucfirst("New {$label}"),
            'deleted' => Str::ucfirst("{$label} deleted"),
            default => Str::ucfirst("{$label} updated"),
        };
    }

    private function describe(Model $model): ?string
    {
        foreach (self::NAME_ATTRIBUTES as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return Str::limit($value, 120);
            }
        }

        return '#'.$model->getKey();
    }

    /**
     * Resolve the notification's deep link, tolerating routes that do or don't take the
     * model as a parameter, and never letting a routing hiccup break the write.
     */
    private function link(?string $route, Model $model): ?string
    {
        if ($route === null || ! Route::has($route)) {
            return null;
        }

        try {
            return route($route, $model);
        } catch (Throwable) {
            try {
                return route($route);
            } catch (Throwable) {
                return null;
            }
        }
    }
}
