<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/support/helpers.php';

use app\repository\mysql\WalletRepository;
use app\repository\mysql\SubscriptionRepository;
use app\repository\mysql\ApiKeyRepository;
use app\repository\mysql\AnnouncementRepository;

$wallet = (new WalletRepository())->findByUserId(1);
$plan = (new SubscriptionRepository())->findCurrentByUserId(1);
$keys = (new ApiKeyRepository())->findByUserId(1);
$ann = (new AnnouncementRepository())->latest();

if (!is_array($wallet)) { fwrite(STDERR, "wallet invalid\n"); exit(1); }
if (!is_array($plan)) { fwrite(STDERR, "plan invalid\n"); exit(2); }
if (!is_array($keys)) { fwrite(STDERR, "apikey invalid\n"); exit(3); }
if (!is_array($ann)) { fwrite(STDERR, "announcement invalid\n"); exit(4); }

echo "user-center real-ready smoke ok\n";
