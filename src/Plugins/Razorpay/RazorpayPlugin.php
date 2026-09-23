<?php

namespace OpenKOS\Plugins\Razorpay;

use OpenKOS\Platform\OpenKOSManager;
use OpenKOS\Platform\Plugin\Plugin;
use OpenKOS\Platform\Plugin\PluginManifest;

class RazorpayPlugin extends Plugin
{
    public function manifest(): PluginManifest
    {
        return new PluginManifest(
            id: 'openkos/razorpay',
            name: 'Razorpay Payments',
            version: '1.0.0',
            description: 'Razorpay payment gateway integration for UPI, cards and net banking.',
            coreVersion: '^0.2',
        );
    }

    public function register(OpenKOSManager $platform): void
    {
        $platform->payments()->registerGateway(
            'razorpay',
            RazorpayGateway::class,
        );
    }
}