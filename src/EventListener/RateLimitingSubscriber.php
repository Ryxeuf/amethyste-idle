<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class RateLimitingSubscriber implements EventSubscriberInterface
{
    /** @var array<string, array{limiter: RateLimiterFactoryInterface, routes: list<string>}> */
    private readonly array $limiterMap;

    public function __construct(
        private readonly Security $security,
        private readonly RateLimiterFactoryInterface $apiMoveLimiter,
        private readonly RateLimiterFactoryInterface $gameFightLimiter,
        private readonly RateLimiterFactoryInterface $gameShopLimiter,
        private readonly RateLimiterFactoryInterface $gameCraftLimiter,
    ) {
        $this->limiterMap = [
            'api_move' => [
                'limiter' => $this->apiMoveLimiter,
                'routes' => ['api_map_move'],
            ],
            'game_fight' => [
                'limiter' => $this->gameFightLimiter,
                'routes' => [
                    'app_game_fight_attack',
                    'app_game_fight_spell',
                    'app_game_fight_item',
                    'app_game_fight_flee',
                ],
            ],
            'game_shop' => [
                'limiter' => $this->gameShopLimiter,
                'routes' => [
                    'app_game_shop_buy',
                    'app_game_shop_sell',
                ],
            ],
            'game_craft' => [
                'limiter' => $this->gameCraftLimiter,
                'routes' => [
                    'app_game_craft_execute',
                    'app_game_craft_experiment',
                ],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (!$route) {
            return;
        }

        foreach ($this->limiterMap as $config) {
            if (\in_array($route, $config['routes'], true)) {
                $user = $this->security->getUser();
                $identifier = $user?->getUserIdentifier() ?? $request->getClientIp() ?? 'anonymous';

                $limiter = $config['limiter']->create($identifier);
                $limit = $limiter->consume();

                if (!$limit->isAccepted()) {
                    $retryAfter = $limit->getRetryAfter();
                    $retrySeconds = max(1, $retryAfter->getTimestamp() - time());

                    $event->setResponse(new JsonResponse(
                        [
                            'error' => 'Trop de requêtes. Veuillez patienter avant de réessayer.',
                            'retry_after' => $retrySeconds,
                        ],
                        Response::HTTP_TOO_MANY_REQUESTS,
                        [
                            'Retry-After' => (string) $retrySeconds,
                            'X-RateLimit-Limit' => (string) $limit->getLimit(),
                            'X-RateLimit-Remaining' => (string) $limit->getRemainingTokens(),
                        ],
                    ));

                    return;
                }

                return;
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 10]],
        ];
    }
}
