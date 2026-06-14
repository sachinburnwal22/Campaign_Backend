<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Listeners\UpdateCustomerStats;
use App\Repositories\AiConversationRepositoryInterface;
use App\Repositories\EloquentAiConversationRepository;
use App\Repositories\CampaignEventRepositoryInterface;
use App\Repositories\CampaignRepositoryInterface;
use App\Repositories\CustomerRepositoryInterface;
use App\Repositories\CommunicationRepositoryInterface;
use App\Repositories\EloquentCampaignEventRepository;
use App\Repositories\EloquentCampaignRepository;
use App\Repositories\EloquentCustomerRepository;
use App\Repositories\EloquentCommunicationRepository;
use App\Repositories\EloquentOrderRepository;
use App\Repositories\EloquentSegmentRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\SegmentRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
        $this->app->bind(SegmentRepositoryInterface::class, EloquentSegmentRepository::class);
        $this->app->bind(CampaignRepositoryInterface::class, EloquentCampaignRepository::class);
        $this->app->bind(CommunicationRepositoryInterface::class, EloquentCommunicationRepository::class);
        $this->app->bind(CampaignEventRepositoryInterface::class, EloquentCampaignEventRepository::class);
        $this->app->bind(AiConversationRepositoryInterface::class, EloquentAiConversationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicitly register our event-listener link
        Event::listen(
            OrderCreated::class,
            UpdateCustomerStats::class
        );
    }
}
